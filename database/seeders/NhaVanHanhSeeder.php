<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class NhaVanHanhSeeder extends Seeder
{
    public function run(): void
    {
        $timestamp = now();

        $records = [
            ['id'=>1,'ten'=>'Phuong Trang (Futa)','loai'=>'xe_khach','mo_ta'=>'Nha xe tuyen truong','lien_he_dien_thoai'=>'19006067','lien_he_email'=>'support@futa.vn','trang_thai'=>'hoat_dong','ngay_tao'=>$timestamp],
            ['id'=>2,'ten'=>'Thanh Buoi','loai'=>'xe_khach','mo_ta'=>'Dich vu chat luong','lien_he_dien_thoai'=>'19006079','lien_he_email'=>'cs@thanhbuoi.com','trang_thai'=>'hoat_dong','ngay_tao'=>$timestamp],
            ['id'=>3,'ten'=>'Mai Linh','loai'=>'xe_khach','mo_ta'=>'Xe khach chat luong','lien_he_dien_thoai'=>'19006060','lien_he_email'=>'bus@mailinh.vn','trang_thai'=>'hoat_dong','ngay_tao'=>$timestamp],
            ['id'=>4,'ten'=>'Kumho Samco','loai'=>'xe_khach','mo_ta'=>'Tuyen mien Dong','lien_he_dien_thoai'=>'19006686','lien_he_email'=>'care@kumhosamco.vn','trang_thai'=>'hoat_dong','ngay_tao'=>$timestamp],
            ['id'=>5,'ten'=>'Hoang Long','loai'=>'xe_khach','mo_ta'=>'Bac Nam','lien_he_dien_thoai'=>'19006726','lien_he_email'=>'hl@hoanglong.vn','trang_thai'=>'hoat_dong','ngay_tao'=>$timestamp],
            ['id'=>6,'ten'=>'Duong sat Viet Nam','loai'=>'tau_hoa','mo_ta'=>'VNR','lien_he_dien_thoai'=>'19006469','lien_he_email'=>'info@vr.com.vn','trang_thai'=>'hoat_dong','ngay_tao'=>$timestamp],
            ['id'=>7,'ten'=>'SE1/SE2','loai'=>'tau_hoa','mo_ta'=>'Tau toc hanh Bac Nam','lien_he_dien_thoai'=>'19006469','lien_he_email'=>'se12@vr.com.vn','trang_thai'=>'hoat_dong','ngay_tao'=>$timestamp],
            ['id'=>8,'ten'=>'SE3/SE4','loai'=>'tau_hoa','mo_ta'=>'Tau Bac Nam','lien_he_dien_thoai'=>'19006469','lien_he_email'=>'se34@vr.com.vn','trang_thai'=>'hoat_dong','ngay_tao'=>$timestamp],
            ['id'=>9,'ten'=>'Vietnam Airlines','loai'=>'may_bay','mo_ta'=>'Hang hang khong quoc gia','lien_he_dien_thoai'=>'19001100','lien_he_email'=>'contact@vietnamairlines.com','trang_thai'=>'hoat_dong','ngay_tao'=>$timestamp],
            ['id'=>10,'ten'=>'Vietjet Air','loai'=>'may_bay','mo_ta'=>'Hang hang khong gia re','lien_he_dien_thoai'=>'19001886','lien_he_email'=>'support@vietjetair.com','trang_thai'=>'hoat_dong','ngay_tao'=>$timestamp],
            ['id'=>11,'ten'=>'Bamboo Airways','loai'=>'may_bay','mo_ta'=>'Hang hang khong tu nhan','lien_he_dien_thoai'=>'19001166','lien_he_email'=>'info@bambooairways.com','trang_thai'=>'hoat_dong','ngay_tao'=>$timestamp],
            ['id'=>12,'ten'=>'Vietravel Airlines','loai'=>'may_bay','mo_ta'=>'Hang hang khong du lich','lien_he_dien_thoai'=>'19006686','lien_he_email'=>'support@vietravelairlines.com','trang_thai'=>'hoat_dong','ngay_tao'=>$timestamp],
            ['id'=>13,'ten'=>'Pacific Airlines','loai'=>'may_bay','mo_ta'=>'Lien ket VNA','lien_he_dien_thoai'=>'19001550','lien_he_email'=>'support@pacificairlines.com','trang_thai'=>'hoat_dong','ngay_tao'=>$timestamp],
            ['id'=>14,'ten'=>'VASCO','loai'=>'may_bay','mo_ta'=>'Vietnam Air Services','lien_he_dien_thoai'=>'19001100','lien_he_email'=>'info@vasco.com.vn','trang_thai'=>'hoat_dong','ngay_tao'=>$timestamp],
        ];

        DB::table('nha_van_hanhs')->upsert(
            $records,
            ['id'],
            ['ten','loai','mo_ta','lien_he_dien_thoai','lien_he_email','trang_thai']
        );
    }
}

