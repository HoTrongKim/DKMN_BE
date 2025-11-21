<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('huy_ves')) {
            return;
        }

        Schema::create('huy_ves', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('don_hang_id');
            $table->enum('ly_do_huy', ['khach_hang','nha_van_hanh','he_thong','thien_tai']);
            $table->text('mo_ta')->nullable();
            $table->decimal('ty_le_hoan_tien', 5, 2);
            $table->decimal('so_tien_hoan', 12, 2);
            $table->decimal('phi_huy', 12, 2)->default(0.00);
            $table->enum('trang_thai', ['cho_xu_ly','da_hoan','tu_choi'])->default('cho_xu_ly');
            $table->unsignedBigInteger('nguoi_xu_ly')->nullable();
            $table->timestamp('ngay_huy')->useCurrent();
            $table->timestamp('ngay_hoan_tien')->nullable();

            $table->index(['nguoi_xu_ly'], 'fk_hv_nd');
            $table->index(['don_hang_id'], 'idx_huy_don_hang');
            $table->index(['trang_thai'], 'idx_huy_trang_thai');
            $table->index(['ngay_huy'], 'idx_huy_ngay');

            $table->foreign('don_hang_id')->references('id')->on('don_hangs')
                ->restrictOnDelete()->restrictOnUpdate();
            $table->foreign('nguoi_xu_ly')->references('id')->on('nguoi_dungs')
                ->restrictOnDelete()->restrictOnUpdate();

            $table->engine = 'InnoDB';
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('huy_ves');
    }
};


