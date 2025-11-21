<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('ghes')) {
            return;
        }

        Schema::create('ghes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('chuyen_di_id');
            $table->string('so_ghe', 10);
            $table->enum('loai_ghe', ['thuong','vip','thuong_gia'])->default('thuong');
            $table->decimal('gia', 12, 2);
            $table->enum('trang_thai', ['trong','da_dat','khoa'])->default('trong');
            $table->timestamp('ngay_tao')->useCurrent();

            $table->unique(['chuyen_di_id','so_ghe'], 'uq_ghes');
            $table->index(['chuyen_di_id'], 'idx_ghe_cd');
            $table->index(['trang_thai'], 'idx_ghe_trangthai');

            $table->foreign('chuyen_di_id')->references('id')->on('chuyen_dis')
                ->restrictOnDelete()->restrictOnUpdate();

            $table->engine = 'InnoDB';
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ghes');
    }
};


