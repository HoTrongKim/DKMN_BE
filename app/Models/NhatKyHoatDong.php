<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NhatKyHoatDong extends Model
{
    protected $table = 'nhat_ky_hoat_dongs';
    public $timestamps = false;
    protected $fillable = ['nguoi_dung_id','hanh_dong','mo_ta','ip','user_agent'];
}


