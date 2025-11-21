<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::table('chuyen_dis')
            ->orderBy('id')
            ->chunkById(100, function ($trips) {
                foreach ($trips as $trip) {
                    $seatIds = DB::table('ghes')
                        ->where('chuyen_di_id', $trip->id)
                        ->orderBy('id')
                        ->pluck('id');

                    $index = 1;
                    foreach ($seatIds as $seatId) {
                        DB::table('ghes')
                            ->where('id', $seatId)
                            ->update(['so_ghe' => $this->seatLabel($index++)]);
                    }
                }
            });
    }

    public function down(): void
    {
        // No rollback - original seat labels are unknown.
    }

    private function seatLabel(int $index): string
    {
        $deckSize = 20;
        $letters = ['A', 'B', 'C', 'D', 'E', 'F'];

        $deck = intdiv(max(0, $index - 1), $deckSize);
        $letter = $letters[$deck] ?? chr(ord('A') + $deck);
        $number = (($index - 1) % $deckSize) + 1;

        return sprintf('%s%d', $letter, $number);
    }
};
