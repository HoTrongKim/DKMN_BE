<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TicketSeeder extends Seeder
{
    public function run(): void
    {
        if (!DB::getSchemaBuilder()->hasTable('tickets')) {
            return;
        }

        Schema::withoutForeignKeyConstraints(function () {
            DB::table('tickets')->truncate();
        });

        $orders = DB::table('don_hangs')
            ->select('id', 'chuyen_di_id', 'tong_tien', 'ngay_tao', 'ngay_cap_nhat', 'trang_thai')
            ->orderBy('id')
            ->get();

        if ($orders->isEmpty()) {
            return;
        }

        $tripFares = DB::table('chuyen_dis')->pluck('gia_co_ban', 'id');
        $seatCodes = ['A1', 'A2', 'B1', 'B2', 'C1', 'C2', 'D1', 'D2', 'E1', 'E2'];

        $rows = [];
        foreach ($orders as $index => $order) {
            $baseFare = (int) ($tripFares[$order->chuyen_di_id] ?? $order->tong_tien);
            $discount = ($index % 3 === 0) ? 50000 : 0;
            $surcharge = ($index % 2 === 0) ? 20000 : 0;
            $total = max(0, $baseFare - $discount + $surcharge);

            $status = match ($order->trang_thai) {
                'da_huy' => 'CANCELLED',
                'hoan_tat' => 'PAID',
                'da_xac_nhan' => 'PAID',
                default => 'PENDING',
            };
            $isPaid = $status === 'PAID';

            $createdAt = Carbon::parse($order->ngay_tao, 'Asia/Ho_Chi_Minh');
            $updatedAt = Carbon::parse($order->ngay_cap_nhat, 'Asia/Ho_Chi_Minh');

            $rows[] = [
                'don_hang_id' => $order->id,
                'trip_id' => $order->chuyen_di_id,
                'seat_numbers' => $seatCodes[$index % count($seatCodes)],
                'status' => $status,
                'base_fare_vnd' => $baseFare,
                'discount_vnd' => $discount,
                'surcharge_vnd' => $surcharge,
                'total_amount_vnd' => $total,
                'paid_amount_vnd' => $isPaid ? $total : null,
                'payment_id' => null,
                'created_at' => $createdAt,
                'updated_at' => $updatedAt,
            ];
        }

        DB::table('tickets')->insert($rows);
    }
}
