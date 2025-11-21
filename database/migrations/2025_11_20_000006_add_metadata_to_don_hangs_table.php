<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('don_hangs', function (Blueprint $table) {
            if (!Schema::hasColumn('don_hangs', 'noi_di')) {
                $table->string('noi_di', 150)->nullable()->after('chuyen_di_id');
            }

            if (!Schema::hasColumn('don_hangs', 'noi_den')) {
                $table->string('noi_den', 150)->nullable()->after('noi_di');
            }

            if (!Schema::hasColumn('don_hangs', 'tram_don')) {
                $table->string('tram_don', 150)->nullable()->after('noi_den');
            }

            if (!Schema::hasColumn('don_hangs', 'tram_tra')) {
                $table->string('tram_tra', 150)->nullable()->after('tram_don');
            }

            if (!Schema::hasColumn('don_hangs', 'so_hanh_khach')) {
                $table->unsignedSmallInteger('so_hanh_khach')->default(1)->after('tram_tra');
            }

            if (!Schema::hasColumn('don_hangs', 'ten_nha_van_hanh')) {
                $table->string('ten_nha_van_hanh', 150)->nullable()->after('so_hanh_khach');
            }

            if (!Schema::hasColumn('don_hangs', 'cong_thanh_toan')) {
                $table->string('cong_thanh_toan', 50)->nullable()->after('ten_nha_van_hanh');
            }
        });
    }

    public function down(): void
    {
        Schema::table('don_hangs', function (Blueprint $table) {
            $columns = [
                'cong_thanh_toan',
                'ten_nha_van_hanh',
                'so_hanh_khach',
                'tram_tra',
                'tram_don',
                'noi_den',
                'noi_di',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('don_hangs', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
