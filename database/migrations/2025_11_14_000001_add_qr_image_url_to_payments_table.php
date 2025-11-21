<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasColumn('payments', 'qr_image_url')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->string('qr_image_url')->nullable()->after('provider_ref');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('payments', 'qr_image_url')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->dropColumn('qr_image_url');
            });
        }
    }
};
