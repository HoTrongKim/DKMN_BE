<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HuyVe extends Model
{
    protected $table = 'huy_ves';
    public $timestamps = false;
    protected $fillable = [
        'don_hang_id','ly_do_huy','mo_ta','ty_le_hoan_tien','so_tien_hoan','phi_huy',
        'trang_thai','nguoi_xu_ly','ngay_huy','ngay_hoan_tien',
    ];
}


