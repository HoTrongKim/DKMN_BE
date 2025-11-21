<?php

namespace App\Services;

use App\Mail\TicketBookedMail;
use App\Models\Payment;
use App\Models\Ticket;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;

class TicketNotificationService
{
    public function sendTicketBookedMail(?Ticket $ticket, ?Payment $payment = null): void
    {
        if (!$ticket) {
            return;
        }

        $ticket->loadMissing([
            'donHang.nguoiDung',
            'donHang.chiTietDonHang.ghe',
            'trip.tramDi.tinhThanh',
            'trip.tramDen.tinhThanh',
            'trip.nhaVanHanh',
        ]);

        $order = $ticket->donHang;
        if (!$order) {
            return;
        }

        $email = $order->email_khach
            ?? $order->nguoiDung?->email;

        if (!$email) {
            return;
        }

        $payload = $this->buildPayload($ticket, $payment);

        try {
            Mail::to($email)->send(new TicketBookedMail($payload));
        } catch (\Throwable $exception) {
            report($exception);
        }
    }

    private function buildPayload(Ticket $ticket, ?Payment $payment = null): array
    {
        $order = $ticket->donHang;
        $trip = $ticket->trip;

        $from = $order->noi_di
            ?? $trip?->tramDi?->tinhThanh?->ten
            ?? $trip?->tramDi?->ten;
        $to = $order->noi_den
            ?? $trip?->tramDen?->tinhThanh?->ten
            ?? $trip?->tramDen?->ten;

        $pickup = $order->tram_don
            ?? $trip?->tramDi?->ten
            ?? $from;

        $dropoff = $order->tram_tra
            ?? $trip?->tramDen?->ten
            ?? $to;

        $seats = $order->chiTietDonHang
            ?->map(function ($detail) {
                $label = $detail->ghe?->so_ghe ?? $detail->ghe_id;
                return [
                    'label' => (string) $label,
                    'price' => (int) round((float) $detail->gia_ghe),
                ];
            })
            ->filter(fn ($seat) => !empty($seat['label']))
            ->values()
            ->toArray() ?? [];

        $seatCodes = $this->parseSeatNumbers($ticket->seat_numbers);
        if (empty($seats) && !empty($seatCodes)) {
            $count = max(1, count($seatCodes));
            $perSeat = (int) round(($ticket->total_amount_vnd ?: 0) / $count);

            $seats = array_map(function ($seatCode) use ($perSeat) {
                return [
                    'label' => $seatCode,
                    'price' => max(0, $perSeat),
                ];
            }, $seatCodes);
        }

        $baseFare = (int) ($ticket->base_fare_vnd ?: array_sum(array_column($seats, 'price')));
        $discount = (int) ($ticket->discount_vnd ?? 0);
        $total = (int) ($ticket->total_amount_vnd ?: max(0, $baseFare - $discount));

        $method = strtoupper($order->cong_thanh_toan ?? $payment?->method ?? 'QR');
        $status = strtoupper($payment?->status ?? Payment::STATUS_SUCCEEDED) === Payment::STATUS_SUCCEEDED
            ? 'ĐÃ THANH TOÁN'
            : strtoupper($payment?->status ?? 'ĐANG XỬ LÝ');

        return [
            'customer' => [
                'name' => $order->ten_khach
                    ?? $order->nguoiDung?->ho_ten
                    ?? 'Quý khách',
                'email' => $order->email_khach
                    ?? $order->nguoiDung?->email,
                'phone' => $order->sdt_khach,
                'bookingCode' => $order->ma_don,
            ],
            'trip' => [
                'route' => trim(($from ?? '') . ' → ' . ($to ?? '')),
                'operator' => $order->ten_nha_van_hanh ?? $trip?->nhaVanHanh?->ten,
                'vehicle' => $trip?->nhaVanHanh?->loai,
                'departure' => $this->formatDate($trip?->gio_khoi_hanh),
                'arrival' => $this->formatDate($trip?->gio_den),
                'pickup' => $pickup,
                'dropoff' => $dropoff,
            ],
            'seats' => $seats,
            'totals' => [
                'subtotal' => max(0, $baseFare),
                'discount' => max(0, $discount),
                'total' => max(0, $total),
            ],
            'payment' => [
                'method' => $method,
                'status' => $status,
            ],
        ];
    }

    private function parseSeatNumbers(?string $value): array
    {
        if (!$value) {
            return [];
        }

        return array_values(array_filter(array_map(function ($seat) {
            return trim($seat);
        }, explode(',', $value))));
    }

    private function formatDate($value): ?string
    {
        if (!$value) {
            return null;
        }

        try {
            return Carbon::parse($value)->timezone('Asia/Ho_Chi_Minh')->format('H:i d/m/Y');
        } catch (\Throwable $exception) {
            return null;
        }
    }
}
