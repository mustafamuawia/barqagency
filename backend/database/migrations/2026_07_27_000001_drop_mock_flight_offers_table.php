<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('mock_flight_offers');
    }

    public function down(): void
    {
        Schema::create('mock_flight_offers', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique();
            $table->unsignedBigInteger('airline_id');
            $table->unsignedBigInteger('origin_airport_id');
            $table->unsignedBigInteger('destination_airport_id');
            $table->unsignedBigInteger('cabin_class_id');
            $table->decimal('price_oneway', 10, 2);
            $table->decimal('price_roundtrip', 10, 2)->nullable();
            $table->string('currency', 3)->default('USD');
            $table->unsignedInteger('duration_minutes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->foreign('airline_id')->references('id')->on('airlines')->onDelete('restrict');
            $table->foreign('origin_airport_id')->references('id')->on('airports')->onDelete('restrict');
            $table->foreign('destination_airport_id')->references('id')->on('airports')->onDelete('restrict');
            $table->foreign('cabin_class_id')->references('id')->on('cabin_classes')->onDelete('restrict');
        });
    }
};
