<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Ticket;
use App\Services\PaymentProviderFactory;
use App\Services\PaymentSuccessNotifier;
use App\Services\PaymentService;
use App\Services\TicketNotificationService;
use App\Services\TicketHoldService;
use App\Support\PriceNormalizer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PaymentController extends Controller
{
    public function __construct(
        private readonly PaymentProviderFactory $providerFactory,
        private readonly PaymentService $paymentService,
        private readonly TicketNotificationService $ticketNotificationService,
        private readonly PaymentSuccessNotifier $paymentSuccessNotifier
    ) {
    }

    public function initQr(Request $request): JsonResponse
    {
        $channelRule = ['nullable', 'string'];
        $providerKeys = $this->providerKeys();
        if (!empty($providerKeys)) {
            $channelRule[] = Rule::in($providerKeys);
        }

        $validated = $request->validate([
            'ticketId' => 'required|integer|exists:tickets,id',
            'channel' => $channelRule,
            'testMode' => 'nullable|boolean',
        ]);

        $ticket = Ticket::with('donHang')->findOrFail($validated['ticketId']);
        if ($response = $this->guardTicketOwner($request, $ticket)) {
            return $response;
        }
        if (TicketHoldService::isExpired($ticket)) {
            TicketHoldService::expireTicket($ticket);
            return response()->json([
                'status' => false,
                'message' => 'Phiên giữ ghế đã hết hạn. Vui lòng đặt lại chuyến.',
            ], 410);
        }
        $channel = $validated['channel'] ?? config('payments.default_provider', 'vietqr');
        $provider = $this->providerFactory->getProvider($channel);
        $fare = $this->paymentService->computeFare($ticket);
        $amount = (int) ($fare['totalAmount'] ?? 0);

        if ($request->boolean('testMode')) {
            $amount = (int) config('payments.test_amount_vnd', $amount ?: 0);
        }

        if ($amount <= 0) {
            $amount = (int) config('payments.default_fare_vnd', 1200);
        }

        $idempotencyKey = $request->header('Idempotency-Key');
        if ($idempotencyKey) {
            $existing = Payment::query()
                ->where('ticket_id', $ticket->id)
                ->where('method', 'QR')
                ->where('provider', $provider->key())
                ->where('idempotency_key', $idempotencyKey)
                ->latest()
                ->first();

            if ($existing) {
                return $this->respondWithPayment($existing);
            }
        }

        $expiresAt = Carbon::now()->addMinutes((int) config('payments.intent_expiration_minutes', 15));

        $payment = DB::transaction(function () use ($ticket, $provider, $amount, $idempotencyKey, $expiresAt) {
            $payment = Payment::create([
                'ticket_id' => $ticket->id,
                'method' => 'QR',
                'provider' => $provider->key(),
                'amount_vnd' => PriceNormalizer::clamp($amount),
                'status' => Payment::STATUS_PENDING,
                'idempotency_key' => $idempotencyKey,
                'expires_at' => $expiresAt,
            ]);

            $qrPayload = $provider->generateQr($payment->amount_vnd, $payment);
            $providerRef = $qrPayload['providerRef'] ?? strtoupper(sprintf('%s-%s', $provider->key(), $payment->id));
            $qrUrl = $qrPayload['qrImageUrl'] ?? null;

            $payment->provider_ref = $providerRef;
            $payment->qr_image_url = $qrUrl;
            $payment->checksum = $this->paymentService->computeChecksum($payment);
            $payment->save();

            return $payment->fresh();
        });

        return $this->respondWithPayment($payment, 201);
    }

    public function status(Request $request, Payment $payment): JsonResponse
    {
        if ($response = $this->guardPaymentOwner($request, $payment)) {
            return $response;
        }

        return $this->respondWithPayment($payment);
    }

    public function handleQrWebhook(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'providerRef' => 'required|string|max:150',
            'amount_vnd' => 'required|integer|min:0',
            'status' => 'required|string',
        ]);

        $signature = $request->header('X-Signature');
        if (!$signature) {
            return response()->json([
                'status' => false,
                'message' => 'Missing signature',
            ], 401);
        }

        $expectedSignature = hash_hmac(
            'sha256',
            json_encode($payload, JSON_UNESCAPED_UNICODE),
            config('payments.webhook_secret')
        );

        if (!hash_equals($expectedSignature, $signature)) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid signature',
            ], 401);
        }

        $payment = Payment::where('provider_ref', $payload['providerRef'])->first();
        if (!$payment) {
            return response()->json([
                'status' => false,
                'message' => 'Payment not found',
            ], 404);
        }

        $idempotencyKey = $request->header('X-Idempotency-Key');
        if ($idempotencyKey && $payment->webhook_idempotency_key === $idempotencyKey) {
            return $this->respondWithPayment($payment);
        }

        $amount = PriceNormalizer::clamp((int) $payload['amount_vnd']);
        $allowedDelta = (int) config('payments.allowed_amount_delta', 0);

        if (abs($amount - (int) $payment->amount_vnd) > $allowedDelta) {
            $payment->update([
                'status' => Payment::STATUS_MISMATCH,
                'webhook_idempotency_key' => $idempotencyKey,
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Amount mismatch',
                'data' => $this->serializePayment($payment->fresh()),
            ], 422);
        }

        $incomingStatus = strtoupper($payload['status']);
        if ($incomingStatus !== 'SUCCESS') {
            $payment->update([
                'status' => $incomingStatus === 'EXPIRED'
                    ? Payment::STATUS_EXPIRED
                    : Payment::STATUS_FAILED,
                'webhook_idempotency_key' => $idempotencyKey,
            ]);

            return $this->respondWithPayment($payment->fresh());
        }

        $ticketForMail = null;
        DB::transaction(function () use ($payment, $idempotencyKey, &$ticketForMail) {
            $payment->update([
                'status' => Payment::STATUS_SUCCEEDED,
                'paid_at' => now(),
                'webhook_idempotency_key' => $idempotencyKey,
            ]);

            $ticket = $payment->ticket()->lockForUpdate()->first();
            if ($ticket) {
                $this->paymentService->setAmountOnTicket($ticket, $payment->amount_vnd, $payment->id);
                $ticketForMail = $ticket->fresh();
            }
        });

        $payment->refresh();
        if ($ticketForMail) {
            $this->ticketNotificationService->sendTicketBookedMail($ticketForMail, $payment);
            $this->paymentSuccessNotifier->send($ticketForMail, $payment);
        }

        return $this->respondWithPayment($payment->fresh());
    }

    public function confirmOnboard(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ticketId' => 'required|integer|exists:tickets,id',
            'operatorId' => 'nullable|string|max:100',
            'note' => 'nullable|string|max:500',
            'method' => ['nullable', 'string', Rule::in(['QR', 'CASH_ONBOARD', 'MANUAL'])],
            'provider' => 'nullable|string|max:100',
        ]);

        $ticket = Ticket::with('donHang')->findOrFail($validated['ticketId']);
        if ($response = $this->guardTicketOwner($request, $ticket)) {
            return $response;
        }
        if (TicketHoldService::isExpired($ticket)) {
            TicketHoldService::expireTicket($ticket);
            return response()->json([
                'status' => false,
                'message' => 'Phiên giữ ghế đã hết hạn. Vui lòng đặt lại chuyến.',
            ], 410);
        }
        $method = $this->normalizeMethod($validated['method'] ?? null);

        $existing = $ticket->payments()
            ->where('method', $method)
            ->where('status', Payment::STATUS_SUCCEEDED)
            ->latest()
            ->first();

        if ($existing) {
            return $this->respondWithPayment($existing);
        }

        $fare = $this->paymentService->computeFare($ticket);
        $amount = (int) ($fare['totalAmount'] ?? config('payments.default_fare_vnd', 1200));

        $ticketForMail = null;
        $payment = DB::transaction(function () use ($ticket, $amount, $validated, $method, &$ticketForMail) {
            $payment = Payment::create([
                'ticket_id' => $ticket->id,
                'method' => $method,
                'provider' => $validated['provider'] ?? ($method === 'QR' ? 'vietqr' : 'cash_onboard'),
                'provider_ref' => $validated['operatorId'] ?? null,
                'amount_vnd' => PriceNormalizer::clamp($amount),
                'status' => Payment::STATUS_SUCCEEDED,
                'paid_at' => now(),
            ]);

            $this->paymentService->setAmountOnTicket($ticket, $payment->amount_vnd, $payment->id);
            $ticketForMail = $ticket->fresh();

            return $payment->fresh();
        });

        if ($ticketForMail) {
            $this->ticketNotificationService->sendTicketBookedMail($ticketForMail, $payment);
            $this->paymentSuccessNotifier->send($ticketForMail, $payment);
        }

        return $this->respondWithPayment($payment, 201);
    }

    private function guardPaymentOwner(Request $request, Payment $payment): ?JsonResponse
    {
        $payment->loadMissing('ticket.donHang');

        $ticket = $payment->ticket;
        if (!$ticket) {
            return response()->json([
                'status' => false,
                'message' => 'Khong tim thay ve tuong ung.',
            ], 404);
        }

        return $this->guardTicketOwner($request, $ticket);
    }

    private function guardTicketOwner(Request $request, Ticket $ticket): ?JsonResponse
    {
        $user = $request->user('sanctum') ?? $request->user();

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Yeu cau dang nhap.',
            ], 401);
        }

        $role = strtolower((string) ($user->vai_tro ?? ''));
        if ($role === 'quan_tri') {
            return null;
        }

        $ownerId = $ticket->donHang?->nguoi_dung_id;
        if ((int) $ownerId !== (int) $user->id) {
            return response()->json([
                'status' => false,
                'message' => 'Khong co quyen thao tac tren ve nay.',
            ], 403);
        }

        return null;
    }

    private function respondWithPayment(Payment $payment, int $status = 200): JsonResponse
    {
        return response()->json([
            'status' => true,
            'data' => $this->serializePayment($payment->fresh()),
        ], $status);
    }

    private function serializePayment(Payment $payment): array
    {
        return [
            'paymentId' => $payment->id,
            'ticketId' => $payment->ticket_id,
            'status' => $payment->status,
            'method' => $payment->method,
            'provider' => $payment->provider,
            'providerRef' => $payment->provider_ref,
            'amount' => (int) $payment->amount_vnd,
            'currency' => 'VND',
            'qrImageUrl' => $payment->qr_image_url,
            'expiresAt' => optional($payment->expires_at)->toIso8601String(),
            'paidAt' => optional($payment->paid_at)->toIso8601String(),
        ];
    }

    private function providerKeys(): array
    {
        return array_keys(config('payments.providers', []));
    }

    private function normalizeMethod(?string $method): string
    {
        return match (strtoupper((string) $method)) {
            'QR' => 'QR',
            'MANUAL' => 'MANUAL',
            default => 'CASH_ONBOARD',
        };
    }
}
