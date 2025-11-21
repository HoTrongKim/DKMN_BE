<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('thong_baos')) {
            return;
        }

        Schema::create('thong_baos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('nguoi_dung_id')->nullable();
            $table->string('tieu_de', 200);
            $table->text('noi_dung');
            $table->enum('loai', ['info','warning','success','error','trip_update'])->default('info');
            $table->boolean('da_doc')->default(0);
            $table->timestamp('ngay_tao')->useCurrent();

            $table->index(['nguoi_dung_id'], 'fk_tb_nd');

            $table->foreign('nguoi_dung_id')->references('id')->on('nguoi_dungs')
                ->restrictOnDelete()->restrictOnUpdate();

            $table->engine = 'InnoDB';
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('thong_baos');
    }
};

