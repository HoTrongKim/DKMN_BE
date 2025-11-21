<?php

namespace App\Http\Controllers;

use App\Models\NhaVanHanh;
use Illuminate\Http\Request;

class NhaVanHanhController extends Controller
{
    public function getData(Request $request)
    {
        $query = NhaVanHanh::query();

        if ($request->filled('loai')) {
            $query->where('loai', $request->input('loai'));
        }

        if ($request->filled('trang_thai')) {
            $query->where('trang_thai', $request->input('trang_thai'));
        }

        if ($request->filled('keyword')) {
            $keyword = trim($request->input('keyword'));
            $query->where('ten', 'like', "%{$keyword}%");
        }

        return response()->json(['data' => $query->orderBy('ten')->get()]);
    }
}
