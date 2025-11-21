<?php

namespace App\Services;

use App\Models\ChuyenDi;
use App\Models\Ghe;
use App\Models\Ticket;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class TicketHoldService
{
    public static function holdMinutes(): int
    {
        $minutes = (int) config('payments.ticket_hold_minutes', 10);

        return $minutes > 0 ? $minutes : 10;
    }

    public static function holdExpiresAt(Ticket $ticket): ?Carbon
    {
        if (!$ticket->created_at) {
            return null;
        }

        return $ticket->created_at->copy()->addMinutes(self::holdMinutes());
    }

    public static function isExpired(Ticket $ticket): bool
    {
        if ($ticket->status !== Ticket::STATUS_PENDING) {
            return false;
        }

        if ((int) $ticket->paid_amount_vnd > 0) {
            return false;
        }

        $expiresAt = self::holdExpiresAt($ticket);

        return $expiresAt ? now()->greaterThanOrEqualTo($expiresAt) : false;
    }

    public static function releaseExpiredForTrip(ChuyenDi $trip): void
    {
        $threshold = now()->subMinutes(self::holdMinutes());
        $batchSize = 50;

        while (true) {
            $tickets = Ticket::query()
                ->where('trip_id', $trip->id)
                ->where('status', Ticket::STATUS_PENDING)
                ->whereNull('paid_amount_vnd')
                ->where('created_at', '<=', $threshold)
                ->with('donHang')
                ->limit($batchSize)
                ->get();

            if ($tickets->isEmpty()) {
                break;
            }

            foreach ($tickets as $ticket) {
                self::expireTicket($ticket);
            }

            if ($tickets->count() < $batchSize) {
                break;
            }
        }
    }

    public static function expireTicket(Ticket $ticket): void
    {
        if ($ticket->status === Ticket::STATUS_CANCELLED) {
            return;
        }

        DB::transaction(function () use ($ticket) {
            $seatCodes = self::parseSeatCodes($ticket->seat_numbers);
            if (!empty($seatCodes)) {
                Ghe::where('chuyen_di_id', $ticket->trip_id)
                    ->whereIn('so_ghe', $seatCodes)
                    ->update([
                        'trang_thai' => 'trong',
                        'ngay_cap_nhat' => now(),
                    ]);
            }

            $ticket->forceFill([
                'status' => Ticket::STATUS_CANCELLED,
            ])->save();

            $order = $ticket->relationLoaded('donHang')
                ? $ticket->donHang
                : $ticket->donHang()->first();

            if ($order) {
                $order->forceFill([
                    'trang_thai' => 'da_huy',
                    'ngay_cap_nhat' => now(),
                ])->save();
            }
        });
    }

    private static function parseSeatCodes(?string $value): array
    {
        if (!$value) {
            return [];
        }

        return array_values(array_filter(array_map(static function ($seat) {
            return trim($seat);
        }, explode(',', $value))));
    }
}
