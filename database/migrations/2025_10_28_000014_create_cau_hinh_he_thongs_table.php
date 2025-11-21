<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('cau_hinh_he_thongs')) {
            return;
        }

        Schema::create('cau_hinh_he_thongs', function (Blueprint $table) {
            $table->id();
            $table->string('khoa', 100);
            $table->text('gia_tri')->nullable();
            $table->text('mo_ta')->nullable();
            $table->timestamp('ngay_tao')->useCurrent();
            $table->timestamp('ngay_cap_nhat')->useCurrent()->useCurrentOnUpdate();

            $table->unique(['khoa'], 'khoa');

            $table->engine = 'InnoDB';
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cau_hinh_he_thongs');
    }
};


