<?php

namespace App\Http\Controllers;

use App\Models\NguoiDungQuyenHan;

class NguoiDungQuyenHanController extends Controller
{
    public function getData()
    {
        return response()->json(['data' => NguoiDungQuyenHan::orderByDesc('ngay_cap')->get()]);
    }
}


