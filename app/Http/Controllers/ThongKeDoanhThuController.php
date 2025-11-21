<?php

namespace App\Http\Controllers;

use App\Models\ThongKeDoanhThu;
use Illuminate\Http\Request;

class ThongKeDoanhThuController extends Controller
{
    public function getData(Request $request)
    {
        $query = ThongKeDoanhThu::query()->orderByDesc('ngay');

        if ($request->filled('fromDate')) {
            $query->whereDate('ngay', '>=', $request->input('fromDate'));
        }

        if ($request->filled('toDate')) {
            $query->whereDate('ngay', '<=', $request->input('toDate'));
        }

        if ($request->filled('vehicleType')) {
            $query->where('loai_phuong_tien', $request->input('vehicleType'));
        }

        $data = $query->get();

        return response()->json([
            'data' => $data,
            'meta' => [
                'totalRevenue' => (float) $data->sum('tong_doanh_thu'),
                'netRevenue' => (float) $data->sum('doanh_thu_thuc'),
                'totalOrders' => (int) $data->sum('so_don_hang'),
                'totalTickets' => (int) $data->sum('so_ve_ban'),
                'cancellationRate' => $data->avg('ty_le_huy') ?? 0,
            ],
        ]);
    }
}

