<?php

namespace App\Services;

use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentReconcileService
{
    public function __construct(
        private readonly PaymentService $paymentService,
        private readonly TicketNotificationService $ticketNotificationService
    ) {
    }

    public function extractOrderCode(?string $description, ?string $explicitCode = null): ?string
    {
        if ($explicitCode && ($normalized = $this->normalizeOrderCode($explicitCode))) {
            return $normalized;
        }

        if (!$description) {
            return null;
        }

        $regexes = array_filter([
            config('payments.bank.description_regex'),
        ]);

        foreach ($regexes as $regex) {
            try {
                if ($regex && preg_match($regex, $description, $matches)) {
                    $raw = $matches[1] ?? $matches[0] ?? null;
                    if ($raw) {
                        $normalized = $this->normalizeOrderCode($raw);
                        if ($normalized) {
                            return $normalized;
                        }
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('Invalid order code regex for payment reconcile', [
                    'regex' => $regex,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $this->extractLegacyDhCode($description);
    }

    public function findMatchingPayment(string $code, int $amount): ?Payment
    {
        $delta = max(0, (int) config('payments.allowed_amount_delta', 0));
        $query = Payment::query()
            ->whereIn('status', [Payment::STATUS_PENDING, Payment::STATUS_MISMATCH])
            ->whereHas('ticket.donHang', fn ($q) => $q->where('ma_don', $code));

        if ($delta > 0) {
            $min = max(0, $amount - $delta);
            $query->whereBetween('amount_vnd', [$min, $amount + $delta]);
        } else {
            $query->where('amount_vnd', $amount);
        }

        return $query->latest()->first();
    }

    public function markPaymentSuccess(Payment $payment, ?string $refNo = null): void
    {
        $ticketForMail = null;

        DB::transaction(function () use ($payment, $refNo, &$ticketForMail) {
            $payment->update([
                'status' => Payment::STATUS_SUCCEEDED,
                'paid_at' => now(),
                'provider_ref' => $refNo ?: $payment->provider_ref,
            ]);

            $ticket = $payment->ticket()->lockForUpdate()->first();
            if ($ticket) {
                $this->paymentService->setAmountOnTicket($ticket, $payment->amount_vnd, $payment->id);
                $ticketForMail = $ticket->fresh();
            }
        });

        if ($ticketForMail) {
            $this->ticketNotificationService->sendTicketBookedMail($ticketForMail, $payment->fresh());
        }
    }

    private function normalizeOrderCode(string $raw): ?string
    {
        $normalized = strtoupper(preg_replace('/[^A-Z0-9\-]/', '', $raw));
        if ($normalized === '') {
            return null;
        }

        if (str_starts_with($normalized, 'ORD')) {
            if (str_contains($normalized, '-')) {
                return $normalized;
            }

            if (preg_match('/^ORD([0-9]{6,})([A-Z0-9]{3,})$/', $normalized, $matches)) {
                return sprintf('ORD%s-%s', $matches[1], $matches[2]);
            }

            return $normalized;
        }

        if (str_starts_with($normalized, 'DH')) {
            return $this->extractLegacyDhCode($normalized);
        }

        if (preg_match('/^[0-9A-Z\-]+$/', $normalized)) {
            $sanitized = ltrim($normalized, '-');
            if (preg_match('/^([0-9]{6,})([A-Z0-9]{3,})$/', $sanitized, $matches)) {
                return sprintf('ORD%s-%s', $matches[1], $matches[2]);
            }

            return 'ORD' . $sanitized;
        }

        return null;
    }

    private function extractLegacyDhCode(string $description): ?string
    {
        if (!preg_match('/DH([A-F0-9\s\-]+)/i', $description, $matches)) {
            return null;
        }

        $rawUuid = strtoupper(preg_replace('/[^A-F0-9]/', '', $matches[1] ?? ''));
        if (strlen($rawUuid) < 32) {
            return null;
        }

        $rawUuid = substr($rawUuid, 0, 32);
        $formatted = substr($rawUuid, 0, 8) . '-' .
            substr($rawUuid, 8, 4) . '-' .
            substr($rawUuid, 12, 4) . '-' .
            substr($rawUuid, 16, 4) . '-' .
            substr($rawUuid, 20, 12);

        return 'DH-' . $formatted;
    }

}
