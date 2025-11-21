<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('nhat_ky_hoat_dongs')) {
            return;
        }

        Schema::create('nhat_ky_hoat_dongs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('nguoi_dung_id')->nullable();
            $table->string('hanh_dong', 100);
            $table->text('mo_ta')->nullable();
            $table->string('ip', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('ngay_tao')->useCurrent();

            $table->index(['nguoi_dung_id'], 'fk_nkhd_nd');

            $table->foreign('nguoi_dung_id')->references('id')->on('nguoi_dungs')
                ->restrictOnDelete()->restrictOnUpdate();

            $table->engine = 'InnoDB';
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nhat_ky_hoat_dongs');
    }
};


