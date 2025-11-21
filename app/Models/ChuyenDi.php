<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\DonHang;

class ChuyenDi extends Model
{
    protected $table = 'chuyen_dis';
    protected $fillable = [
        'nha_van_hanh_id',
        'tram_di_id',
        'tram_den_id',
        'noi_di_tinh_thanh_id',
        'noi_den_tinh_thanh_id',
        'gio_khoi_hanh',
        'gio_den',
        'gia_co_ban',
        'tong_ghe',
        'ghe_con',
        'trang_thai',
    ];
    const CREATED_AT = 'ngay_tao';
    const UPDATED_AT = 'ngay_cap_nhat';

    protected $casts = [
        'gio_khoi_hanh' => 'datetime',
        'gio_den' => 'datetime',
    ];

    public function tramDi(): BelongsTo
    {
        return $this->belongsTo(Tram::class, 'tram_di_id');
    }

    public function tramDen(): BelongsTo
    {
        return $this->belongsTo(Tram::class, 'tram_den_id');
    }

    public function nhaVanHanh(): BelongsTo
    {
        return $this->belongsTo(NhaVanHanh::class, 'nha_van_hanh_id');
    }

    public function noiDiTinhThanh(): BelongsTo
    {
        return $this->belongsTo(TinhThanh::class, 'noi_di_tinh_thanh_id');
    }

    public function noiDenTinhThanh(): BelongsTo
    {
        return $this->belongsTo(TinhThanh::class, 'noi_den_tinh_thanh_id');
    }

    public function ghes(): HasMany
    {
        return $this->hasMany(Ghe::class, 'chuyen_di_id');
    }

    public function donHangs(): HasMany
    {
        return $this->hasMany(DonHang::class, 'chuyen_di_id');
    }

}
