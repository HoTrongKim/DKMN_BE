<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RevenueStatisticBuilder
{
    /**
     * Rebuild daily revenue statistics derived from existing booking data.
     *
     * @param  Carbon|null  $from
     * @param  Carbon|null  $to
     * @return int number of rows written
     */
    public function rebuild(?Carbon $from = null, ?Carbon $to = null): int
    {
        $range = DB::table('don_hangs')
            ->selectRaw('MIN(DATE(ngay_tao)) as min_date, MAX(DATE(ngay_tao)) as max_date')
            ->first();

        if (!$range || !$range->min_date) {
            DB::table('thong_ke_doanh_thus')->truncate();
            return 0;
        }

        $startDate = $from?->toDateString() ?? $range->min_date;
        $endDate = $to?->toDateString() ?? $range->max_date;

        $orders = $this->collectOrders($startDate, $endDate);

        if ($orders->isEmpty()) {
            DB::table('thong_ke_doanh_thus')
                ->whereBetween('ngay', [$startDate, $endDate])
                ->delete();
            return 0;
        }

        $buckets = [];
        foreach ($orders as $order) {
            $this->accumulate($buckets, $order->ngay, $order->loai_phuong_tien, $order);
            $this->accumulate($buckets, $order->ngay, 'tat_ca', $order);
        }

        // Remove any old stats inside the rebuilt window to avoid duplicates.
        DB::table('thong_ke_doanh_thus')
            ->whereBetween('ngay', [$startDate, $endDate])
            ->delete();

        $now = Carbon::now();
        $rows = [];

        foreach ($buckets as $bucket) {
            $sold = max(0, $bucket['so_ve_ban']);
            $bucket['ty_le_huy'] = $sold > 0
                ? round(($bucket['so_ve_huy'] / $sold) * 100, 2)
                : 0;

            $bucket['tong_doanh_thu'] = round($bucket['tong_doanh_thu'], 2);
            $bucket['doanh_thu_thuc'] = round($bucket['doanh_thu_thuc'], 2);
            $bucket['ngay_tao'] = $now;

            $rows[] = $bucket;
        }

        if (!empty($rows)) {
            // Chunk insert to avoid huge single insert statements.
            collect($rows)->chunk(500)->each(function ($chunk) {
                DB::table('thong_ke_doanh_thus')->insert($chunk->toArray());
            });
        }

        return count($rows);
    }

    private function collectOrders(string $startDate, string $endDate)
    {
        $seatCounts = DB::table('chi_tiet_don_hangs')
            ->select('don_hang_id', DB::raw('COUNT(*) as seat_count'))
            ->groupBy('don_hang_id');

        $manualRevenue = DB::table('thanh_toans')
            ->select('don_hang_id', DB::raw("SUM(CASE WHEN trang_thai = 'thanh_cong' THEN so_tien ELSE 0 END) as manual_paid"))
            ->groupBy('don_hang_id');

        $query = DB::table('don_hangs as dh')
            ->join('chuyen_dis as cd', 'dh.chuyen_di_id', '=', 'cd.id')
            ->join('nha_van_hanhs as nvh', 'cd.nha_van_hanh_id', '=', 'nvh.id')
            ->leftJoinSub($seatCounts, 'seat_counts', 'seat_counts.don_hang_id', '=', 'dh.id')
            ->leftJoinSub($manualRevenue, 'manual_payments', 'manual_payments.don_hang_id', '=', 'dh.id');

        $onlinePaymentsAvailable = Schema::hasTable('tickets') && Schema::hasTable('payments');
        if ($onlinePaymentsAvailable) {
            $onlineRevenue = DB::table('tickets')
                ->join('payments', 'payments.ticket_id', '=', 'tickets.id')
                ->select(
                    'tickets.don_hang_id',
                    DB::raw("SUM(CASE WHEN payments.status = 'SUCCEEDED' THEN payments.amount_vnd ELSE 0 END) as online_paid")
                )
                ->groupBy('tickets.don_hang_id');

            $query->leftJoinSub($onlineRevenue, 'online_payments', 'online_payments.don_hang_id', '=', 'dh.id');
        }

        $selects = [
            'dh.id',
            DB::raw('DATE(dh.ngay_tao) as ngay'),
            'nvh.loai as loai_phuong_tien',
            'dh.trang_thai',
            'dh.tong_tien',
            DB::raw('COALESCE(seat_counts.seat_count, 1) as seat_count'),
            DB::raw('COALESCE(manual_payments.manual_paid, 0) as manual_paid'),
        ];

        $selects[] = $onlinePaymentsAvailable
            ? DB::raw('COALESCE(online_payments.online_paid, 0) as online_paid')
            : DB::raw('0 as online_paid');

        return $query
            ->select($selects)
            ->whereBetween(DB::raw('DATE(dh.ngay_tao)'), [$startDate, $endDate])
            ->orderBy('dh.ngay_tao')
            ->get();
    }

    private function accumulate(array &$buckets, string $date, string $type, object $order): void
    {
        $key = "{$date}|{$type}";

        if (!isset($buckets[$key])) {
            $buckets[$key] = [
                'ngay' => $date,
                'loai_phuong_tien' => $type,
                'so_don_hang' => 0,
                'tong_doanh_thu' => 0,
                'doanh_thu_thuc' => 0,
                'so_ve_ban' => 0,
                'so_ve_huy' => 0,
            ];
        }

        $seatCount = max(1, (int) ($order->seat_count ?? 1));
        $netRevenue = (float) ($order->manual_paid ?? 0) + (float) ($order->online_paid ?? 0);

        $buckets[$key]['so_don_hang'] += 1;
        $buckets[$key]['tong_doanh_thu'] += (float) $order->tong_tien;
        $buckets[$key]['doanh_thu_thuc'] += $netRevenue;
        $buckets[$key]['so_ve_ban'] += $seatCount;

        if ($order->trang_thai === 'da_huy') {
            $buckets[$key]['so_ve_huy'] += $seatCount;
        }
    }
}
