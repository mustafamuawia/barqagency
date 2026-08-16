<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_segments', function (Blueprint $table) {
            $table->dateTime('departure_datetime')->nullable()->change();
            $table->dateTime('arrival_datetime')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('order_segments', function (Blueprint $table) {
            $table->dateTime('departure_datetime')->nullable(false)->change();
            $table->dateTime('arrival_datetime')->nullable(false)->change();
        });
    }
};
