<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NhaVanHanh extends Model
{
    protected $table = 'nha_van_hanhs';
    public $timestamps = false;
    protected $fillable = ['ten','loai','mo_ta','lien_he_dien_thoai','lien_he_email','trang_thai'];
}


