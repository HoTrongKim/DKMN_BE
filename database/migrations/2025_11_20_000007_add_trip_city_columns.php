<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('chuyen_dis', function (Blueprint $table) {
            if (!Schema::hasColumn('chuyen_dis', 'noi_di_tinh_thanh_id')) {
                $table->unsignedBigInteger('noi_di_tinh_thanh_id')
                    ->nullable()
                    ->after('tram_di_id');
                $table->foreign('noi_di_tinh_thanh_id', 'fk_chuyen_dis_from_city')
                    ->references('id')
                    ->on('tinh_thanhs')
                    ->nullOnDelete()
                    ->cascadeOnUpdate();
            }

            if (!Schema::hasColumn('chuyen_dis', 'noi_den_tinh_thanh_id')) {
                $table->unsignedBigInteger('noi_den_tinh_thanh_id')
                    ->nullable()
                    ->after('tram_den_id');
                $table->foreign('noi_den_tinh_thanh_id', 'fk_chuyen_dis_to_city')
                    ->references('id')
                    ->on('tinh_thanhs')
                    ->nullOnDelete()
                    ->cascadeOnUpdate();
            }

        });
    }

    public function down(): void
    {
        Schema::table('chuyen_dis', function (Blueprint $table) {
            if (Schema::hasColumn('chuyen_dis', 'noi_di_tinh_thanh_id')) {
                $table->dropForeign('fk_chuyen_dis_from_city');
                $table->dropColumn('noi_di_tinh_thanh_id');
            }

            if (Schema::hasColumn('chuyen_dis', 'noi_den_tinh_thanh_id')) {
                $table->dropForeign('fk_chuyen_dis_to_city');
                $table->dropColumn('noi_den_tinh_thanh_id');
            }

        });
    }
};
