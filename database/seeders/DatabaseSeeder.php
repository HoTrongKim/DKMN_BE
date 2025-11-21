<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        Schema::disableForeignKeyConstraints();
        $this->prepareTables();

        $this->call([
            TinhThanhSeeder::class,
            TramSeeder::class,
            NhaVanHanhSeeder::class,
            NguoiDungSeeder::class,
            QuyenHanSeeder::class,
            NguoiDungQuyenHanSeeder::class,
            ChuyenDiSeeder::class,
            GheSeeder::class,
            DonHangSeeder::class,
            ChiTietDonHangSeeder::class,
            PhiDichVuSeeder::class,
            ChiTietPhiDonHangSeeder::class,
            TicketSeeder::class,
            ThanhToanSeeder::class,
            PaymentSeeder::class,
            CauHinhHeThongSeeder::class,
            ThongKeDoanhThuSeeder::class,
        ]);

        Schema::enableForeignKeyConstraints();
    }

    private function prepareTables(): void
    {
        $truncateTables = [
            'payments',
            'tickets',
            'chi_tiet_phi_don_hangs',
            'chi_tiet_don_hangs',
            'thanh_toans',
            'huy_ves',
            'danh_gias',
            'phan_hois',
            'thong_baos',
            'nhat_ky_hoat_dongs',
            'nguoi_dung_quyen_hans',
        ];

        foreach ($truncateTables as $table) {
            if (Schema::hasTable($table)) {
                DB::table($table)->truncate();
            }
        }

        $deleteTables = [
            'don_hangs',
            'ghes',
            'phi_dich_vus',
            'chuyen_dis',
            'nguoi_dungs',
            'quyen_hans',
            'nha_van_hanhs',
            'trams',
            'tinh_thanhs',
        ];

        foreach ($deleteTables as $table) {
            if (Schema::hasTable($table)) {
                DB::table($table)->delete();
            }
        }

        $finalTruncate = [
            'thong_ke_doanh_thus',
            'cau_hinh_he_thongs',
        ];

        foreach ($finalTruncate as $table) {
            if (Schema::hasTable($table)) {
                DB::table($table)->truncate();
            }
        }
    }
}
