<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChiTietPhiDonHang extends Model
{
    protected $table = 'chi_tiet_phi_don_hangs';
    public $timestamps = false;
    protected $fillable = ['don_hang_id','phi_dich_vu_id','so_tien','mo_ta'];
}


