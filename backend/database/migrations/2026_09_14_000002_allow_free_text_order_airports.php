<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_segments', function (Blueprint $table) {
            $table->foreignId('departure_airport_id')->nullable()->change();
            $table->foreignId('arrival_airport_id')->nullable()->change();
            $table->string('departure_airport_name')->nullable()->after('departure_airport_id');
            $table->string('arrival_airport_name')->nullable()->after('arrival_airport_id');
        });
    }

    public function down(): void
    {
        Schema::table('order_segments', function (Blueprint $table) {
            $table->dropColumn(['departure_airport_name', 'arrival_airport_name']);
            $table->foreignId('departure_airport_id')->nullable(false)->change();
            $table->foreignId('arrival_airport_id')->nullable(false)->change();
        });
    }
};
