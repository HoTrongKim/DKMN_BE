<?php

namespace App\Http\Controllers;

use App\Models\QuyenHan;

class QuyenHanController extends Controller
{
    public function getData()
    {
        return response()->json(['data' => QuyenHan::orderBy('ten')->get()]);
    }
}


