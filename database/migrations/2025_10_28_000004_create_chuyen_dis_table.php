<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('chuyen_dis')) {
            return;
        }

        Schema::create('chuyen_dis', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('nha_van_hanh_id');
            $table->unsignedBigInteger('tram_di_id');
            $table->unsignedBigInteger('tram_den_id');
            $table->dateTime('gio_khoi_hanh');
            $table->dateTime('gio_den');
            $table->decimal('gia_co_ban', 12, 2);
            $table->integer('tong_ghe');
            $table->integer('ghe_con');
            $table->enum('trang_thai', ['CON_VE','HET_VE','HUY'])->default('CON_VE');
            $table->timestamp('ngay_tao')->useCurrent();
            $table->timestamp('ngay_cap_nhat')->useCurrent()->useCurrentOnUpdate();

            $table->unique(['nha_van_hanh_id','tram_di_id','tram_den_id','gio_khoi_hanh'], 'uq_chuyen');
            $table->index(['gio_khoi_hanh'], 'idx_cd_gio_kh');
            $table->index(['tram_di_id'], 'idx_cd_tram_di');
            $table->index(['tram_den_id'], 'idx_cd_tram_den');
            $table->index(['nha_van_hanh_id'], 'idx_cd_nvh');
            $table->index(['trang_thai'], 'idx_cd_trangthai');

            $table->foreign('nha_van_hanh_id')->references('id')->on('nha_van_hanhs')
                ->restrictOnDelete()->restrictOnUpdate();
            $table->foreign('tram_di_id')->references('id')->on('trams')
                ->restrictOnDelete()->restrictOnUpdate();
            $table->foreign('tram_den_id')->references('id')->on('trams')
                ->restrictOnDelete()->restrictOnUpdate();

            $table->engine = 'InnoDB';
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chuyen_dis');
    }
};


