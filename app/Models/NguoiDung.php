<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NguoiDung extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $table = 'nguoi_dungs';
    protected $fillable = [
        'ho_ten','email','so_dien_thoai','mat_khau','vai_tro','trang_thai',
    ];
    const CREATED_AT = 'ngay_tao';
    const UPDATED_AT = 'ngay_cap_nhat';
    protected $hidden = ['mat_khau','remember_token'];

    public function donHangs(): HasMany
    {
        return $this->hasMany(DonHang::class, 'nguoi_dung_id');
    }
}
