<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('payments')) {
            return;
        }

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ticket_id');
            $table->enum('method', ['QR','CASH_ONBOARD']);
            $table->string('provider')->nullable();
            $table->string('provider_ref')->nullable();
            $table->unsignedBigInteger('amount_vnd');
            $table->enum('status', ['PENDING','SUCCEEDED','FAILED','MISMATCH','EXPIRED'])->default('PENDING');
            $table->string('checksum', 128)->nullable();
            $table->string('idempotency_key', 128)->nullable();
            $table->string('webhook_idempotency_key', 128)->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->unique(['ticket_id','method','provider','idempotency_key'], 'payments_unique_intent');
            $table->index(['ticket_id'], 'idx_payments_ticket');
            $table->foreign('ticket_id')->references('id')->on('tickets')
                ->restrictOnDelete()->restrictOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
