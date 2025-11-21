<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NguoiDungQuyenHan extends Model
{
    protected $table = 'nguoi_dung_quyen_hans';
    public $timestamps = false;
    protected $fillable = ['nguoi_dung_id','quyen_han_id','ngay_cap','ngay_het_han'];
}


