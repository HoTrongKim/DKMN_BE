<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class DonHang extends Model
{
    protected $table = 'don_hangs';
    protected $fillable = [
        'nguoi_dung_id',
        'chuyen_di_id',
        'noi_di',
        'noi_den',
        'tram_don',
        'tram_tra',
        'so_hanh_khach',
        'ten_nha_van_hanh',
        'cong_thanh_toan',
        'ma_don',
        'ten_khach',
        'sdt_khach',
        'email_khach',
        'tong_tien',
        'trang_thai',
        'trang_thai_chuyen',
    ];
    const CREATED_AT = 'ngay_tao';
    const UPDATED_AT = 'ngay_cap_nhat';

    public function chuyenDi(): BelongsTo
    {
        return $this->belongsTo(ChuyenDi::class, 'chuyen_di_id');
    }

    public function nguoiDung(): BelongsTo
    {
        return $this->belongsTo(NguoiDung::class, 'nguoi_dung_id');
    }

    public function ticket(): HasOne
    {
        return $this->hasOne(Ticket::class, 'don_hang_id');
    }

    public function thanhToans(): HasMany
    {
        return $this->hasMany(ThanhToan::class, 'don_hang_id')->orderByDesc('thoi_diem_thanh_toan');
    }

    public function chiTietDonHang(): HasMany
    {
        return $this->hasMany(ChiTietDonHang::class, 'don_hang_id');
    }
}
