<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        $min = (int) config('payments.display_min_vnd', 1000);
        $max = (int) config('payments.display_max_vnd', 2000);

        DB::table('chuyen_dis')
            ->orderBy('id')
            ->chunkById(500, function ($trips) use ($min, $max) {
                foreach ($trips as $trip) {
                    $current = (int) $trip->gia_co_ban;
                    $normalized = max($min, min($max, $current));
                    if ($normalized !== $current) {
                        DB::table('chuyen_dis')
                            ->where('id', $trip->id)
                            ->update(['gia_co_ban' => $normalized]);
                    }
                }
            });

        DB::table('ghes')
            ->orderBy('id')
            ->chunkById(1000, function ($seats) use ($min, $max) {
                foreach ($seats as $seat) {
                    $current = (int) $seat->gia;
                    $normalized = max($min, min($max, $current));
                    if ($normalized !== $current) {
                        DB::table('ghes')
                            ->where('id', $seat->id)
                            ->update(['gia' => $normalized]);
                    }
                }
            });
    }

    public function down(): void
    {
        // No-op. Historical data does not need to be reverted.
    }
};
