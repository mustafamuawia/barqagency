<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('point_settings', function (Blueprint $table) {
            $table->decimal('agency_commission_pct', 5, 2)->default(0)->after('point_amount');
        });
    }

    public function down(): void
    {
        Schema::table('point_settings', function (Blueprint $table) {
            $table->dropColumn('agency_commission_pct');
        });
    }
};
