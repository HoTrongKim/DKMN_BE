<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('thong_baos')) {
            return;
        }

        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return;
        }

        Schema::table('thong_baos', function (Blueprint $table) {
            // Expand enum to include 'inbox' for user inbox messages
            $table->enum('loai', ['info', 'warning', 'success', 'error', 'trip_update', 'inbox'])
                ->default('info')
                ->change();
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('thong_baos')) {
            return;
        }

        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return;
        }

        // Convert any existing 'inbox' records back to 'info' before shrinking enum
        DB::table('thong_baos')->where('loai', 'inbox')->update(['loai' => 'info']);

        Schema::table('thong_baos', function (Blueprint $table) {
            $table->enum('loai', ['info', 'warning', 'success', 'error', 'trip_update'])
                ->default('info')
                ->change();
        });
    }
};
