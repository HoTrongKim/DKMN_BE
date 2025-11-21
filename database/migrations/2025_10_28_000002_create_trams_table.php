<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('trams')) {
            return;
        }

        Schema::create('trams', function (Blueprint $table) {
            $table->id();
            $table->string('ten', 200);
            $table->unsignedBigInteger('tinh_thanh_id')->nullable();
            $table->enum('loai', ['ben_xe','ga_tau','san_bay']);
            $table->text('dia_chi')->nullable();
            $table->timestamp('ngay_tao')->useCurrent();

            $table->unique(['ten'], 'uq_tram_ten');
            $table->index(['tinh_thanh_id'], 'fk_trams_tinh_thanh');

            $table->foreign('tinh_thanh_id')
                ->references('id')->on('tinh_thanhs')
                ->restrictOnDelete()
                ->restrictOnUpdate();

            $table->engine = 'InnoDB';
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trams');
    }
};


