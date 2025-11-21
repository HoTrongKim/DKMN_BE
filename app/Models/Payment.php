<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $table = 'payments';
    protected $fillable = [
        'ticket_id',
        'method',
        'provider',
        'provider_ref',
        'qr_image_url',
        'amount_vnd',
        'status',
        'checksum',
        'idempotency_key',
        'webhook_idempotency_key',
        'paid_at',
        'expires_at',
    ];
    protected $casts = [
        'paid_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    const STATUS_PENDING = 'PENDING';
    const STATUS_SUCCEEDED = 'SUCCEEDED';
    const STATUS_FAILED = 'FAILED';
    const STATUS_MISMATCH = 'MISMATCH';
    const STATUS_EXPIRED = 'EXPIRED';

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class, 'ticket_id');
    }
}
