<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('quyen_hans')) {
            return;
        }

        Schema::create('quyen_hans', function (Blueprint $table) {
            $table->id();
            $table->string('ten', 100);
            $table->text('mo_ta')->nullable();
            $table->text('danh_sach_quyen')->nullable();
            $table->enum('trang_thai', ['hoat_dong','tam_dung'])->default('hoat_dong');
            $table->timestamp('ngay_tao')->useCurrent();

            $table->engine = 'InnoDB';
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quyen_hans');
    }
};


