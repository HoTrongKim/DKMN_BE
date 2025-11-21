<?php

namespace App\Support;

class PriceNormalizer
{
    public static function clamp(int $amount, ?int $min = null, ?int $max = null): int
    {
        $minValue = $min !== null ? max(0, (int) $min) : 0;
        $maxValue = $max !== null ? max(0, (int) $max) : null;

        if ($maxValue !== null && $maxValue < $minValue) {
            [$minValue, $maxValue] = [$maxValue, $minValue];
        }

        $normalized = max($minValue, $amount);

        if ($maxValue !== null && $maxValue >= $minValue) {
            $normalized = min($normalized, $maxValue);
        }

        return $normalized;
    }

    public static function displayRange(): array
    {
        $min = max(0, (int) config('payments.display_min_vnd', 0));
        $max = (int) config('payments.display_max_vnd', 0);
        $max = $max > 0 ? $max : null;

        if ($max !== null && $max < $min) {
            [$min, $max] = [$max, $min];
        }

        return [$min, $max];
    }
}
