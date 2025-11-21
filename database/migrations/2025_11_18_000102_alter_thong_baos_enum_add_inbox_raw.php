<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('thong_baos')) {
            return;
        }

        // SQLite không hỗ trợ ALTER ENUM theo cách này
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return;
        }

        // Use raw SQL to avoid doctrine/dbal requirement when altering ENUM
        DB::statement("
            ALTER TABLE thong_baos
            MODIFY loai ENUM('info','warning','success','error','trip_update','inbox') DEFAULT 'info'
        ");
    }

    public function down(): void
    {
        if (!Schema::hasTable('thong_baos')) {
            return;
        }

        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return;
        }

        // Revert to the previous set (without 'inbox')
        DB::statement("
            ALTER TABLE thong_baos
            MODIFY loai ENUM('info','warning','success','error','trip_update') DEFAULT 'info'
        ");
    }
};
