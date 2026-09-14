<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('ticket_path')->nullable()->after('trip_type');
            $table->timestamp('ticket_uploaded_at')->nullable()->after('ticket_path');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['ticket_path', 'ticket_uploaded_at']);
        });
    }
};
