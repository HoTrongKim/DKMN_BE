<?php

namespace App\Http\Controllers;

use App\Models\CauHinhHeThong;

class CauHinhHeThongController extends Controller
{
    public function getData()
    {
        return response()->json(['data' => CauHinhHeThong::orderBy('khoa')->get()]);
    }
}


