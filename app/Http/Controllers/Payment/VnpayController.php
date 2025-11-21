<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Ticket;
use App\Services\PaymentService;
use App\Services\PaymentSuccessNotifier;
use App\Services\TicketHoldService;
use App\Services\TicketNotificationService;
use App\Services\VnpayService;
use App\Support\PriceNormalizer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class VnpayController extends Controller
{
    public function __construct(
        private readonly VnpayService $vnpayService,
        private readonly PaymentService $paymentService,
        private readonly TicketNotificationService $ticketNotificationService,
        private readonly PaymentSuccessNotifier $paymentSuccessNotifier
    ) {
    }

    public function init(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ticketId' => 'required|integer|exists:tickets,id',
            'bankCode' => 'nullable|string|max:50',
            'locale' => 'nullable|string|in:vn,en',
            'orderInfo' => 'nullable|string|max:255',
            'returnUrl' => 'nullable|url|max:255',
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
                'message' => 'Phien giu ghe da het han. Vui long dat lai chuyen.',
            ], 410);
        }

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
                ->where('method', 'VNPAY')
                ->where('provider', 'vnpay')
                ->where('idempotency_key', $idempotencyKey)
                ->latest()
                ->first();

            if ($existing) {
                return $this->respondWithPayment($existing);
            }
        }

        $expiresAt = Carbon::now()->addMinutes(
            (int) config('payments.vnpay.expire_minutes', config('payments.intent_expiration_minutes', 15))
        );

        $payment = DB::transaction(function () use ($ticket, $amount, $idempotencyKey, $expiresAt) {
            $payment = Payment::create([
                'ticket_id' => $ticket->id,
                'method' => 'VNPAY',
                'provider' => 'vnpay',
                'amount_vnd' => PriceNormalizer::clamp($amount),
                'status' => Payment::STATUS_PENDING,
                'idempotency_key' => $idempotencyKey,
                'expires_at' => $expiresAt,
            ]);

            $payment->provider_ref = 'VNPAY-' . $payment->id;
            $payment->checksum = $this->paymentService->computeChecksum($payment);
            $payment->save();

            return $payment->fresh();
        });

        try {
            $payData = $this->vnpayService->buildPayUrl($payment, [
                'bank_code' => $validated['bankCode'] ?? null,
                'locale' => $validated['locale'] ?? null,
                'order_info' => $validated['orderInfo'] ?? null,
                'return_url' => $validated['returnUrl'] ?? null,
                'ip_addr' => $request->ip(),
            ]);
        } catch (\Throwable $e) {
            $payment->delete();

            return response()->json([
                'status' => false,
                'message' => 'Cau hinh VNPAY chua du. Vui long kiem tra tmn_code/hash_secret/payment_url.',
            ], 503);
        }

        $payment->provider_ref = $payData['txn_ref'];
        $payment->save();

        return response()->json([
            'status' => true,
            'data' => $this->serializePayment($payment->fresh()),
            'payUrl' => $payData['pay_url'],
            'payload' => $payData['query'],
        ], 201);
    }

    public function ipn(Request $request): JsonResponse
    {
        $payload = $request->all();

        if (!$this->vnpayService->verifySignature($payload)) {
            return $this->ipnResponse('97', 'Invalid signature');
        }

        $txnRef = $payload['vnp_TxnRef'] ?? null;
        if (!$txnRef || !is_string($txnRef)) {
            return $this->ipnResponse('01', 'Missing transaction reference');
        }

        $payment = Payment::find($txnRef);
        if (!$payment) {
            return $this->ipnResponse('01', 'Payment not found');
        }

        $amount = (int) round(((int) ($payload['vnp_Amount'] ?? 0)) / 100);
        $allowedDelta = (int) config('payments.allowed_amount_delta', 0);

        if (abs($amount - (int) $payment->amount_vnd) > $allowedDelta) {
            $payment->update([
                'status' => Payment::STATUS_MISMATCH,
                'webhook_idempotency_key' => $payload['vnp_SecureHash'] ?? null,
            ]);

            return $this->ipnResponse('04', 'Amount mismatch');
        }

        if ($payment->status === Payment::STATUS_SUCCEEDED) {
            return $this->ipnResponse('00', 'Payment already confirmed', $payment);
        }

        $responseCode = (string) ($payload['vnp_ResponseCode'] ?? '');
        $txnStatus = (string) ($payload['vnp_TransactionStatus'] ?? '');

        if ($responseCode !== '00' || $txnStatus !== '00') {
            $payment->update([
                'status' => Payment::STATUS_FAILED,
                'provider_ref' => $payment->provider_ref ?? ($payload['vnp_TransactionNo'] ?? $txnRef),
                'webhook_idempotency_key' => $payload['vnp_SecureHash'] ?? null,
            ]);

            return $this->ipnResponse('00', 'Payment recorded as failed', $payment);
        }

        $ticket = $this->completePayment(
            $payment,
            $amount,
            $payload['vnp_SecureHash'] ?? null,
            $payload['vnp_TransactionNo'] ?? ($payload['vnp_BankTranNo'] ?? $payment->provider_ref)
        );

        if ($ticket) {
            $this->ticketNotificationService->sendTicketBookedMail($ticket, $payment->fresh());
            $this->paymentSuccessNotifier->send($ticket, $payment->fresh());
        }

        return $this->ipnResponse('00', 'Confirm success', $payment->fresh());
    }

    public function handleReturn(Request $request): JsonResponse
    {
        $payload = $request->all();
        $txnRef = $payload['vnp_TxnRef'] ?? null;
        $payment = $txnRef ? Payment::find($txnRef) : null;

        if (!$payment) {
            return response()->json([
                'status' => false,
                'message' => 'Payment not found',
            ], 404);
        }

        $amount = (int) round(((int) ($payload['vnp_Amount'] ?? 0)) / 100);
        $responseCode = (string) ($payload['vnp_ResponseCode'] ?? '');
        $txnStatus = (string) ($payload['vnp_TransactionStatus'] ?? '');

        if ($this->vnpayService->verifySignature($payload)
            && $responseCode === '00'
            && $txnStatus === '00'
            && $payment->status !== Payment::STATUS_SUCCEEDED
        ) {
            $ticket = $this->completePayment(
                $payment,
                $amount,
                $payload['vnp_SecureHash'] ?? null,
                $payload['vnp_TransactionNo'] ?? ($payload['vnp_BankTranNo'] ?? $payment->provider_ref)
            );

            if ($ticket) {
                $this->ticketNotificationService->sendTicketBookedMail($ticket, $payment->fresh());
                $this->paymentSuccessNotifier->send($ticket, $payment->fresh());
            }
        }

        return response()->json([
            'status' => true,
            'data' => $this->serializePayment($payment->fresh()),
            'message' => $responseCode === '00' ? 'Payment success' : 'Payment failed or cancelled',
        ]);
    }

    private function completePayment(
        Payment $payment,
        int $amount,
        ?string $idempotencyKey,
        ?string $providerRef
    ): ?Ticket {
        $ticketForMail = null;

        DB::transaction(function () use ($payment, $amount, $idempotencyKey, $providerRef, &$ticketForMail) {
            $payment->update([
                'status' => Payment::STATUS_SUCCEEDED,
                'paid_at' => now(),
                'webhook_idempotency_key' => $idempotencyKey,
                'provider_ref' => $providerRef ?: $payment->provider_ref,
                'amount_vnd' => PriceNormalizer::clamp($amount),
            ]);

            $ticket = $payment->ticket()->lockForUpdate()->first();
            if ($ticket) {
                $this->paymentService->setAmountOnTicket($ticket, $payment->amount_vnd, $payment->id);
                $ticketForMail = $ticket->fresh();
            }
        });

        return $ticketForMail;
    }

    private function ipnResponse(string $code, string $message, ?Payment $payment = null): JsonResponse
    {
        $data = [
            'RspCode' => $code,
            'Message' => $message,
        ];

        if ($payment) {
            $data['payment'] = $this->serializePayment($payment->fresh());
        }

        return response()->json($data);
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
}
