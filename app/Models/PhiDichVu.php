<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PhiDichVu extends Model
{
    protected $table = 'phi_dich_vus';
    protected $fillable = ['ten','loai','gia_tri','loai_tinh','ap_dung_cho','trang_thai'];
    const CREATED_AT = 'ngay_tao';
    const UPDATED_AT = 'ngay_cap_nhat';
}


