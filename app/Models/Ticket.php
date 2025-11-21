<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ticket extends Model
{
    protected $table = 'tickets';
    protected $fillable = [
        'don_hang_id',
        'trip_id',
        'seat_numbers',
        'status',
        'base_fare_vnd',
        'discount_vnd',
        'surcharge_vnd',
        'total_amount_vnd',
        'paid_amount_vnd',
        'payment_id',
    ];

    const STATUS_PENDING = 'PENDING';
    const STATUS_PAID = 'PAID';
    const STATUS_CANCELLED = 'CANCELLED';
    const STATUS_REFUNDED = 'REFUNDED';

    public function donHang(): BelongsTo
    {
        return $this->belongsTo(DonHang::class, 'don_hang_id');
    }

    public function trip(): BelongsTo
    {
        return $this->belongsTo(ChuyenDi::class, 'trip_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'ticket_id');
    }
}
