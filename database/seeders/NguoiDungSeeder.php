<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class NguoiDungSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('nguoi_dungs')->delete();
        $password = Hash::make('123456');

        DB::table('nguoi_dungs')->insert([
            ['id'=>1,'ho_ten'=>'Quan tri DKMN','email'=>'admin@dkmn.com','so_dien_thoai'=>'0123456789','mat_khau'=>$password,'vai_tro'=>'quan_tri','trang_thai'=>'hoat_dong','ngay_tao'=>now(),'ngay_cap_nhat'=>now()],
            ['id'=>2,'ho_ten'=>'Nguyen Van A','email'=>'kh1@example.com','so_dien_thoai'=>'0987654321','mat_khau'=>$password,'vai_tro'=>'khach_hang','trang_thai'=>'hoat_dong','ngay_tao'=>now(),'ngay_cap_nhat'=>now()],
            ['id'=>3,'ho_ten'=>'Tran Thi B','email'=>'kh2@example.com','so_dien_thoai'=>'0987654322','mat_khau'=>$password,'vai_tro'=>'khach_hang','trang_thai'=>'hoat_dong','ngay_tao'=>now(),'ngay_cap_nhat'=>now()],
            ['id'=>4,'ho_ten'=>'Le Minh Chau','email'=>'kh3@example.com','so_dien_thoai'=>'0911222333','mat_khau'=>$password,'vai_tro'=>'khach_hang','trang_thai'=>'khoa','ngay_tao'=>now(),'ngay_cap_nhat'=>now()],
            ['id'=>5,'ho_ten'=>'Pham Quang Duy','email'=>'support@dkmn.com','so_dien_thoai'=>'0909009900','mat_khau'=>$password,'vai_tro'=>'quan_tri','trang_thai'=>'hoat_dong','ngay_tao'=>now(),'ngay_cap_nhat'=>now()],
            ['id'=>6,'ho_ten'=>'Dang Thi Cam Nhung','email'=>'kh4@example.com','so_dien_thoai'=>'0977333444','mat_khau'=>$password,'vai_tro'=>'khach_hang','trang_thai'=>'hoat_dong','ngay_tao'=>now(),'ngay_cap_nhat'=>now()],
        ]);
    }
}
