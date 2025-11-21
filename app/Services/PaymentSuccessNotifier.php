<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\ThongBao;
use App\Models\Ticket;

class PaymentSuccessNotifier
{
    public function send(?Ticket $ticket, ?Payment $payment = null): void
    {
        if (!$ticket?->donHang || !$ticket->donHang->nguoi_dung_id) {
            return;
        }

        try {
            $order = $ticket->donHang;
            $trip = $ticket->trip()->with(['tramDi.tinhThanh', 'tramDen.tinhThanh'])->first();

            $from = $order->noi_di
                ?? $trip?->tramDi?->tinhThanh?->ten
                ?? $trip?->tramDi?->ten
                ?? 'Diem di';
            $to = $order->noi_den
                ?? $trip?->tramDen?->tinhThanh?->ten
                ?? $trip?->tramDen?->ten
                ?? 'Diem den';

            $departure = optional($trip?->gio_khoi_hanh)->timezone('Asia/Ho_Chi_Minh')->format('H:i d/m/Y');
            $seatLabels = $ticket->seat_numbers;
            $amount = (int) ($ticket->total_amount_vnd ?? $order->tong_tien ?? 0);

            $parts = [
                sprintf('Thanh toan thanh cong ma ve %s cho chuyen %s -> %s.', $order->ma_don, $from, $to),
            ];
            if ($departure) {
                $parts[] = 'Gio khoi hanh: ' . $departure;
            }
            if (!empty($seatLabels)) {
                $parts[] = 'Ghe: ' . $seatLabels;
            }
            if ($amount > 0) {
                $parts[] = 'Tong tien: ' . number_format($amount, 0, ',', '.') . ' VND';
            }

            ThongBao::create([
                'nguoi_dung_id' => $order->nguoi_dung_id,
                'tieu_de' => 'Thanh toan thanh cong',
                'noi_dung' => implode(' ', $parts),
                'loai' => 'success',
                'da_doc' => 0,
            ]);
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
