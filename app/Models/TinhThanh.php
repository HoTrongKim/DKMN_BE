<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TinhThanh extends Model
{
    protected $table = 'tinh_thanhs';
    public $timestamps = false;
    protected $fillable = ['ten','ma'];
}


