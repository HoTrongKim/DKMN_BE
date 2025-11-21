<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ThanhToanSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('thanh_toans')->truncate();

        $orders = DB::table('don_hangs')
            ->select('id', 'tong_tien', 'ngay_tao', 'trang_thai')
            ->orderBy('id')
            ->get()
            ->keyBy('id');

        if ($orders->isEmpty()) {
            return;
        }

        $gateways = ['momo', 'zalopay', 'paypal', 'ngan_hang', 'tra_sau'];
        $rows = [];

        foreach ($orders as $order) {
            $status = match ($order->trang_thai) {
                'da_huy' => 'hoan_tien',
                'hoan_tat', 'da_xac_nhan' => 'thanh_cong',
                default => 'cho',
            };

            $createdAt = Carbon::parse($order->ngay_tao, 'Asia/Ho_Chi_Minh');
            $processedAt = in_array($status, ['thanh_cong', 'hoan_tien'], true)
                ? $createdAt->copy()->addMinutes(45)
                : null;

            $gateway = $gateways[$order->id % count($gateways)];

            $rows[] = [
                'id' => count($rows) + 1,
                'don_hang_id' => $order->id,
                'ma_thanh_toan' => sprintf('PAY%s%03d', $createdAt->format('ymd'), $order->id),
                'cong_thanh_toan' => $gateway,
                'so_tien' => (float) $order->tong_tien,
                'trang_thai' => $status,
                'ma_giao_dich' => sprintf('%s-%04d', strtoupper(substr($gateway, 0, 3)), 1000 + $order->id),
                'phan_hoi_gateway' => null,
                'thoi_diem_thanh_toan' => $processedAt,
                'ngay_tao' => $createdAt,
                'ngay_cap_nhat' => $processedAt ?? $createdAt,
            ];
        }

        DB::table('thanh_toans')->insert($rows);
    }
}
