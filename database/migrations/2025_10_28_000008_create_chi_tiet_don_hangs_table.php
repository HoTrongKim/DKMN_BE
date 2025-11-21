<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('chi_tiet_don_hangs')) {
            return;
        }

        Schema::create('chi_tiet_don_hangs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('don_hang_id');
            $table->unsignedBigInteger('ghe_id');
            $table->string('ten_hanh_khach', 100);
            $table->string('sdt_hanh_khach', 20)->nullable();
            $table->decimal('gia_ghe', 12, 2);
            $table->timestamp('ngay_tao')->useCurrent();

            $table->index(['don_hang_id'], 'fk_ctdh_dh');
            $table->index(['ghe_id'], 'fk_ctdh_ghe');

            $table->foreign('don_hang_id')->references('id')->on('don_hangs')
                ->cascadeOnDelete()->restrictOnUpdate();
            $table->foreign('ghe_id')->references('id')->on('ghes')
                ->restrictOnDelete()->restrictOnUpdate();

            $table->engine = 'InnoDB';
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chi_tiet_don_hangs');
    }
};


