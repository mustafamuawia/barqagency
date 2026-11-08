<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('point_settings', function (Blueprint $table) {
            $table->renameColumn('agency_commission_pct', 'agency_commission_amount');
        });
    }

    public function down(): void
    {
        Schema::table('point_settings', function (Blueprint $table) {
            $table->renameColumn('agency_commission_amount', 'agency_commission_pct');
        });
    }
};
