<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('danh_gias')) {
            return;
        }

        Schema::create('danh_gias', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('nguoi_dung_id');
            $table->unsignedBigInteger('chuyen_di_id');
            $table->unsignedBigInteger('don_hang_id')->nullable();
            $table->integer('diem');
            $table->text('nhan_xet')->nullable();
            $table->enum('trang_thai', ['cho_duyet','chap_nhan','tu_choi'])->default('cho_duyet');
            $table->timestamp('ngay_tao')->useCurrent();

            $table->index(['nguoi_dung_id'], 'fk_dg_nd');
            $table->index(['chuyen_di_id'], 'fk_dg_cd');
            $table->index(['don_hang_id'], 'fk_dg_dh');

            $table->foreign('chuyen_di_id')->references('id')->on('chuyen_dis')
                ->restrictOnDelete()->restrictOnUpdate();
            $table->foreign('don_hang_id')->references('id')->on('don_hangs')
                ->restrictOnDelete()->restrictOnUpdate();
            $table->foreign('nguoi_dung_id')->references('id')->on('nguoi_dungs')
                ->restrictOnDelete()->restrictOnUpdate();

            $table->engine = 'InnoDB';
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('danh_gias');
    }
};


