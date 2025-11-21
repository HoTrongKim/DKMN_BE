<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Tram extends Model
{
    protected $table = 'trams';
    public $timestamps = false;
    protected $fillable = ['ten','tinh_thanh_id','loai','dia_chi'];

    public function tinhThanh(): BelongsTo
    {
        return $this->belongsTo(TinhThanh::class, 'tinh_thanh_id');
    }
}

