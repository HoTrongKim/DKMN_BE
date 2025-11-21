<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DonHangSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('don_hangs')->delete();

        $anchor = Carbon::now('Asia/Ho_Chi_Minh')->startOfDay()->addHours(8);
        $orders = [
            [
                'id' => 1,
                'nguoi_dung_id' => 2,
                'chuyen_di_id' => 1,
                'ma_don' => 'ORD20251101001',
                'ten_khach' => 'Nguyen Van A',
                'sdt_khach' => '0987654321',
                'email_khach' => 'kh1@example.com',
                'tong_tien' => 1250000.00,
                'trang_thai' => 'da_xac_nhan',
                'trang_thai_chuyen' => 'cho_khoi_hanh',
                'days_ago' => 0,
                'time' => [8, 15],
            ],
            [
                'id' => 2,
                'nguoi_dung_id' => 3,
                'chuyen_di_id' => 2,
                'ma_don' => 'ORD20251101002',
                'ten_khach' => 'Tran Thi B',
                'sdt_khach' => '0987654322',
                'email_khach' => 'kh2@example.com',
                'tong_tien' => 950000.00,
                'trang_thai' => 'cho_xu_ly',
                'trang_thai_chuyen' => 'cho_khoi_hanh',
                'days_ago' => 1,
                'time' => [9, 20],
            ],
            [
                'id' => 3,
                'nguoi_dung_id' => 2,
                'chuyen_di_id' => 3,
                'ma_don' => 'ORD20251102001',
                'ten_khach' => 'Nguyen Van A',
                'sdt_khach' => '0987654321',
                'email_khach' => 'kh1@example.com',
                'tong_tien' => 820000.00,
                'trang_thai' => 'hoan_tat',
                'trang_thai_chuyen' => 'da_den',
                'days_ago' => 2,
                'time' => [7, 45],
            ],
            [
                'id' => 4,
                'nguoi_dung_id' => 4,
                'chuyen_di_id' => 4,
                'ma_don' => 'ORD20251103001',
                'ten_khach' => 'Le Minh Chau',
                'sdt_khach' => '0911222333',
                'email_khach' => 'kh3@example.com',
                'tong_tien' => 1500000.00,
                'trang_thai' => 'da_huy',
                'trang_thai_chuyen' => 'huy',
                'days_ago' => 4,
                'time' => [10, 0],
            ],
            [
                'id' => 5,
                'nguoi_dung_id' => 5,
                'chuyen_di_id' => 5,
                'ma_don' => 'ORD20251103002',
                'ten_khach' => 'Pham Quang Duy',
                'sdt_khach' => '0909009900',
                'email_khach' => 'support@dkmn.com',
                'tong_tien' => 2100000.00,
                'trang_thai' => 'da_xac_nhan',
                'trang_thai_chuyen' => 'cho_khoi_hanh',
                'days_ago' => 6,
                'time' => [12, 40],
            ],
            [
                'id' => 6,
                'nguoi_dung_id' => 6,
                'chuyen_di_id' => 6,
                'ma_don' => 'ORD20251104001',
                'ten_khach' => 'Dang Thi Cam Nhung',
                'sdt_khach' => '0977333444',
                'email_khach' => 'kh4@example.com',
                'tong_tien' => 710000.00,
                'trang_thai' => 'hoan_tat',
                'trang_thai_chuyen' => 'da_den',
                'days_ago' => 8,
                'time' => [15, 5],
            ],
            [
                'id' => 7,
                'nguoi_dung_id' => 2,
                'chuyen_di_id' => 1,
                'ma_don' => 'ORD20251105001',
                'ten_khach' => 'Nguyen Van A',
                'sdt_khach' => '0987654321',
                'email_khach' => 'kh1@example.com',
                'tong_tien' => 980000.00,
                'trang_thai' => 'hoan_tat',
                'trang_thai_chuyen' => 'da_den',
                'days_ago' => 10,
                'time' => [9, 45],
            ],
            [
                'id' => 8,
                'nguoi_dung_id' => 3,
                'chuyen_di_id' => 2,
                'ma_don' => 'ORD20251105002',
                'ten_khach' => 'Tran Thi B',
                'sdt_khach' => '0987654322',
                'email_khach' => 'kh2@example.com',
                'tong_tien' => 890000.00,
                'trang_thai' => 'cho_xu_ly',
                'trang_thai_chuyen' => 'cho_khoi_hanh',
                'days_ago' => 12,
                'time' => [14, 30],
            ],
        ];

        foreach ($orders as &$order) {
            $date = $anchor->copy()->subDays($order['days_ago'])->setTime($order['time'][0], $order['time'][1]);
            $order['ngay_tao'] = $date;
            $order['ngay_cap_nhat'] = $date->copy()->addHours(match ($order['trang_thai']) {
                'da_huy' => 24,
                'hoan_tat' => 6,
                default => 3,
            });

            unset($order['days_ago'], $order['time']);
        }

        DB::table('don_hangs')->insert($orders);
    }
}
