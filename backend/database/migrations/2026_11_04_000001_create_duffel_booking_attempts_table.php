<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('duffel_booking_attempts', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('agency_id')->constrained('travel_agencies');
            $table->unsignedBigInteger('created_by')->nullable(); // User who initiated the booking
            $table->string('offer_id');
            $table->string('idempotency_key', 36)->unique();
            $table->json('duffel_passengers');  // Full passenger array for Duffel retry
            $table->string('duffel_amount');
            $table->string('duffel_currency', 3);
            $table->decimal('sar_amount', 12, 3);
            // pending    — connection failed; booking status unknown; pending reconciliation
            // reconciled — booking confirmed; order created; wallet debited
            // failed     — booking definitively did not succeed; wallet never debited
            $table->string('status', 20)->default('pending');
            $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->unsignedSmallInteger('attempts')->default(1);
            $table->timestamp('last_attempted_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index(['agency_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('duffel_booking_attempts');
    }
};
