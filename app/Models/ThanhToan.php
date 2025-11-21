<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ThanhToan extends Model
{
    protected $table = 'thanh_toans';
    protected $fillable = [
        'don_hang_id','ma_thanh_toan','cong_thanh_toan','so_tien','trang_thai',
        'ma_giao_dich','phan_hoi_gateway','thoi_diem_thanh_toan',
    ];
    const CREATED_AT = 'ngay_tao';
    const UPDATED_AT = 'ngay_cap_nhat';

    public function donHang(): BelongsTo
    {
        return $this->belongsTo(DonHang::class, 'don_hang_id');
    }
}

