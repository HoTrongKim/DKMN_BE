<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('phi_dich_vus')) {
            return;
        }

        Schema::create('phi_dich_vus', function (Blueprint $table) {
            $table->id();
            $table->string('ten', 100);
            $table->enum('loai', ['phi_bao_hiem','phi_phuc_vu','phi_hoan_ve','phi_doi_ve','phi_phat']);
            $table->decimal('gia_tri', 12, 2);
            $table->enum('loai_tinh', ['co_dinh','phan_tram'])->default('co_dinh');
            $table->enum('ap_dung_cho', ['tat_ca','xe_khach','tau_hoa','may_bay'])->default('tat_ca');
            $table->enum('trang_thai', ['hoat_dong','tam_dung'])->default('hoat_dong');
            $table->timestamp('ngay_tao')->useCurrent();
            $table->timestamp('ngay_cap_nhat')->useCurrent()->useCurrentOnUpdate();

            $table->index(['loai'], 'idx_phi_loai');
            $table->index(['trang_thai'], 'idx_phi_trang_thai');

            $table->engine = 'InnoDB';
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('phi_dich_vus');
    }
};


