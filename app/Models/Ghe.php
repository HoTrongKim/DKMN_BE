<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Ghe extends Model
{
    protected $table = 'ghes';
    public $timestamps = false;
    protected $fillable = ['chuyen_di_id','so_ghe','loai_ghe','gia','trang_thai'];

    public function chuyenDi(): BelongsTo
    {
        return $this->belongsTo(ChuyenDi::class, 'chuyen_di_id');
    }
}

