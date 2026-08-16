<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_segments', function (Blueprint $table) {
            // Departure hall, optional — often unknown until the airline confirms.
            $table->string('departure_terminal')->nullable()->after('departure_datetime');
        });

        // A round trip needs the return time, not just the date.
        Schema::table('orders', function (Blueprint $table) {
            $table->dateTime('return_date')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('order_segments', function (Blueprint $table) {
            $table->dropColumn('departure_terminal');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->date('return_date')->nullable()->change();
        });
    }
};
