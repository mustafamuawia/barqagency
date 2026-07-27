<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('email_verification_token')->nullable()->unique()->after('email_verified_at');
        });

        DB::statement("ALTER TABLE travel_agencies MODIFY status ENUM('pending', 'active', 'suspended', 'inactive', 'rejected') DEFAULT 'pending'");

        Schema::table('travel_agencies', function (Blueprint $table) {
            $table->text('rejection_reason')->nullable()->after('status');
            $table->unsignedBigInteger('reviewed_by')->nullable()->after('rejection_reason');
            $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');

            $table->foreign('reviewed_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('travel_agencies', function (Blueprint $table) {
            $table->dropForeign(['reviewed_by']);
            $table->dropColumn(['rejection_reason', 'reviewed_by', 'reviewed_at']);
        });

        DB::statement("ALTER TABLE travel_agencies MODIFY status ENUM('active', 'suspended', 'inactive') DEFAULT 'active'");

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('email_verification_token');
        });
    }
};
