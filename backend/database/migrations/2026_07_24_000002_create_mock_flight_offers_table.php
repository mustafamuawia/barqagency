<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mock_flight_offers', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique();
            $table->foreignId('airline_id')->constrained('airlines')->onDelete('cascade');
            $table->foreignId('origin_airport_id')->constrained('airports')->onDelete('cascade');
            $table->foreignId('destination_airport_id')->constrained('airports')->onDelete('cascade');
            $table->foreignId('cabin_class_id')->constrained('cabin_classes')->onDelete('cascade');
            $table->decimal('price_oneway', 10, 2);
            $table->decimal('price_roundtrip', 10, 2)->nullable();
            $table->string('currency', 3)->default('USD');
            $table->unsignedInteger('duration_minutes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
            $table->index(['origin_airport_id', 'destination_airport_id'], 'mock_flight_offers_route_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mock_flight_offers');
    }
};
