<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('don_hangs')) {
            return;
        }

        Schema::create('don_hangs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('nguoi_dung_id')->nullable();
            $table->unsignedBigInteger('chuyen_di_id');
            $table->string('ma_don', 50);
            $table->string('ten_khach', 100);
            $table->string('sdt_khach', 20);
            $table->string('email_khach', 100)->nullable();
            $table->decimal('tong_tien', 12, 2);
            $table->enum('trang_thai', ['cho_xu_ly','da_xac_nhan','hoan_tat','da_huy'])->default('cho_xu_ly');
            $table->enum('trang_thai_chuyen', ['cho_khoi_hanh','dang_di','da_den','huy'])->default('cho_khoi_hanh');
            $table->timestamp('ngay_tao')->useCurrent();
            $table->timestamp('ngay_cap_nhat')->useCurrent()->useCurrentOnUpdate();

            $table->unique(['ma_don'], 'ma_don');
            $table->index(['nguoi_dung_id'], 'idx_dh_nd');
            $table->index(['chuyen_di_id'], 'idx_dh_cd');
            $table->index(['trang_thai'], 'idx_dh_trangthai');
            $table->index(['ngay_tao'], 'idx_dh_ngay');

            $table->foreign('chuyen_di_id')->references('id')->on('chuyen_dis')
                ->restrictOnDelete()->restrictOnUpdate();
            $table->foreign('nguoi_dung_id')->references('id')->on('nguoi_dungs')
                ->restrictOnDelete()->restrictOnUpdate();

            $table->engine = 'InnoDB';
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('don_hangs');
    }
};


