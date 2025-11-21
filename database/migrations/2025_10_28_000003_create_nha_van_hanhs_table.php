<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('nha_van_hanhs')) {
            return;
        }

        Schema::create('nha_van_hanhs', function (Blueprint $table) {
            $table->id();
            $table->string('ten', 200);
            $table->enum('loai', ['xe_khach','tau_hoa','may_bay']);
            $table->text('mo_ta')->nullable();
            $table->string('lien_he_dien_thoai', 20)->nullable();
            $table->string('lien_he_email', 100)->nullable();
            $table->enum('trang_thai', ['hoat_dong','tam_dung'])->default('hoat_dong');
            $table->timestamp('ngay_tao')->useCurrent();

            $table->unique(['ten','loai'], 'uq_nvh');

            $table->engine = 'InnoDB';
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nha_van_hanhs');
    }
};


