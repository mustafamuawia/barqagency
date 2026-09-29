<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The agency now only declares how many passengers of each type travel when it requests
 * the order — the names and passport details are filled in later, when it approves the
 * admin's quote. So the passenger rows exist with a type but no name for a while.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_passengers', function (Blueprint $table) {
            $table->string('first_name')->nullable()->change();
            $table->string('last_name')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('order_passengers', function (Blueprint $table) {
            $table->string('first_name')->nullable(false)->change();
            $table->string('last_name')->nullable(false)->change();
        });
    }
};
