<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ChuyenDi;
use App\Models\DanhGia;
use App\Models\DonHang;
use App\Models\NguoiDung;
use App\Models\Payment;
use App\Models\ThanhToan;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class DashboardAdminController extends Controller
{
    public function overview()
    {
        $today = Carbon::today();
        $yesterday = Carbon::yesterday();
        $totalTrips = ChuyenDi::count();
        $totalOrders = DonHang::count();
        $totalCustomers = NguoiDung::where('vai_tro', 'khach_hang')->count();

        $hasPayments = $this->hasPaymentsTable();
        $manualRevenue = ThanhToan::where('trang_thai', 'thanh_cong')->sum('so_tien');
        $onlineRevenue = $hasPayments
            ? Payment::where('status', Payment::STATUS_SUCCEEDED)->sum('amount_vnd')
            : 0;
        $ticketsToday = DonHang::whereDate('ngay_tao', $today)->count();
        $ticketsYesterday = DonHang::whereDate('ngay_tao', $yesterday)->count();
        $revenueToday = $this->dailyRevenue($today, $hasPayments);
        $revenueYesterday = $this->dailyRevenue($yesterday, $hasPayments);
        $newCustomers = NguoiDung::where('vai_tro', 'khach_hang')
            ->where('ngay_tao', '>=', Carbon::now()->subDays(7))
            ->count();
        $ratingScore = (float) round(
            DanhGia::where('trang_thai', 'chap_nhan')->avg('diem') ?? 0,
            1
        );

        $recentOrders = DonHang::query()
            ->with('nguoiDung')
            ->orderByDesc('ngay_tao')
            ->limit(5)
            ->get()
            ->map(function (DonHang $order) {
                return [
                    'id' => $order->id,
                    'code' => $order->ma_don,
                    'customer' => $order->nguoiDung?->ho_ten ?? $order->ten_khach,
                    'total' => (float) $order->tong_tien,
                    'status' => $order->trang_thai,
                    'createdAt' => optional($order->ngay_tao)->toIso8601String(),
                ];
            });

        $topRoutes = DonHang::query()
            ->selectRaw('CONCAT(COALESCE(noi_di, "Unknown"), " → ", COALESCE(noi_den, "Unknown")) as route, COUNT(*) as total')
            ->groupBy('route')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        $monthlyRevenue = $this->monthlyRevenue($hasPayments);

        return response()->json([
            'status' => true,
            'data' => [
                'summary' => [
                    'ticketsToday' => $ticketsToday,
                    'revenueToday' => (float) $revenueToday,
                    'newCustomers' => $newCustomers,
                    'ratingScore' => $ratingScore,
                    'ticketsTodayDelta' => $ticketsToday - $ticketsYesterday,
                    'revenueTodayDelta' => (float) ($revenueToday - $revenueYesterday),
                    'ratingBase' => 5,
                ],
                'counters' => [
                    'trips' => $totalTrips,
                    'orders' => $totalOrders,
                    'customers' => $totalCustomers,
                    'revenue' => (float) ($manualRevenue + $onlineRevenue),
                ],
                'recentOrders' => $recentOrders,
                'topRoutes' => $topRoutes,
                'monthlyRevenue' => $monthlyRevenue,
            ],
        ]);
    }

    private function monthlyRevenue(bool $hasPaymentsTable): array
    {
        $from = Carbon::now()->subMonths(5)->startOfMonth();
        $manual = ThanhToan::query()
            ->selectRaw('DATE_FORMAT(thoi_diem_thanh_toan, "%Y-%m") as month, SUM(so_tien) as total')
            ->where('trang_thai', 'thanh_cong')
            ->where('thoi_diem_thanh_toan', '>=', $from)
            ->groupBy('month')
            ->pluck('total', 'month')
            ->toArray();

        $online = $hasPaymentsTable
            ? Payment::query()
                ->selectRaw('DATE_FORMAT(paid_at, "%Y-%m") as month, SUM(amount_vnd) as total')
                ->where('status', Payment::STATUS_SUCCEEDED)
                ->where('paid_at', '>=', $from)
                ->groupBy('month')
                ->pluck('total', 'month')
                ->toArray()
            : [];

        $months = [];
        for ($i = 0; $i < 6; $i++) {
            $key = $from->copy()->addMonths($i)->format('Y-m');
            $months[$key] = ($manual[$key] ?? 0) + ($online[$key] ?? 0);
        }

        return collect($months)->map(fn ($value, $month) => [
            'month' => $month,
            'total' => (float) $value,
        ])->values()->toArray();
    }

    public function report(Request $request)
    {
        $validated = $request->validate([
            'period' => ['nullable', Rule::in(['week', 'month', 'quarter', 'year'])],
        ]);

        $period = $validated['period'] ?? 'month';
        [$start, $end] = $this->resolvePeriodRange($period);

        $ordersInRange = DonHang::query()->whereBetween('ngay_tao', [$start, $end]);
        $totalOrders = (clone $ordersInRange)->count();
        $totalTickets = (clone $ordersInRange)->sum(DB::raw('COALESCE(so_hanh_khach, 1)'));
        $cancelledOrders = (clone $ordersInRange)->where('trang_thai', 'da_huy')->count();

        $hasPayments = $this->hasPaymentsTable();

        $manualRevenue = ThanhToan::query()
            ->where('trang_thai', 'thanh_cong')
            ->whereBetween('thoi_diem_thanh_toan', [$start, $end])
            ->sum('so_tien');

        $onlineRevenue = $hasPayments
            ? Payment::query()
                ->where('status', Payment::STATUS_SUCCEEDED)
                ->whereBetween('paid_at', [$start, $end])
                ->sum('amount_vnd')
            : 0;

        $ratingQuery = DanhGia::query()
            ->where('trang_thai', 'chap_nhan')
            ->whereBetween('ngay_tao', [$start, $end]);

        $averageRating = round((float) ((clone $ratingQuery)->avg('diem') ?? 0), 1);
        $totalReviews = (clone $ratingQuery)->count();

        $topRoutes = DonHang::query()
            ->selectRaw('COALESCE(noi_di, "Khong ro") as from_location, COALESCE(noi_den, "Khong ro") as to_location, COUNT(*) as total')
            ->whereBetween('ngay_tao', [$start, $end])
            ->groupBy('from_location', 'to_location')
            ->orderByDesc('total')
            ->limit(5)
            ->get()
            ->map(fn ($row) => [
                'name' => trim(($row->from_location ?? 'Khong ro') . ' -> ' . ($row->to_location ?? 'Khong ro')),
                'tickets' => (int) $row->total,
            ]);

        $topCompanies = DanhGia::query()
            ->selectRaw('nha_van_hanhs.ten as name, AVG(danh_gias.diem) as avg_rating, COUNT(*) as reviews')
            ->join('chuyen_dis', 'danh_gias.chuyen_di_id', '=', 'chuyen_dis.id')
            ->join('nha_van_hanhs', 'chuyen_dis.nha_van_hanh_id', '=', 'nha_van_hanhs.id')
            ->where('danh_gias.trang_thai', 'chap_nhan')
            ->whereBetween('danh_gias.ngay_tao', [$start, $end])
            ->groupBy('nha_van_hanhs.id', 'nha_van_hanhs.ten')
            ->orderByDesc('avg_rating')
            ->limit(5)
            ->get()
            ->map(fn ($row) => [
                'name' => $row->name,
                'rating' => (float) round($row->avg_rating ?? 0, 2),
                'reviews' => (int) $row->reviews,
            ]);

        $totalRevenue = (float) ($manualRevenue + $onlineRevenue);
        $cancellationRate = $totalOrders > 0
            ? round(($cancelledOrders / $totalOrders) * 100, 2)
            : 0.0;

        return response()->json([
            'status' => true,
            'data' => [
                'period' => $period,
                'range' => [
                    'from' => $start->toDateString(),
                    'to' => $end->toDateString(),
                ],
                'totalRevenue' => $totalRevenue,
                'totalTickets' => (int) $totalTickets,
                'cancellationRate' => $cancellationRate,
                'averageRating' => $averageRating,
                'totalReviews' => (int) $totalReviews,
                'topRoutes' => $topRoutes,
                'topCompanies' => $topCompanies,
            ],
        ]);
    }

    protected function hasPaymentsTable(): bool
    {
        static $cache = null;

        if ($cache !== null) {
            return $cache;
        }

        try {
            $cache = Schema::hasTable('payments')
                && Schema::hasColumns('payments', ['status', 'amount_vnd', 'paid_at']);
        } catch (\Throwable $exception) {
            report($exception);
            $cache = false;
        }

        return $cache;
    }

    private function resolvePeriodRange(string $period): array
    {
        $end = Carbon::now()->endOfDay();

        $start = match ($period) {
            'week' => $end->copy()->subDays(6)->startOfDay(),
            'quarter' => $end->copy()->firstOfQuarter()->startOfDay(),
            'year' => $end->copy()->startOfYear()->startOfDay(),
            default => $end->copy()->startOfMonth()->startOfDay(),
        };

        return [$start, $end];
    }

    private function dailyRevenue(Carbon $date, bool $hasPaymentsTable): float
    {
        $manual = ThanhToan::where('trang_thai', 'thanh_cong')
            ->whereDate('thoi_diem_thanh_toan', $date)
            ->sum('so_tien');

        $online = $hasPaymentsTable
            ? Payment::where('status', Payment::STATUS_SUCCEEDED)
                ->whereDate('paid_at', $date)
                ->sum('amount_vnd')
            : 0;

        return (float) ($manual + $online);
    }
}
