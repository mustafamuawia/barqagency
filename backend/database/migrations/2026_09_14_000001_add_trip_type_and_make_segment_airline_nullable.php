<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('trip_type', 20)->default('one_way')->after('return_date');
        });

        // An agency requests a route; the administrator selects the carrier while pricing it.
        Schema::table('order_segments', function (Blueprint $table) {
            $table->foreignId('airline_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('order_segments', function (Blueprint $table) {
            $table->foreignId('airline_id')->nullable(false)->change();
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('trip_type');
        });
    }
};
