<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('ghes', function (Blueprint $table) {
            if (!Schema::hasColumn('ghes', 'ngay_cap_nhat')) {
                $table->timestamp('ngay_cap_nhat')->useCurrent()->useCurrentOnUpdate()->after('ngay_tao');
            }
        });
    }

    public function down(): void
    {
        Schema::table('ghes', function (Blueprint $table) {
            $table->dropColumn('ngay_cap_nhat');
        });
    }
};
