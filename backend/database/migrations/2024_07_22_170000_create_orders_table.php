<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('label');
            $table->timestamps();
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique();
            $table->string('order_number')->unique();
            $table->unsignedBigInteger('agency_id');
            $table->unsignedBigInteger('status_id');
            $table->json('itinerary_details')->nullable();
            $table->decimal('estimated_price', 15, 3)->nullable();
            $table->integer('total_passengers')->default(0);
            $table->date('departure_date');
            $table->date('return_date')->nullable();
            $table->json('metadata')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('agency_id')->references('id')->on('travel_agencies')->onDelete('restrict');
            $table->foreign('status_id')->references('id')->on('order_statuses')->onDelete('restrict');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('restrict');

            $table->index(['agency_id']);
            $table->index(['status_id']);
        });

        Schema::create('order_segments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('airline_id');
            $table->unsignedBigInteger('departure_airport_id');
            $table->unsignedBigInteger('arrival_airport_id');
            $table->unsignedBigInteger('cabin_class_id');
            $table->dateTime('departure_datetime');
            $table->dateTime('arrival_datetime');
            $table->string('flight_number')->nullable();
            $table->string('aircraft_type')->nullable();
            $table->integer('duration_minutes')->nullable();
            $table->integer('sequence')->default(1);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
            $table->foreign('airline_id')->references('id')->on('airlines')->onDelete('restrict');
            $table->foreign('departure_airport_id')->references('id')->on('airports')->onDelete('restrict');
            $table->foreign('arrival_airport_id')->references('id')->on('airports')->onDelete('restrict');
            $table->foreign('cabin_class_id')->references('id')->on('cabin_classes')->onDelete('restrict');

            $table->index(['order_id']);
        });

        Schema::create('order_passengers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('passenger_type_id');
            $table->string('title')->nullable();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('date_of_birth')->nullable();
            $table->string('passport_number')->nullable();
            $table->date('passport_expiry')->nullable();
            $table->string('nationality')->nullable();
            $table->integer('sequence')->default(1);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
            $table->foreign('passenger_type_id')->references('id')->on('passenger_types')->onDelete('restrict');

            $table->index(['order_id']);
        });

        Schema::create('quote_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('label');
            $table->timestamps();
        });

        Schema::create('quotes', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique();
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('status_id');
            $table->decimal('base_price', 15, 3);
            $table->decimal('taxes', 15, 3)->default(0);
            $table->decimal('fees', 15, 3)->default(0);
            $table->decimal('total_price', 15, 3);
            $table->string('currency_code', 3)->default('USD');
            $table->text('remarks')->nullable();
            $table->dateTime('valid_until')->nullable();
            $table->json('pricing_details')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('order_id')->references('id')->on('orders')->onDelete('restrict');
            $table->foreign('status_id')->references('id')->on('quote_statuses')->onDelete('restrict');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('restrict');

            $table->index(['order_id']);
        });

        Schema::create('order_status_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('from_status_id')->nullable();
            $table->unsignedBigInteger('to_status_id');
            $table->text('reason')->nullable();
            $table->json('metadata')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
            $table->foreign('from_status_id')->references('id')->on('order_statuses')->onDelete('set null');
            $table->foreign('to_status_id')->references('id')->on('order_statuses')->onDelete('restrict');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('restrict');

            $table->index(['order_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_status_histories');
        Schema::dropIfExists('quotes');
        Schema::dropIfExists('quote_statuses');
        Schema::dropIfExists('order_passengers');
        Schema::dropIfExists('order_segments');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('order_statuses');
    }
};
