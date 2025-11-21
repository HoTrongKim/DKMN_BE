<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ChiTietDonHangSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('chi_tiet_don_hangs')->delete();

        $seatPools = [];
        DB::table('ghes')
            ->select('id', 'chuyen_di_id')
            ->orderBy('id')
            ->chunk(1000, function ($chunk) use (&$seatPools) {
                foreach ($chunk as $seat) {
                    $seatPools[$seat->chuyen_di_id] ??= [];
                    $seatPools[$seat->chuyen_di_id][] = $seat->id;
                }
            });

        $rows = [];
        DB::table('don_hangs')
            ->select('id', 'chuyen_di_id', 'ten_khach', 'sdt_khach', 'tong_tien', 'ngay_tao')
            ->orderBy('id')
            ->chunk(100, function ($orders) use (&$rows, &$seatPools) {
                foreach ($orders as $order) {
                    $seatId = $this->pullSeatId($seatPools, $order->chuyen_di_id);
                    if (!$seatId) {
                        continue;
                    }

                    $rows[] = [
                        'don_hang_id' => $order->id,
                        'ghe_id' => $seatId,
                        'ten_hanh_khach' => $order->ten_khach,
                        'sdt_hanh_khach' => $order->sdt_khach,
                        'gia_ghe' => $order->tong_tien,
                        'ngay_tao' => $order->ngay_tao,
                    ];
                }
            });

        if (!empty($rows)) {
            DB::table('chi_tiet_don_hangs')->insert($rows);
        }
    }

    private function pullSeatId(array &$seatPools, int $tripId): ?int
    {
        if (empty($seatPools[$tripId])) {
            return null;
        }

        return array_shift($seatPools[$tripId]);
    }
}

