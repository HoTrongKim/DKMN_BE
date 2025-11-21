<?php

namespace Database\Seeders;

use App\Services\RevenueStatisticBuilder;
use Illuminate\Database\Seeder;

class ThongKeDoanhThuSeeder extends Seeder
{
    public function run(): void
    {
        app(RevenueStatisticBuilder::class)->rebuild();
    }
}
