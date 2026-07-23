<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallets', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique();
            $table->unsignedBigInteger('agency_id')->unique();
            $table->decimal('balance', 15, 3)->default(0);
            $table->decimal('paid_balance', 15, 3)->default(0);
            $table->decimal('unpaid_balance', 15, 3)->default(0);
            $table->decimal('credit_limit', 15, 3)->default(0);
            $table->json('metadata')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->foreign('agency_id')->references('id')->on('travel_agencies')->onDelete('restrict');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
        });

        Schema::create('wallet_transaction_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('label');
            $table->timestamps();
        });

        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique();
            $table->unsignedBigInteger('wallet_id');
            $table->unsignedBigInteger('transaction_type_id');
            $table->decimal('amount', 15, 3);
            $table->decimal('balance_after', 15, 3);
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('wallet_id')->references('id')->on('wallets')->onDelete('restrict');
            $table->foreign('transaction_type_id')->references('id')->on('wallet_transaction_types')->onDelete('restrict');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('restrict');

            $table->index(['wallet_id']);
        });

        Schema::create('wallet_balance_snapshots', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('wallet_id');
            $table->decimal('balance', 15, 3);
            $table->decimal('paid_balance', 15, 3);
            $table->decimal('unpaid_balance', 15, 3);
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('wallet_id')->references('id')->on('wallets')->onDelete('cascade');

            $table->index(['wallet_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallet_balance_snapshots');
        Schema::dropIfExists('wallet_transactions');
        Schema::dropIfExists('wallet_transaction_types');
        Schema::dropIfExists('wallets');
    }
};
