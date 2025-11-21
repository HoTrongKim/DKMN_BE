<?php

namespace App\Http\Controllers;

use App\Models\TinhThanh;

class TinhThanhController extends Controller
{
    public function getData()
    {
        return response()->json(['data' => TinhThanh::orderBy('ten')->get()]);
    }
}


