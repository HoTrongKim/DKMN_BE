<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('nguoi_dungs')) {
            return;
        }

        Schema::create('nguoi_dungs', function (Blueprint $table) {
            $table->id();
            $table->string('ho_ten', 100);
            $table->string('email', 100);
            $table->string('so_dien_thoai', 20)->nullable();
            $table->string('mat_khau', 255);
            $table->enum('vai_tro', ['khach_hang','quan_tri'])->default('khach_hang');
            $table->enum('trang_thai', ['hoat_dong','khoa'])->default('hoat_dong');
            $table->timestamp('ngay_tao')->useCurrent();
            $table->timestamp('ngay_cap_nhat')->useCurrent()->useCurrentOnUpdate();

            $table->unique(['email'], 'email');
            $table->index(['email'], 'idx_nd_email');
            $table->index(['so_dien_thoai'], 'idx_nd_sdt');
            $table->index(['vai_tro'], 'idx_nd_vaitro');
            $table->index(['trang_thai'], 'idx_nd_trangthai');

            $table->engine = 'InnoDB';
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nguoi_dungs');
    }
};


