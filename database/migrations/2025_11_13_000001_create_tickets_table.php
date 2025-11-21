<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('tickets')) {
            return;
        }

        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('don_hang_id')->unique();
            $table->unsignedBigInteger('trip_id');
            $table->string('seat_numbers');
            $table->enum('status', ['PENDING','PAID','CANCELLED','REFUNDED'])->default('PENDING');
            $table->unsignedBigInteger('base_fare_vnd')->default(0);
            $table->unsignedBigInteger('discount_vnd')->default(0);
            $table->unsignedBigInteger('surcharge_vnd')->default(0);
            $table->unsignedBigInteger('total_amount_vnd')->default(0);
            $table->unsignedBigInteger('paid_amount_vnd')->nullable();
            $table->unsignedBigInteger('payment_id')->nullable();
            $table->timestamps();

            $table->foreign('don_hang_id')->references('id')->on('don_hangs')
                ->restrictOnDelete()->restrictOnUpdate();
            $table->foreign('trip_id')->references('id')->on('chuyen_dis')
                ->restrictOnDelete()->restrictOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
