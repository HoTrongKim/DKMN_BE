<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('thong_ke_doanh_thus')) {
            return;
        }

        Schema::create('thong_ke_doanh_thus', function (Blueprint $table) {
            $table->id();
            $table->date('ngay');
            $table->enum('loai_phuong_tien', ['tat_ca','xe_khach','tau_hoa','may_bay'])->default('tat_ca');
            $table->integer('so_don_hang')->default(0);
            $table->decimal('tong_doanh_thu', 15, 2)->default(0);
            $table->decimal('doanh_thu_thuc', 15, 2)->default(0);
            $table->integer('so_ve_ban')->default(0);
            $table->integer('so_ve_huy')->default(0);
            $table->decimal('ty_le_huy', 5, 2)->default(0);
            $table->timestamp('ngay_tao')->useCurrent();

            $table->unique(['ngay','loai_phuong_tien'], 'uq_thong_ke');
            $table->index(['ngay'], 'idx_tk_ngay');
            $table->index(['loai_phuong_tien'], 'idx_tk_loai');

            $table->engine = 'InnoDB';
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('thong_ke_doanh_thus');
    }
};


