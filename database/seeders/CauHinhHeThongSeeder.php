<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CauHinhHeThongSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('cau_hinh_he_thongs')->truncate();
        DB::table('cau_hinh_he_thongs')->insert([
            ['id'=>1,'khoa'=>'ten_site','gia_tri'=>'DKMN - He thong dat ve','mo_ta'=>'Ten website','ngay_tao'=>now(),'ngay_cap_nhat'=>now()],
            ['id'=>2,'khoa'=>'email_site','gia_tri'=>'info@dkmn.com','mo_ta'=>'Email lien he','ngay_tao'=>now(),'ngay_cap_nhat'=>now()],
            ['id'=>3,'khoa'=>'sdt_site','gia_tri'=>'19001234','mo_ta'=>'So dien thoai lien he','ngay_tao'=>now(),'ngay_cap_nhat'=>now()],
            ['id'=>4,'khoa'=>'momo_sdt','gia_tri'=>'0366818392','mo_ta'=>'So dien thoai MoMo','ngay_tao'=>now(),'ngay_cap_nhat'=>now()],
            ['id'=>5,'khoa'=>'ngan_hang_so_tk','gia_tri'=>'1037240068','mo_ta'=>'So tai khoan','ngay_tao'=>now(),'ngay_cap_nhat'=>now()],
            ['id'=>6,'khoa'=>'ngan_hang_ten','gia_tri'=>'Vietcombank','mo_ta'=>'Ten ngan hang','ngay_tao'=>now(),'ngay_cap_nhat'=>now()],
            ['id'=>7,'khoa'=>'so_ngay_dat_truoc','gia_tri'=>'30','mo_ta'=>'Toi da ngay co the dat truoc','ngay_tao'=>now(),'ngay_cap_nhat'=>now()],
            ['id'=>8,'khoa'=>'so_gio_toi_thieu','gia_tri'=>'2','mo_ta'=>'So gio toi thieu truoc gio khoi hanh','ngay_tao'=>now(),'ngay_cap_nhat'=>now()],
            ['id'=>9,'khoa'=>'ty_le_hoan_tien','gia_tri'=>'80','mo_ta'=>'% hoan tien khi huy','ngay_tao'=>now(),'ngay_cap_nhat'=>now()],
        ]);
    }
}


