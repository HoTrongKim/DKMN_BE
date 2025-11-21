<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class QuyenHanSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('quyen_hans')->delete();
        DB::table('quyen_hans')->insert([
            ['id'=>1,'ten'=>'Quan tri vien','mo_ta'=>'Toan quyen he thong','danh_sach_quyen'=>'["quan_ly_nguoi_dung","quan_ly_chuyen_di","quan_ly_don_hang","quan_ly_thanh_toan","xem_bao_cao","cau_hinh_he_thong"]','trang_thai'=>'hoat_dong','ngay_tao'=>now()],
            ['id'=>2,'ten'=>'Nhan vien ban ve','mo_ta'=>'Quan ly don & thanh toan','danh_sach_quyen'=>'["quan_ly_don_hang","quan_ly_thanh_toan","xem_bao_cao"]','trang_thai'=>'hoat_dong','ngay_tao'=>now()],
            ['id'=>3,'ten'=>'Nhan vien ho tro','mo_ta'=>'Ho tro khach hang','danh_sach_quyen'=>'["xem_don_hang","quan_ly_phan_hoi","xem_nguoi_dung"]','trang_thai'=>'hoat_dong','ngay_tao'=>now()],
        ]);
    }
}

