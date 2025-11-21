<?php

namespace App\Services;

use App\Models\ChiTietDonHang;
use App\Models\Payment;
use App\Models\Ticket;
use App\Support\PriceNormalizer;
use App\Services\ActivityLogService;
use Illuminate\Support\Str;

class PaymentService
{
    public function __construct(
        private readonly RevenueService $revenueService,
        private readonly ActivityLogService $activityLogService
    ) {
    }

    public function computeFare(Ticket $ticket, array $overrides = []): array
    {
        $baseFare = $this->resolveBaseFare($ticket);
        $discount = max(0, (int) ($overrides['discount'] ?? $ticket->discount_vnd ?? 0));
        $discount = min($discount, $baseFare);
        $surcharge = max(0, (int) ($overrides['surcharge'] ?? $ticket->surcharge_vnd ?? 0));
        $total = PriceNormalizer::clamp(max(0, $baseFare - $discount + $surcharge));

        return [
            'baseFare' => $baseFare,
            'discount' => $discount,
            'surcharge' => $surcharge,
            'totalAmount' => $total,
            'currency' => 'VND',
        ];
    }

    public function sumSeatPrices(int $donHangId): int
    {
        $sum = ChiTietDonHang::where('don_hang_id', $donHangId)
            ->sum('gia_ghe');

        return (int) round($sum);
    }

    private function resolveBaseFare(Ticket $ticket): int
    {
        $baseFare = $this->normalizeAmount($ticket->total_amount_vnd);

        if ($baseFare <= 0) {
            $baseFare = $this->normalizeAmount($ticket->base_fare_vnd);
        }

        if ($baseFare <= 0) {
            $baseFare = $this->sumSeatPrices($ticket->don_hang_id);
        }

        if ($baseFare <= 0) {
            $baseFare = $this->resolveDefaultFare();
        }

        return PriceNormalizer::clamp($baseFare);
    }

    private function resolveDefaultFare(): int
    {
        $default = max(0, (int) round((float) config('payments.default_fare_vnd', 1200)));

        return PriceNormalizer::clamp($default);
    }

    private function normalizeAmount(mixed $value): int
    {
        if (!is_numeric($value)) {
            return 0;
        }

        return (int) round((float) $value);
    }

    public function computeChecksum(Payment $payment): string
    {
        $raw = implode('|', [
            $payment->ticket_id,
            $payment->method,
            $payment->amount_vnd,
            $payment->provider ?? '',
            $payment->provider_ref ?? '',
            now()->timestamp,
            Str::uuid(),
        ]);

        return hash('sha256', $raw);
    }

    public function setAmountOnTicket(Ticket $ticket, int $amount, ?int $paymentId = null): void
    {
        $normalized = PriceNormalizer::clamp($amount);
        $ticket->update([
            'paid_amount_vnd' => $normalized,
            'status' => Ticket::STATUS_PAID,
            'payment_id' => $paymentId ?? $ticket->payment_id,
        ]);

        $order = $ticket->relationLoaded('donHang') ? $ticket->donHang : $ticket->donHang()->first();
        if ($order) {
            $order->update([
                'trang_thai' => 'da_xac_nhan',
                'ngay_cap_nhat' => now(),
            ]);
        }

        $this->revenueService->recordTicketSale($ticket, $normalized);
        $this->activityLogService->logTicketPayment($ticket, $normalized, $paymentId);
    }
}
