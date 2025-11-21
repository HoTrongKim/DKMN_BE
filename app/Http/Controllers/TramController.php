<?php

namespace App\Http\Controllers;

use App\Models\Tram;
use Illuminate\Http\Request;

class TramController extends Controller
{
    public function getData(Request $request)
    {
        $query = Tram::query()->with('tinhThanh');

        if ($request->filled('tinh_thanh_id')) {
            $query->where('tinh_thanh_id', (int) $request->input('tinh_thanh_id'));
        }

        if ($request->filled('loai')) {
            $query->where('loai', $request->input('loai'));
        }

        if ($request->filled('keyword')) {
            $keyword = trim($request->input('keyword'));
            $query->where('ten', 'like', "%{$keyword}%");
        }

        $trams = $query->orderBy('ten')->get()->map(function (Tram $tram) {
            return [
                'id' => $tram->id,
                'ten' => $tram->ten,
                'loai' => $tram->loai,
                'dia_chi' => $tram->dia_chi,
                'tinh_thanh_id' => $tram->tinh_thanh_id,
                'tinh_thanh' => $tram->tinhThanh->ten ?? null,
            ];
        });

        return response()->json(['data' => $trams]);
    }
}
