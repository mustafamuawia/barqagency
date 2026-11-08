<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('point_settings', function (Blueprint $table) {
            $table->decimal('usd_to_sar_rate', 8, 4)->default(3.7500)->after('agency_commission_amount');
        });
    }

    public function down(): void
    {
        Schema::table('point_settings', function (Blueprint $table) {
            $table->dropColumn('usd_to_sar_rate');
        });
    }
};
