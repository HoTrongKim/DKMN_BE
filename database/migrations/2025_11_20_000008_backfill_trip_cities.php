<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('chuyen_dis', function (Blueprint $table) {
            // Ensure the city columns exist for legacy databases.
            if (!Schema::hasColumn('chuyen_dis', 'noi_di_tinh_thanh_id')) {
                $table->unsignedBigInteger('noi_di_tinh_thanh_id')
                    ->nullable()
                    ->after('tram_di_id');
            }

            if (!Schema::hasColumn('chuyen_dis', 'noi_den_tinh_thanh_id')) {
                $table->unsignedBigInteger('noi_den_tinh_thanh_id')
                    ->nullable()
                    ->after('tram_den_id');
            }
        });

        // Backfill city ids from tram references to avoid empty search results.
        DB::statement(<<<SQL
UPDATE chuyen_dis cd
JOIN trams t ON cd.tram_di_id = t.id
SET cd.noi_di_tinh_thanh_id = t.tinh_thanh_id
WHERE cd.noi_di_tinh_thanh_id IS NULL
SQL);

        DB::statement(<<<SQL
UPDATE chuyen_dis cd
JOIN trams t ON cd.tram_den_id = t.id
SET cd.noi_den_tinh_thanh_id = t.tinh_thanh_id
WHERE cd.noi_den_tinh_thanh_id IS NULL
SQL);

        // Log a hint so operators know the migration ran.
        Log::info('Backfilled noi_di_tinh_thanh_id/noi_den_tinh_thanh_id for chuyen_dis');
    }

    public function down(): void
    {
        // Do not drop data; only drop columns if they exist.
        Schema::table('chuyen_dis', function (Blueprint $table) {
            if (Schema::hasColumn('chuyen_dis', 'noi_di_tinh_thanh_id')) {
                $table->dropColumn('noi_di_tinh_thanh_id');
            }
            if (Schema::hasColumn('chuyen_dis', 'noi_den_tinh_thanh_id')) {
                $table->dropColumn('noi_den_tinh_thanh_id');
            }
        });
    }
};
