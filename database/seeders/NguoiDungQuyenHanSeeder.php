<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class NguoiDungQuyenHanSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('nguoi_dung_quyen_hans')->truncate();
        DB::table('nguoi_dung_quyen_hans')->insert([
            ['id'=>1,'nguoi_dung_id'=>1,'quyen_han_id'=>1,'ngay_cap'=>now(),'ngay_het_han'=>null],
            ['id'=>2,'nguoi_dung_id'=>2,'quyen_han_id'=>2,'ngay_cap'=>now(),'ngay_het_han'=>null],
            ['id'=>3,'nguoi_dung_id'=>3,'quyen_han_id'=>3,'ngay_cap'=>now(),'ngay_het_han'=>null],
        ]);
    }
}


