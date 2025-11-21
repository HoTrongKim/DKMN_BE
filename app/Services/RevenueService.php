<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\ThongKeDoanhThu;
use Illuminate\Support\Carbon;

class RevenueService
{
    public function recordTicketSale(Ticket $ticket, int $amount): void
    {
        $ticket->loadMissing('trip.nhaVanHanh');

        $vehicleType = $ticket->trip?->nhaVanHanh?->loai ?: 'tat_ca';
        $seatCount = $this->seatCount($ticket->seat_numbers);
        $date = Carbon::now()->toDateString();

        $this->accumulate($date, 'tat_ca', $amount, $seatCount);
        if ($vehicleType !== 'tat_ca') {
            $this->accumulate($date, $vehicleType, $amount, $seatCount);
        }
    }

    private function accumulate(string $date, string $vehicleType, int $amount, int $seatCount): void
    {
        $stat = ThongKeDoanhThu::query()
            ->where('ngay', $date)
            ->where('loai_phuong_tien', $vehicleType)
            ->lockForUpdate()
            ->first();

        if (!$stat) {
            $stat = new ThongKeDoanhThu([
                'ngay' => $date,
                'loai_phuong_tien' => $vehicleType,
                'so_don_hang' => 0,
                'tong_doanh_thu' => 0,
                'doanh_thu_thuc' => 0,
                'so_ve_ban' => 0,
                'so_ve_huy' => 0,
                'ty_le_huy' => 0,
            ]);
            if ($stat->isFillable('ngay_tao')) {
                $stat->ngay_tao = Carbon::now();
            }
        }

        $stat->so_don_hang = (int) $stat->so_don_hang + 1;
        $stat->tong_doanh_thu = (float) $stat->tong_doanh_thu + $amount;
        $stat->doanh_thu_thuc = (float) $stat->doanh_thu_thuc + $amount;
        $stat->so_ve_ban = (int) $stat->so_ve_ban + $seatCount;
        if ((int) $stat->so_ve_ban > 0) {
            $stat->ty_le_huy = round(((float) $stat->so_ve_huy) / $stat->so_ve_ban, 2);
        }
        $stat->save();
    }

    private function seatCount(?string $seatNumbers): int
    {
        if (!$seatNumbers) {
            return 0;
        }

        $seats = array_filter(array_map('trim', explode(',', $seatNumbers)));

        return count($seats);
    }
}
