<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DanhGia extends Model
{
    protected $table = 'danh_gias';
    public $timestamps = false;
    protected $fillable = [
        'nguoi_dung_id',
        'chuyen_di_id',
        'don_hang_id',
        'diem',
        'nhan_xet',
        'trang_thai',
        'ngay_tao',
    ];
    const CREATED_AT = 'ngay_tao';

    public function nguoiDung(): BelongsTo
    {
        return $this->belongsTo(NguoiDung::class, 'nguoi_dung_id');
    }

    public function chuyenDi(): BelongsTo
    {
        return $this->belongsTo(ChuyenDi::class, 'chuyen_di_id');
    }

    public function donHang(): BelongsTo
    {
        return $this->belongsTo(DonHang::class, 'don_hang_id');
    }
}

