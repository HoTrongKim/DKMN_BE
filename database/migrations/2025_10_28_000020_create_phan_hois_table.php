<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('phan_hois')) {
            return;
        }

        Schema::create('phan_hois', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('nguoi_dung_id')->nullable();
            $table->unsignedBigInteger('don_hang_id')->nullable();
            $table->enum('loai', ['ho_tro','khi_nai','goi_y','khac']);
            $table->string('tieu_de', 200);
            $table->text('noi_dung');
            $table->enum('trang_thai', ['moi','dang_xu_ly','da_tra_loi','dong'])->default('moi');
            $table->unsignedBigInteger('nguoi_phu_trach')->nullable();
            $table->text('tra_loi')->nullable();
            $table->timestamp('ngay_tra_loi')->nullable();
            $table->timestamp('ngay_tao')->useCurrent();
            $table->timestamp('ngay_cap_nhat')->useCurrent()->useCurrentOnUpdate();

            $table->index(['don_hang_id'], 'fk_ph_dh');
            $table->index(['nguoi_phu_trach'], 'fk_ph_pt');
            $table->index(['nguoi_dung_id'], 'idx_ph_nguoi_dung');
            $table->index(['trang_thai'], 'idx_ph_trang_thai');
            $table->index(['ngay_tao'], 'idx_ph_ngay');

            $table->foreign('don_hang_id')->references('id')->on('don_hangs')
                ->restrictOnDelete()->restrictOnUpdate();
            $table->foreign('nguoi_dung_id')->references('id')->on('nguoi_dungs')
                ->restrictOnDelete()->restrictOnUpdate();
            $table->foreign('nguoi_phu_trach')->references('id')->on('nguoi_dungs')
                ->restrictOnDelete()->restrictOnUpdate();

            $table->engine = 'InnoDB';
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('phan_hois');
    }
};


