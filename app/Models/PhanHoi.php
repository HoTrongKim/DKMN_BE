<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PhanHoi extends Model
{
    protected $table = 'phan_hois';
    protected $fillable = [
        'nguoi_dung_id','don_hang_id','loai','tieu_de','noi_dung','trang_thai',
        'nguoi_phu_trach','tra_loi','ngay_tra_loi',
    ];
    const CREATED_AT = 'ngay_tao';
    const UPDATED_AT = 'ngay_cap_nhat';
}


