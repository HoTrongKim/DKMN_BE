<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('chi_tiet_phi_don_hangs')) {
            return;
        }

        Schema::create('chi_tiet_phi_don_hangs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('don_hang_id');
            $table->unsignedBigInteger('phi_dich_vu_id');
            $table->decimal('so_tien', 12, 2);
            $table->string('mo_ta', 200)->nullable();
            $table->timestamp('ngay_tao')->useCurrent();

            $table->index(['don_hang_id'], 'fk_ctpdh_dh');
            $table->index(['phi_dich_vu_id'], 'fk_ctpdh_phi');

            $table->foreign('don_hang_id')->references('id')->on('don_hangs')
                ->cascadeOnDelete()->restrictOnUpdate();
            $table->foreign('phi_dich_vu_id')->references('id')->on('phi_dich_vus')
                ->restrictOnDelete()->restrictOnUpdate();

            $table->engine = 'InnoDB';
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chi_tiet_phi_don_hangs');
    }
};


