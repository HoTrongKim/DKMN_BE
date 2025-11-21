<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('nguoi_dung_quyen_hans')) {
            return;
        }

        Schema::create('nguoi_dung_quyen_hans', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('nguoi_dung_id');
            $table->unsignedBigInteger('quyen_han_id');
            $table->timestamp('ngay_cap')->useCurrent();
            $table->timestamp('ngay_het_han')->nullable();

            $table->unique(['nguoi_dung_id','quyen_han_id'], 'uq_nguoi_dung_quyen');
            $table->index(['quyen_han_id'], 'fk_ndqh_qh');

            $table->foreign('nguoi_dung_id')->references('id')->on('nguoi_dungs')
                ->cascadeOnDelete()->restrictOnUpdate();
            $table->foreign('quyen_han_id')->references('id')->on('quyen_hans')
                ->restrictOnDelete()->restrictOnUpdate();

            $table->engine = 'InnoDB';
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nguoi_dung_quyen_hans');
    }
};


