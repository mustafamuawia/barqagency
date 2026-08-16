<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Locally added carriers often have no IATA code. The unique index stays put —
        // MySQL allows any number of NULLs in a unique column.
        Schema::table('airlines', function (Blueprint $table) {
            $table->string('code', 2)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('airlines', function (Blueprint $table) {
            $table->string('code', 2)->nullable(false)->change();
        });
    }
};
