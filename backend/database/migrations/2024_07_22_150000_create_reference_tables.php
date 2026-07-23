<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('countries', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('code', 2)->unique();
            $table->string('phone_code')->nullable();
            $table->timestamps();
        });

        Schema::create('cities', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedBigInteger('country_id');
            $table->timestamps();
            $table->foreign('country_id')->references('id')->on('countries')->onDelete('cascade');
            $table->unique(['name', 'country_id']);
        });

        Schema::create('airports', function (Blueprint $table) {
            $table->id();
            $table->string('code', 3)->unique();
            $table->string('name');
            $table->unsignedBigInteger('city_id');
            $table->unsignedBigInteger('country_id');
            $table->timestamps();
            $table->foreign('city_id')->references('id')->on('cities')->onDelete('restrict');
            $table->foreign('country_id')->references('id')->on('countries')->onDelete('restrict');
            $table->index(['country_id']);
        });

        Schema::create('airlines', function (Blueprint $table) {
            $table->id();
            $table->string('code', 2)->unique();
            $table->string('name');
            $table->string('call_sign')->nullable();
            $table->boolean('is_local')->default(false);
            $table->boolean('gds_enabled')->default(true);
            $table->enum('channel', ['gds', 'local'])->default('gds');
            $table->timestamps();
        });

        Schema::create('cabin_classes', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('code', 1)->unique();
            $table->timestamps();
        });

        Schema::create('passenger_types', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('currencies', function (Blueprint $table) {
            $table->id();
            $table->string('code', 3)->unique();
            $table->string('name');
            $table->string('symbol');
            $table->integer('decimal_places')->default(2);
            $table->timestamps();
        });

        Schema::create('agency_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agency_types');
        Schema::dropIfExists('currencies');
        Schema::dropIfExists('passenger_types');
        Schema::dropIfExists('cabin_classes');
        Schema::dropIfExists('airlines');
        Schema::dropIfExists('airports');
        Schema::dropIfExists('cities');
        Schema::dropIfExists('countries');
    }
};
