<?php

namespace App\Services;

use App\Models\NhatKyHoatDong;
use App\Models\Ticket;
use Illuminate\Http\Request;

class ActivityLogService
{
    public function __construct(private Request $request)
    {
    }

    public function logTicketPayment(Ticket $ticket, int $amount, ?int $paymentId = null): void
    {
        $ticket->loadMissing('donHang', 'trip');
        $order = $ticket->donHang;
        $userId = $order?->nguoi_dung_id ?? $this->request->user()?->id;

        NhatKyHoatDong::create([
            'nguoi_dung_id' => $userId,
            'hanh_dong' => 'dat_ve_thanh_cong',
            'mo_ta' => $this->buildDescription($ticket, $amount, $paymentId),
            'ip' => $this->request->ip(),
            'user_agent' => $this->request->userAgent(),
        ]);
    }

    private function buildDescription(Ticket $ticket, int $amount, ?int $paymentId): string
    {
        $orderCode = $ticket->donHang?->ma_don ?? $ticket->don_hang_id;
        $tripId = $ticket->trip_id;
        $seats = $ticket->seat_numbers;
        $formattedAmount = number_format($amount, 0, ',', '.');
        $paymentInfo = $paymentId ? " - thanh toán #{$paymentId}" : '';

        return sprintf(
            'Đặt vé thành công (Đơn %s, Chuyến %s, Ghế %s, Số tiền %s₫%s)',
            $orderCode,
            $tripId,
            $seats,
            $formattedAmount,
            $paymentInfo
        );
    }
}
