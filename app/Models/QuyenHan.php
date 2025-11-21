<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuyenHan extends Model
{
    protected $table = 'quyen_hans';
    public $timestamps = false;
    protected $fillable = ['ten','mo_ta','danh_sach_quyen','trang_thai'];
}


