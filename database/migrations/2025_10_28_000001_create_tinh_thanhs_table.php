<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('tinh_thanhs')) {
            return;
        }

        Schema::create('tinh_thanhs', function (Blueprint $table) {
            $table->id();
            $table->string('ten', 100);
            $table->string('ma', 10)->nullable()->unique();
            $table->timestamp('ngay_tao')->useCurrent();

            $table->engine = 'InnoDB';
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tinh_thanhs');
    }
};

