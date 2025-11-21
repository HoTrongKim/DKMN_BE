<?php

namespace Tests\Concerns;

use App\Models\ChiTietDonHang;
use App\Models\ChuyenDi;
use App\Models\DonHang;
use App\Models\Ghe;
use App\Models\NhaVanHanh;
use App\Models\Ticket;
use App\Models\Tram;
use App\Models\TinhThanh;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

trait CreatesTicketData
{
    protected function createTicketWithSeats(array $prices): Ticket
    {
        $tinhThanh = TinhThanh::create(['ten' => 'Test', 'ma' => 'TT']);
        $tramDi = Tram::create(['ten' => 'Ga A', 'tinh_thanh_id' => $tinhThanh->id, 'loai' => 'ben_xe', 'dia_chi' => 'A']);
        $tramDen = Tram::create(['ten' => 'Ga B', 'tinh_thanh_id' => $tinhThanh->id, 'loai' => 'ga_tau', 'dia_chi' => 'B']);
        $operator = NhaVanHanh::create([
            'ten' => 'Operator',
            'loai' => 'xe_khach',
            'mo_ta' => '',
            'lien_he_dien_thoai' => '19000000',
            'lien_he_email' => 'ops@test.com',
            'trang_thai' => 'hoat_dong',
        ]);

        $trip = ChuyenDi::create([
            'nha_van_hanh_id' => $operator->id,
            'tram_di_id' => $tramDi->id,
            'tram_den_id' => $tramDen->id,
            'gio_khoi_hanh' => Carbon::now()->addDay(),
            'gio_den' => Carbon::now()->addDay()->addHours(5),
            'gia_co_ban' => 100000,
            'tong_ghe' => 50,
            'ghe_con' => 50,
            'trang_thai' => 'CON_VE',
        ]);

        $order = DonHang::create([
            'chuyen_di_id' => $trip->id,
            'ma_don' => 'ORD-' . Str::upper(Str::random(6)),
            'ten_khach' => 'Tester',
            'sdt_khach' => '0123456789',
            'email_khach' => 'test@example.com',
            'tong_tien' => array_sum($prices),
            'trang_thai' => 'cho_xu_ly',
            'trang_thai_chuyen' => 'cho_khoi_hanh',
        ]);

        $seatLabels = [];
        foreach ($prices as $index => $price) {
            $seat = Ghe::create([
                'chuyen_di_id' => $trip->id,
                'so_ghe' => 'B' . ($index + 1),
                'loai_ghe' => 'thuong',
                'gia' => $price,
                'trang_thai' => 'da_dat',
            ]);

            ChiTietDonHang::create([
                'don_hang_id' => $order->id,
                'ghe_id' => $seat->id,
                'ten_hanh_khach' => 'Tester',
                'sdt_hanh_khach' => '0123456789',
                'gia_ghe' => $price,
            ]);

            $seatLabels[] = $seat->so_ghe;
        }

        return Ticket::create([
            'don_hang_id' => $order->id,
            'trip_id' => $trip->id,
            'seat_numbers' => implode(',', $seatLabels),
            'base_fare_vnd' => array_sum($prices),
            'total_amount_vnd' => array_sum($prices),
            'status' => Ticket::STATUS_PENDING,
        ]);
    }
}
