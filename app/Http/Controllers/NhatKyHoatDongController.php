<?php

namespace App\Http\Controllers;

use App\Models\NhatKyHoatDong;
use Illuminate\Http\Request;

class NhatKyHoatDongController extends Controller
{
    public function getData(Request $request)
    {
        $query = NhatKyHoatDong::query()->orderByDesc('ngay_tao');

        if ($request->filled('nguoi_dung_id')) {
            $query->where('nguoi_dung_id', (int) $request->input('nguoi_dung_id'));
        } elseif ($request->boolean('me') && $request->user()) {
            $query->where('nguoi_dung_id', $request->user()->id);
        }

        if ($request->filled('hanh_dong')) {
            $query->where('hanh_dong', $request->input('hanh_dong'));
        }

        if ($request->filled('tu_ngay')) {
            $query->whereDate('ngay_tao', '>=', $request->input('tu_ngay'));
        }

        if ($request->filled('den_ngay')) {
            $query->whereDate('ngay_tao', '<=', $request->input('den_ngay'));
        }

        return response()->json(['data' => $query->get()]);
    }
}

