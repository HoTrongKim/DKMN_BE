<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class TramSeeder extends Seeder
{
    private array $busStations = [
        'Hà Nội' => ['Bến xe Giáp Bát', 'Bến xe Mỹ Đình', 'Bến xe Nước Ngầm', 'Bến xe Gia Lâm'],
        'Hải Phòng' => ['Bến xe Niệm Nghĩa', 'Bến xe Cầu Rào', 'Bến xe Thượng Lý'],
        'Quảng Ninh' => ['Bến xe Bãi Cháy', 'Bến xe Cẩm Phả', 'Bến xe Móng Cái'],
        'Bắc Ninh' => ['Bến xe Bắc Ninh', 'Bến xe Gia Bình'],
        'Hưng Yên' => ['Bến xe Hưng Yên', 'Bến xe Mỹ Hào'],
        'Hà Nam' => ['Bến xe Phủ Lý'],
        'Nam Định' => ['Bến xe Nam Định', 'Bến xe Mỹ Lộc'],
        'Ninh Bình' => ['Bến xe Ninh Bình', 'Bến xe Kim Sơn'],
        'Thanh Hóa' => ['Bến xe Thanh Hóa', 'Bến xe phía Bắc Thanh Hóa'],
        'Nghệ An' => ['Bến xe Vinh', 'Bến xe phía Bắc Vinh'],
        'Hà Tĩnh' => ['Bến xe Hà Tĩnh', 'Bến xe Cẩm Xuyên'],
        'Quảng Bình' => ['Bến xe Đồng Hới', 'Bến xe Ba Đồn'],
        'Quảng Trị' => ['Bến xe Đông Hà', 'Bến xe Quảng Trị'],
        'Thừa Thiên Huế' => ['Bến xe Huế', 'Bến xe phía Nam Huế'],
        'Đà Nẵng' => ['Bến xe Trung tâm', 'Bến xe phía Bắc'],
        'Quảng Nam' => ['Bến xe Tam Kỳ', 'Bến xe Hội An'],
        'Quảng Ngãi' => ['Bến xe Quảng Ngãi', 'Bến xe Đức Phổ'],
        'Bình Định' => ['Bến xe Quy Nhơn', 'Bến xe Phù Cát'],
        'Phú Yên' => ['Bến xe Tuy Hòa', 'Bến xe Sông Cầu'],
        'Khánh Hòa' => ['Bến xe Nha Trang', 'Bến xe Ninh Hòa', 'Bến xe Cam Ranh'],
        'Ninh Thuận' => ['Bến xe Phan Rang', 'Bến xe Ninh Sơn'],
        'Bình Thuận' => ['Bến xe Phan Thiết', 'Bến xe Bắc Bình'],
        'Kon Tum' => ['Bến xe Kon Tum', 'Bến xe Đắk Tô'],
        'Gia Lai' => ['Bến xe Pleiku', 'Bến xe An Khê'],
        'Đắk Lắk' => ['Bến xe Buôn Ma Thuột', 'Bến xe Ea Kar'],
        'Đắk Nông' => ['Bến xe Gia Nghĩa', 'Bến xe Đắk Mil'],
        'Lâm Đồng' => ['Bến xe Đà Lạt', 'Bến xe Bảo Lộc'],
        'Bình Phước' => ['Bến xe Đồng Xoài', 'Bến xe Chơn Thành'],
        'Bình Dương' => ['Bến xe Bình Dương', 'Bến xe Dĩ An'],
        'Đồng Nai' => ['Bến xe Biên Hòa', 'Bến xe Long Khánh'],
        'Bà Rịa - Vũng Tàu' => ['Bến xe Vũng Tàu', 'Bến xe Bà Rịa'],
        'TP. Hồ Chí Minh' => ['Bến xe Miền Đông', 'Bến xe Miền Đông Mới', 'Bến xe Miền Tây', 'Bến xe An Sương'],
        'Long An' => ['Bến xe Tân An', 'Bến xe Bến Lức'],
        'Cần Thơ' => ['Bến xe Cần Thơ', 'Bến xe Ô Môn'],
    ];

    private array $trainStations = [
        'Hà Nội' => ['Ga Hà Nội', 'Ga Long Biên'],
        'Hải Phòng' => ['Ga Hải Phòng'],
        'Quảng Ninh' => ['Ga Hạ Long', 'Ga Cái Lân'],
        'Bắc Ninh' => ['Ga Bắc Ninh'],
        'Hưng Yên' => ['Ga Phố Nối'],
        'Hà Nam' => ['Ga Phủ Lý'],
        'Nam Định' => ['Ga Nam Định'],
        'Ninh Bình' => ['Ga Ninh Bình'],
        'Thanh Hóa' => ['Ga Thanh Hóa'],
        'Nghệ An' => ['Ga Vinh'],
        'Hà Tĩnh' => ['Ga Yên Trung'],
        'Quảng Bình' => ['Ga Đồng Hới'],
        'Quảng Trị' => ['Ga Đông Hà'],
        'Thừa Thiên Huế' => ['Ga Huế'],
        'Đà Nẵng' => ['Ga Đà Nẵng'],
        'Quảng Nam' => ['Ga Tam Kỳ'],
        'Quảng Ngãi' => ['Ga Quảng Ngãi'],
        'Bình Định' => ['Ga Diêu Trì', 'Ga Quy Nhơn'],
        'Phú Yên' => ['Ga Tuy Hòa'],
        'Khánh Hòa' => ['Ga Nha Trang'],
        'Ninh Thuận' => ['Ga Tháp Chàm'],
        'Bình Thuận' => ['Ga Phan Thiết', 'Ga Mương Mán'],
        'Kon Tum' => ['Ga Ngọc Hồi'],
        'Gia Lai' => ['Ga Mang Yang'],
        'Đắk Lắk' => ['Ga Buôn Hồ'],
        'Đắk Nông' => ['Ga Đức Lập'],
        'Lâm Đồng' => ['Ga Trại Mát'],
        'Bình Phước' => ['Ga Đồng Xoài'],
        'Bình Dương' => ['Ga Dĩ An', 'Ga Sóng Thần'],
        'Đồng Nai' => ['Ga Biên Hòa', 'Ga Long Khánh'],
        'Bà Rịa - Vũng Tàu' => ['Ga Phú Mỹ'],
        'TP. Hồ Chí Minh' => ['Ga Sài Gòn'],
        'Long An' => ['Ga Tân An'],
        'Cần Thơ' => ['Ga Cái Răng'],
    ];

    private array $airports = [
        'Ha Noi' => ['San bay Noi Bai (HAN)'],
        'Hai Phong' => ['San bay Cat Bi (HPH)'],
        'Quang Ninh' => ['San bay Van Don (VDO)'],
        'Bac Ninh' => ['San bay Noi Bai (HAN)'],
        'Hung Yen' => ['San bay Noi Bai (HAN)'],
        'Ha Nam' => ['San bay Noi Bai (HAN)'],
        'Nam Dinh' => ['San bay Noi Bai (HAN)'],
        'Ninh Binh' => ['San bay Noi Bai (HAN)'],
        'Thanh Hoa' => ['San bay Tho Xuan (THD)'],
        'Nghe An' => ['San bay Vinh (VII)'],
        'Ha Tinh' => ['San bay Vinh (VII)'],
        'Quang Binh' => ['San bay Dong Hoi (VDH)'],
        'Quang Tri' => ['San bay Phu Bai (HUI)'],
        'Thua Thien Hue' => ['San bay Phu Bai (HUI)'],
        'Da Nang' => ['San bay Da Nang (DAD)'],
        'Quang Nam' => ['San bay Chu Lai (VCL)'],
        'Quang Ngai' => ['San bay Chu Lai (VCL)'],
        'Binh Dinh' => ['San bay Phu Cat (UIH)'],
        'Phu Yen' => ['San bay Tuy Hoa (TBB)'],
        'Khanh Hoa' => ['San bay Cam Ranh (CXR)'],
        'Ninh Thuan' => ['San bay Cam Ranh (CXR)'],
        'Binh Thuan' => ['San bay Cam Ranh (CXR)'],
        'Kon Tum' => ['San bay Pleiku (PXU)'],
        'Gia Lai' => ['San bay Pleiku (PXU)'],
        'Dak Lak' => ['San bay Buon Ma Thuot (BMV)'],
        'Dak Nong' => ['San bay Buon Ma Thuot (BMV)'],
        'Lam Dong' => ['San bay Lien Khuong (DLI)'],
        'TP. Ho Chi Minh' => ['San bay Tan Son Nhat (SGN)'],
        'Binh Phuoc' => ['San bay Tan Son Nhat (SGN)'],
        'Binh Duong' => ['San bay Tan Son Nhat (SGN)'],
        'Dong Nai' => ['San bay Long Thanh (LTN)'],
        'Ba Ria - Vung Tau' => ['San bay Tan Son Nhat (SGN)'],
        'Long An' => ['San bay Tan Son Nhat (SGN)'],
        'Can Tho' => ['San bay Can Tho (VCA)'],
    ];

    public function run(): void
    {
        Schema::disableForeignKeyConstraints();
        DB::table('trams')->delete();
        Schema::enableForeignKeyConstraints();

        $now = now();
        $cityMap = $this->buildLookupMap(
            DB::table('tinh_thanhs')->get(['id', 'ten'])
        );
        $records = [];
        $existingNames = [];

        $this->appendStations($records, $existingNames, $cityMap, $this->busStations, 'ben_xe', $now);
        $this->appendStations($records, $existingNames, $cityMap, $this->trainStations, 'ga_tau', $now);
        $this->appendStations($records, $existingNames, $cityMap, $this->airports, 'san_bay', $now);

        DB::table('trams')->insert($records);
        $this->correctAirportMappings($cityMap);
    }

    private function appendStations(array &$records, array &$existingNames, array $cityMap, array $stations, string $type, $timestamp): void
    {
        foreach ($stations as $city => $list) {
            $cityId = $cityMap[$this->normalizeKey($city)] ?? null;
            if (!$cityId) {
                continue;
            }

            foreach ($list as $station) {
                $stationName = trim($station);
                if ($stationName === '' || isset($existingNames[$stationName])) {
                    continue;
                }

                $existingNames[$stationName] = true;
                $records[] = [
                    'ten' => $stationName,
                    'tinh_thanh_id' => $cityId,
                    'loai' => $type,
                    'dia_chi' => $city,
                    'ngay_tao' => $timestamp,
                ];
            }
        }
    }

    private function buildLookupMap($items): array
    {
        $map = [];

        foreach ($items as $item) {
            if (!isset($item->ten, $item->id)) {
                continue;
            }

            $map[$this->normalizeKey($item->ten)] = $item->id;
        }

        return $map;
    }

    private function normalizeKey(string $value): string
    {
        return Str::of($value)
            ->ascii()
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/u', ' ')
            ->squish()
            ->value();
    }

    private function correctAirportMappings(array $cityMap): void
    {
        $overrides = [
            'Sân bay Tân Sơn Nhất (SGN)' => 'TP. Hồ Chí Minh',
            'Sân bay Long Thành (LTN)' => 'Đồng Nai',
        ];

        foreach ($overrides as $station => $cityName) {
            $cityId = $cityMap[$this->normalizeKey($cityName)] ?? null;
            if (!$cityId) {
                continue;
            }

            DB::table('trams')
                ->where('ten', $station)
                ->update([
                    'tinh_thanh_id' => $cityId,
                    'dia_chi' => $cityName,
                ]);
        }
    }
}
