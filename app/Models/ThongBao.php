<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ThongBao extends Model
{
    protected $table = 'thong_baos';
    public $timestamps = false;
    protected $fillable = ['nguoi_dung_id','tieu_de','noi_dung','loai','da_doc'];

    public const LOAI_TRIP_UPDATE = 'trip_update';
    public const LOAI_INBOX = 'inbox';

    public function nguoiDung()
    {
        return $this->belongsTo(NguoiDung::class, 'nguoi_dung_id');
    }
}
