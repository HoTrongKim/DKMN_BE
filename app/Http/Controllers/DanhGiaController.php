<?php

namespace App\Http\Controllers;

use App\Models\DanhGia;

class DanhGiaController extends Controller
{
    public function getData()
    {
        return response()->json(['data' => DanhGia::orderByDesc('ngay_tao')->get()]);
    }
}


