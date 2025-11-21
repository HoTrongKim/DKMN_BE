<?php

namespace App\Services;

use App\Models\ChuyenDi;
use App\Models\Ghe;
use App\Services\TicketHoldService;

class TripSeatSynchronizer
{
    public static function sync(ChuyenDi $trip): void
    {
        $trip->loadMissing('nhaVanHanh');
        TicketHoldService::releaseExpiredForTrip($trip);
        $total = max(0, (int) ($trip->tong_ghe ?? 0));
        if ($total === 0) {
            return;
        }

        $existing = Ghe::where('chuyen_di_id', $trip->id)->count();
        if ($existing < $total) {
            self::createMissingSeats($trip, $existing, $total);
        }

        $available = Ghe::where('chuyen_di_id', $trip->id)
            ->where('trang_thai', 'trong')
            ->count();

        $trip->forceFill([
            'ghe_con' => min($available, $total),
        ])->save();
    }

    private static function createMissingSeats(ChuyenDi $trip, int $existing, int $target): void
    {
        $seatType = self::seatTypeForOperator($trip->nhaVanHanh->loai ?? null);
        $now = now();
        $rows = [];

        for ($i = $existing + 1; $i <= $target; $i++) {
            $rows[] = [
                'chuyen_di_id' => $trip->id,
                'so_ghe' => self::generateSeatLabel($i),
                'loai_ghe' => $seatType,
                'gia' => $trip->gia_co_ban ?? 0,
                'trang_thai' => 'trong',
                'ngay_tao' => $now,
            ];
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            Ghe::insert($chunk);
        }
    }

    private static function seatTypeForOperator(?string $type): string
    {
        return match ($type) {
            'may_bay' => 'thuong_gia',
            'tau_hoa' => 'vip',
            default => 'thuong',
        };
    }

    public static function generateSeatLabel(int $index): string
    {
        $index = max(1, $index);
        $deckSize = 20;
        $letters = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J'];
        $deck = intdiv($index - 1, $deckSize);
        $letter = $letters[$deck] ?? chr(ord('A') + $deck);
        $number = (($index - 1) % $deckSize) + 1;

        return sprintf('%s%d', $letter, $number);
    }
}
