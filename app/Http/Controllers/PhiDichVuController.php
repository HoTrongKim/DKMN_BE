<?php

namespace App\Http\Controllers;

use App\Models\PhiDichVu;

class PhiDichVuController extends Controller
{
    public function getData()
    {
        return response()->json(['data' => PhiDichVu::orderBy('ten')->get()]);
    }
}


