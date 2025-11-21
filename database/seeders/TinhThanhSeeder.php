<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TinhThanhSeeder extends Seeder
{
    public function run(): void
    {
        $cities = [
            ['ten' => "H\u{00e0} N\u{1ed9}i", 'ma' => 'HN'],
            ['ten' => "H\u{1ea3}i Ph\u{00f2}ng", 'ma' => 'HP'],
            ['ten' => "Qu\u{1ea3}ng Ninh", 'ma' => 'QN'],
            ['ten' => "B\u{1eaf}c Ninh", 'ma' => 'BN'],
            ['ten' => "H\u{01b0}ng Y\u{00ean}", 'ma' => 'HY'],
            ['ten' => "H\u{00e0} Nam", 'ma' => 'HNA'],
            ['ten' => "Nam \u{0110}\u{1ecb}nh", 'ma' => 'ND'],
            ['ten' => "Ninh B\u{00ec}nh", 'ma' => 'NB'],
            ['ten' => "Thanh H\u{00f3}a", 'ma' => 'TH'],
            ['ten' => "Ngh\u{1ec7} An", 'ma' => 'NA'],
            ['ten' => "H\u{00e0} T\u{0129}nh", 'ma' => 'HT'],
            ['ten' => "Qu\u{1ea3}ng B\u{00ec}nh", 'ma' => 'QB'],
            ['ten' => "Qu\u{1ea3}ng Tr\u{1ecb}", 'ma' => 'QT'],
            ['ten' => "Th\u{1eeda} Thi\u{00ean} Hu\u{1ebf}", 'ma' => 'TTH'],
            ['ten' => "\u{0110}\u{00e0} N\u{1eb5}ng", 'ma' => 'DN'],
            ['ten' => "Qu\u{1ea3}ng Nam", 'ma' => 'QNA'],
            ['ten' => "Qu\u{1ea3}ng Ng\u{00e3}i", 'ma' => 'QNG'],
            ['ten' => "B\u{00ec}nh \u{0110}\u{1ecb}nh", 'ma' => 'BDI'],
            ['ten' => "Ph\u{00fa} Y\u{00ean}", 'ma' => 'PY'],
            ['ten' => "Kh\u{00e1}nh H\u{00f2}a", 'ma' => 'KH'],
            ['ten' => "Ninh Thu\u{1ead}n", 'ma' => 'NT'],
            ['ten' => "B\u{00ec}nh Thu\u{1ead}n", 'ma' => 'BT'],
            ['ten' => 'Kon Tum', 'ma' => 'KT'],
            ['ten' => 'Gia Lai', 'ma' => 'GL'],
            ['ten' => "\u{0110}\u{1eaf}k L\u{1eaf}k", 'ma' => 'DLK'],
            ['ten' => "\u{0110}\u{1eaf}k N\u{00f4}ng", 'ma' => 'DNO'],
            ['ten' => "L\u{00e2}m \u{0110}\u{1ed3}ng", 'ma' => 'LD'],
            ['ten' => "B\u{00ec}nh Ph\u{01b0}\u{1edb}c", 'ma' => 'BP'],
            ['ten' => "B\u{00ec}nh D\u{01b0}\u{1a1}ng", 'ma' => 'BD'],
            ['ten' => "\u{0110}\u{1ed3}ng Nai", 'ma' => 'DNA'],
            ['ten' => "B\u{00e0} R\u{1ecba} - V\u{169}ng T\u{00e0}u", 'ma' => 'BRVT'],
            ['ten' => "TP. H\u{1ed3} Ch\u{00ed} Minh", 'ma' => 'HCM'],
            ['ten' => 'Long An', 'ma' => 'LA'],
            ['ten' => "C\u{1ea7}n Th\u{01a1}", 'ma' => 'CT'],
        ];

        DB::table('tinh_thanhs')->delete();

        $now = now();
        $payload = [];
        foreach ($cities as $index => $city) {
            $payload[] = [
                'id' => $index + 1,
                'ten' => $city['ten'],
                'ma' => $city['ma'],
                'ngay_tao' => $now,
            ];
        }

        DB::table('tinh_thanhs')->insert($payload);
    }
}
