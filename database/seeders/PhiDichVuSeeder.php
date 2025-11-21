<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PhiDichVuSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('phi_dich_vus')->delete();
        DB::table('phi_dich_vus')->insert([
            ['id'=>1,'ten'=>'Bao hiem du lich','loai'=>'phi_bao_hiem','gia_tri'=>50000.00,'loai_tinh'=>'co_dinh','ap_dung_cho'=>'tat_ca','trang_thai'=>'hoat_dong','ngay_tao'=>now(),'ngay_cap_nhat'=>now()],
            ['id'=>2,'ten'=>'Phi phuc vu','loai'=>'phi_phuc_vu','gia_tri'=>5.00,'loai_tinh'=>'phan_tram','ap_dung_cho'=>'tat_ca','trang_thai'=>'hoat_dong','ngay_tao'=>now(),'ngay_cap_nhat'=>now()],
            ['id'=>3,'ten'=>'Phi doi ve','loai'=>'phi_doi_ve','gia_tri'=>100000.00,'loai_tinh'=>'co_dinh','ap_dung_cho'=>'tat_ca','trang_thai'=>'hoat_dong','ngay_tao'=>now(),'ngay_cap_nhat'=>now()],
        ]);
    }
}

