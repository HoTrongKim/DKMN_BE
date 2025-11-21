<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('thanh_toans')) {
            return;
        }

        Schema::create('thanh_toans', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('don_hang_id');
            $table->string('ma_thanh_toan', 50);
            $table->enum('cong_thanh_toan', ['momo','zalopay','paypal','ngan_hang','tra_sau']);
            $table->decimal('so_tien', 12, 2);
            $table->enum('trang_thai', ['cho','thanh_cong','that_bai','hoan_tien'])->default('cho');
            $table->string('ma_giao_dich', 100)->nullable();
            $table->text('phan_hoi_gateway')->nullable();
            $table->timestamp('thoi_diem_thanh_toan')->nullable();
            $table->timestamp('ngay_tao')->useCurrent();
            $table->timestamp('ngay_cap_nhat')->useCurrent()->useCurrentOnUpdate();

            $table->unique(['ma_thanh_toan'], 'ma_thanh_toan');
            $table->index(['don_hang_id'], 'idx_tt_dh');
            $table->index(['trang_thai'], 'idx_tt_trangthai');
            $table->index(['cong_thanh_toan'], 'idx_tt_cong');

            $table->foreign('don_hang_id')->references('id')->on('don_hangs')
                ->restrictOnDelete()->restrictOnUpdate();

            $table->engine = 'InnoDB';
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('thanh_toans');
    }
};


