<?php

namespace App\Http\Controllers;

use App\Models\ChiTietPhiDonHang;

class ChiTietPhiDonHangController extends Controller
{
    public function getData()
    {
        return response()->json(['data' => ChiTietPhiDonHang::orderByDesc('ngay_tao')->get()]);
    }
}


