<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class ChuyenDiSeeder extends Seeder
{
    private const MIN_TRIP_PRICE = 1000;
    private const MAX_TRIP_PRICE = 2000;
    private const PRICE_VARIANCE_STEP = 50;
    private const TRIP_START_DATE = '2025-11-01';
    private const TRIP_DATE_COUNT = 45; // cover 1/11 -> 15/12
    private const BUS_TIME_SLOTS = ['05:45', '13:30', '20:15'];
    private const PLANE_TIME_SLOTS = ['08:00', '15:00', '20:30'];
    private const TRAIN_TIME_SLOTS = ['06:10', '18:20'];
    private const INSERT_CHUNK_SIZE = 800; // keep under MySQL placeholder limit
    private const BUS_SEATS = 44;
    private const TRAIN_SEATS = 96;
    private const PLANE_SEATS = 186;

    private ?int $demoTripPriceOverride = null;
    private bool $demoTripPriceResolved = false;

    public function run(): void
    {
        Schema::disableForeignKeyConstraints();
        DB::table('chuyen_dis')->delete();
        Schema::enableForeignKeyConstraints();

        $operatorLookup = $this->buildLookupMap(
            DB::table('nha_van_hanhs')->get(['id', 'ten'])
        );

        $operatorPools = $this->groupOperatorsByType(
            DB::table('nha_van_hanhs')->get(['id', 'ten', 'loai'])
        );

        $stationCollection = DB::table('trams')->get(['id', 'ten', 'tinh_thanh_id', 'loai']);
        $stations = $this->buildStationLookup($stationCollection);
        $stationsByProvince = $this->groupStationsByProvince($stationCollection);
        $provinces = $this->buildProvinceNameMap();

        $busRoutes = $this->buildRoutes(
            'ben_xe',
            $stationsByProvince,
            $provinces,
            $operatorPools['xe_khach'] ?? [],
            240,
            1200,
            6,
            self::BUS_TIME_SLOTS,
            self::BUS_SEATS
        );

        $planeRoutes = $this->buildRoutes(
            'san_bay',
            $stationsByProvince,
            $provinces,
            $operatorPools['may_bay'] ?? [],
            90,
            1700,
            8,
            self::PLANE_TIME_SLOTS,
            self::PLANE_SEATS
        );

        $trainRoutes = $this->buildRoutes(
            'ga_tau',
            $stationsByProvince,
            $provinces,
            $operatorPools['tau_hoa'] ?? [],
            180,
            1300,
            4,
            self::TRAIN_TIME_SLOTS,
            self::TRAIN_SEATS
        );

        $busDates = $this->dateRange(self::TRIP_START_DATE, self::TRIP_DATE_COUNT);
        $planeDates = $this->dateRange(self::TRIP_START_DATE, self::TRIP_DATE_COUNT);
        $trainDates = $this->dateRange(self::TRIP_START_DATE, self::TRIP_DATE_COUNT);

        $nextId = 1;
        $this->chunkInsertTrips($this->generateTrips($busRoutes, $busDates, self::BUS_TIME_SLOTS, $operatorLookup, $stations, $nextId));
        $this->chunkInsertTrips($this->generateTrips($planeRoutes, $planeDates, self::PLANE_TIME_SLOTS, $operatorLookup, $stations, $nextId));
        $this->chunkInsertTrips($this->generateTrips($trainRoutes, $trainDates, self::TRAIN_TIME_SLOTS, $operatorLookup, $stations, $nextId));
    }

    private function generateTrips(
        array $routes,
        array $dates,
        array $timeSlots,
        array $operators,
        array $stations,
        int &$nextId
    ): array {
        $records = [];
        $timestamp = now();

        foreach ($routes as $route) {
            $operatorId = $this->lookupId($operators, $route['operator']);
            $fromStation = $this->lookupStation($stations, $route['from']);
            $toStation = $this->lookupStation($stations, $route['to']);

            if (!$operatorId || !$fromStation || !$toStation) {
                $this->warnMissingReference(
                    $route,
                    $operatorId,
                    $fromStation['id'] ?? null,
                    $toStation['id'] ?? null
                );
                continue;
            }

            $routeDates = $route['dates'] ?? $dates;
            $routeTimes = $route['time_slots'] ?? $timeSlots;
            $durationMinutes = $route['duration_minutes'] ?? 360;
            $seats = $route['seats'] ?? 40;
            $basePrice = $this->resolveBasePrice($route['price'] ?? null);
            $demoOverride = $this->getDemoTripPrice();
            if ($demoOverride !== null) {
                $basePrice = $demoOverride;
            }
            $variance = $route['price_variance'] ?? 0;

            foreach ($routeDates as $date) {
                foreach ($routeTimes as $time) {
                    $departure = Carbon::parse("{$date} {$time}");
                    $arrival = (clone $departure)->addMinutes($durationMinutes);

                    $records[] = [
                        'id' => $nextId++,
                        'nha_van_hanh_id' => $operatorId,
                        'tram_di_id' => $fromStation['id'],
                        'tram_den_id' => $toStation['id'],
                        'noi_di_tinh_thanh_id' => $fromStation['tinh_thanh_id'],
                        'noi_den_tinh_thanh_id' => $toStation['tinh_thanh_id'],
                        'gio_khoi_hanh' => $departure->toDateTimeString(),
                        'gio_den' => $arrival->toDateTimeString(),
                        'gia_co_ban' => $this->priceWithVariance($basePrice, $departure, $variance),
                        'tong_ghe' => $seats,
                        'ghe_con' => $seats,
                        'trang_thai' => $route['status'] ?? 'CON_VE',
                        'ngay_tao' => $timestamp,
                        'ngay_cap_nhat' => $timestamp,
                    ];
                }
            }
        }

        return $records;
    }

    private function buildRoutes(
        string $stationType,
        array $stationsByProvince,
        array $provinceNames,
        array $operatorNames,
        int $baseDurationMinutes,
        int $basePrice,
        int $priceVariance,
        array $timeSlots,
        int $defaultSeats
    ): array {
        if (empty($operatorNames)) {
            return [];
        }

        $routes = [];
        $provinceIds = array_keys($provinceNames);
        $count = count($provinceIds);

        for ($i = 0; $i < $count; $i++) {
            $fromId = $provinceIds[$i];
            $fromStation = $stationsByProvince[$fromId][$stationType][0] ?? null;
            if (!$fromStation) {
                continue;
            }

            for ($j = $i + 1; $j < $count; $j++) {
                $toId = $provinceIds[$j];
                $toStation = $stationsByProvince[$toId][$stationType][0] ?? null;
                if (!$toStation) {
                    continue;
                }

                $distanceFactor = 1 + (abs($fromId - $toId) / 10);
                $routeSeed = $fromId + $toId;

                $routes[] = $this->makeRoute(
                    $operatorNames,
                    $fromStation->ten,
                    $toStation->ten,
                    $defaultSeats,
                    $baseDurationMinutes,
                    $basePrice,
                    $distanceFactor,
                    $priceVariance,
                    $timeSlots,
                    $routeSeed
                );

                $routes[] = $this->makeRoute(
                    $operatorNames,
                    $toStation->ten,
                    $fromStation->ten,
                    $defaultSeats,
                    $baseDurationMinutes,
                    $basePrice,
                    $distanceFactor,
                    $priceVariance,
                    $timeSlots,
                    $routeSeed + 1
                );
            }
        }

        return $routes;
    }

    private function makeRoute(
        array $operators,
        string $from,
        string $to,
        int $seats,
        int $baseDurationMinutes,
        int $basePrice,
        float $distanceFactor,
        int $priceVariance,
        array $timeSlots,
        int $seed
    ): array {
        return [
            'operator' => $this->pickOperator($operators, $seed),
            'from' => $from,
            'to' => $to,
            'price' => (int) round($basePrice * $distanceFactor),
            'seats' => $seats,
            'duration_minutes' => max($baseDurationMinutes, (int) round($baseDurationMinutes * $distanceFactor)),
            'time_slots' => $timeSlots,
            'price_variance' => $priceVariance,
        ];
    }

    private function pickOperator(array $operators, int $seed): string
    {
        if (empty($operators)) {
            return '';
        }

        $index = $seed % count($operators);

        return $operators[$index];
    }

    private function groupOperatorsByType($items): array
    {
        $map = [];

        foreach ($items as $item) {
            if (!isset($item->loai, $item->ten)) {
                continue;
            }

            $map[$item->loai] ??= [];
            $map[$item->loai][] = $item->ten;
        }

        return $map;
    }

    private function groupStationsByProvince($items): array
    {
        $map = [];

        foreach ($items as $item) {
            if (!isset($item->id, $item->tinh_thanh_id, $item->loai, $item->ten)) {
                continue;
            }

            $map[$item->tinh_thanh_id][$item->loai][] = $item;
        }

        return $map;
    }

    private function buildProvinceNameMap(): array
    {
        $map = [];

        foreach (DB::table('tinh_thanhs')->get(['id', 'ten']) as $item) {
            $map[$item->id] = $item->ten;
        }

        return $map;
    }

    private function chunkInsertTrips(array $records): void
    {
        foreach (array_chunk($records, self::INSERT_CHUNK_SIZE) as $chunk) {
            DB::table('chuyen_dis')->insert($chunk);
        }
    }

    private function priceWithVariance(int $basePrice, Carbon $departure, int $varianceSteps): int
    {
        if ($varianceSteps <= 0) {
            return $basePrice;
        }

        $hash = crc32($departure->toDateTimeString());
        $offset = ($hash % (2 * $varianceSteps + 1)) - $varianceSteps;
        $price = $basePrice + ($offset * self::PRICE_VARIANCE_STEP);

        return max(self::MIN_TRIP_PRICE, min(self::MAX_TRIP_PRICE, $price));
    }

    private function dateRange(string $startDate, int $days): array
    {
        $dates = [];
        $start = Carbon::parse($startDate)->startOfDay();

        for ($i = 0; $i < $days; $i++) {
            $dates[] = $start->copy()->addDays($i)->toDateString();
        }

        return $dates;
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

    private function buildStationLookup($items): array
    {
        $map = [];

        foreach ($items as $item) {
            if (!isset($item->ten, $item->id)) {
                continue;
            }

            $map[$this->normalizeKey($item->ten)] = [
                'id' => $item->id,
                'tinh_thanh_id' => $item->tinh_thanh_id ?? null,
            ];
        }

        return $map;
    }

    private function lookupStation(array $map, string $name): ?array
    {
        $key = $this->normalizeKey($name);

        return $map[$key] ?? null;
    }

    private function lookupId(array $map, string $name): ?int
    {
        $key = $this->normalizeKey($name);

        return $map[$key] ?? null;
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

    private function warnMissingReference(array $trip, ?int $operatorId, ?int $fromId, ?int $toId): void
    {
        if (!$this->command) {
            return;
        }

        $missing = [];
        if (!$operatorId) {
            $missing[] = "operator: {$trip['operator']}";
        }
        if (!$fromId) {
            $missing[] = "from: {$trip['from']}";
        }
        if (!$toId) {
            $missing[] = "to: {$trip['to']}";
        }

        $this->command->warn('Skipped trip because of missing references -> ' . implode(', ', $missing));
    }

    private function getDemoTripPrice(): ?int
    {
        if ($this->demoTripPriceResolved) {
            return $this->demoTripPriceOverride;
        }

        $value = env('DEMO_TRIP_PRICE');
        if ($value === null || $value === '') {
            $this->demoTripPriceResolved = true;
            $this->demoTripPriceOverride = null;
            return null;
        }

        if (!is_numeric($value)) {
            $this->demoTripPriceResolved = true;
            $this->demoTripPriceOverride = null;
            return null;
        }

        $this->demoTripPriceOverride = $this->resolveBasePrice((int) $value);
        $this->demoTripPriceResolved = true;

        return $this->demoTripPriceOverride;
    }

    private function resolveBasePrice($price): int
    {
        if (is_numeric($price)) {
            $priceValue = (int) $price;
            return min(self::MAX_TRIP_PRICE, max(self::MIN_TRIP_PRICE, $priceValue));
        }

        return random_int(self::MIN_TRIP_PRICE, self::MAX_TRIP_PRICE);
    }
}
