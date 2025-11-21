<?php

namespace App\Http\Controllers;

use App\Models\PhanHoi;

class PhanHoiController extends Controller
{
    public function getData()
    {
        return response()->json(['data' => PhanHoi::orderByDesc('ngay_tao')->get()]);
    }
}


