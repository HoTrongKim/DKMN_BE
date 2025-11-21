<?php

namespace App\Http\Controllers;

use App\Models\HuyVe;

class HuyVeController extends Controller
{
    public function getData()
    {
        return response()->json(['data' => HuyVe::orderByDesc('ngay_huy')->get()]);
    }
}


