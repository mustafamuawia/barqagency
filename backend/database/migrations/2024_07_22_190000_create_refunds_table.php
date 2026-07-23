<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('refund_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('label');
            $table->timestamps();
        });

        Schema::create('refunds', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique();
            $table->string('refund_number')->unique();
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('agency_id');
            $table->unsignedBigInteger('status_id');
            $table->decimal('original_amount', 15, 3);
            $table->decimal('penalty_amount', 15, 3)->default(0);
            $table->decimal('refund_amount', 15, 3);
            $table->string('currency_code', 3)->default('USD');
            $table->text('reason')->nullable();
            $table->text('admin_remarks')->nullable();
            $table->unsignedBigInteger('requested_by');
            $table->unsignedBigInteger('decided_by')->nullable();
            $table->timestamp('requested_at');
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();

            $table->foreign('order_id')->references('id')->on('orders')->onDelete('restrict');
            $table->foreign('agency_id')->references('id')->on('travel_agencies')->onDelete('restrict');
            $table->foreign('status_id')->references('id')->on('refund_statuses')->onDelete('restrict');
            $table->foreign('requested_by')->references('id')->on('users')->onDelete('restrict');
            $table->foreign('decided_by')->references('id')->on('users')->onDelete('set null');

            $table->index(['agency_id']);
            $table->index(['order_id']);
            $table->index(['status_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('refunds');
        Schema::dropIfExists('refund_statuses');
    }
};
