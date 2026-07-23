# Phase 1: Project Setup & Database Design

**Duration:** Week 1  
**Priority:** HIGH  
**Objective:** Establish foundation for all future work  
**Owner:** Backend Lead

---

## Overview

Phase 1 focuses on setting up the Laravel project structure, configuring the database, creating all 48 tables via migrations, and pre-populating reference data. This phase is critical—all subsequent phases depend on this foundation.

---

## Section 1: Initial Project Setup

### 1.1 Environment Configuration

Create `.env` file from `.env.example`:

```bash
cp backend/.env.example backend/.env
```

Configure database settings in `.env`:

```env
APP_NAME="Barqa Travel"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=barqa_travel
DB_USERNAME=root
DB_PASSWORD=
DB_CHARSET=utf8mb4
DB_COLLATION=utf8mb4_unicode_ci

QUEUE_CONNECTION=sync
CACHE_DRIVER=file
SESSION_DRIVER=cookie
```

Generate application key:

```bash
cd backend
php artisan key:generate
```

### 1.2 Create Database

```bash
mysql -u root -p -e "CREATE DATABASE barqa_travel CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

Verify connection:

```bash
php artisan tinker
>>> DB::connection()->getPdo();
# Should return: PDOConnection object
```

---

## Section 2: Create Database Migrations

### 2.1 Identity & Access Tables

**Create User Roles Table:**

```bash
php artisan make:migration create_user_types_table
```

```php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // super_admin, admin, b2b, b2c
            $table->string('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_types');
    }
};
```

**Create Users Table:**

```bash
php artisan make:migration create_users_table --create=users
```

```php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->unsignedBigInteger('user_type_id');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->softDeletes();
            $table->timestamps();

            // Foreign keys
            $table->foreign('user_type_id')->references('id')->on('user_types')->onDelete('restrict');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('deleted_by')->references('id')->on('users')->onDelete('set null');

            // Indexes
            $table->index(['email']);
            $table->index(['user_type_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
```

### 2.2 Travel Agencies Tables

**Create Agency Types:**

```bash
php artisan make:migration create_agency_types_table
```

```php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agency_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // e.g., corporate, individual, group
            $table->string('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agency_types');
    }
};
```

**Create Travel Agencies:**

```bash
php artisan make:migration create_travel_agencies_table
```

```php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('travel_agencies', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->unsignedBigInteger('agency_type_id');
            $table->string('license_number')->nullable();
            $table->string('tax_id')->nullable();
            $table->enum('status', ['active', 'suspended', 'inactive'])->default('active');
            $table->decimal('credit_limit', 15, 2)->default(0);
            $table->json('metadata')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->softDeletes();
            $table->timestamps();

            // Foreign keys
            $table->foreign('agency_type_id')->references('id')->on('agency_types')->onDelete('restrict');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('deleted_by')->references('id')->on('users')->onDelete('set null');

            // Indexes
            $table->index(['email']);
            $table->index(['status']);
            $table->index(['agency_type_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('travel_agencies');
    }
};
```

**Create Agency Users (pivot table):**

```bash
php artisan make:migration create_agency_users_table
```

```php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agency_users', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('agency_id');
            $table->unsignedBigInteger('user_id');
            $table->enum('role', ['owner', 'manager', 'staff'])->default('staff');
            $table->timestamps();

            // Foreign keys
            $table->foreign('agency_id')->references('id')->on('travel_agencies')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            // Unique constraint
            $table->unique(['agency_id', 'user_id']);

            // Indexes
            $table->index(['agency_id']);
            $table->index(['user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agency_users');
    }
};
```

### 2.3 Reference Tables

Create countries, cities, airports, airlines, cabin classes, currencies:

```bash
php artisan make:migration create_reference_tables
```

```php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Countries
        Schema::create('countries', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('code', 2)->unique();
            $table->string('phone_code')->nullable();
            $table->timestamps();
        });

        // Cities
        Schema::create('cities', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedBigInteger('country_id');
            $table->timestamps();
            $table->foreign('country_id')->references('id')->on('countries')->onDelete('cascade');
            $table->unique(['name', 'country_id']);
        });

        // Airports
        Schema::create('airports', function (Blueprint $table) {
            $table->id();
            $table->string('code', 3)->unique(); // IATA code (e.g., KRT, DXB)
            $table->string('name');
            $table->unsignedBigInteger('city_id');
            $table->unsignedBigInteger('country_id');
            $table->timestamps();
            $table->foreign('city_id')->references('id')->on('cities')->onDelete('restrict');
            $table->foreign('country_id')->references('id')->on('countries')->onDelete('restrict');
            $table->index(['country_id']);
        });

        // Airlines
        Schema::create('airlines', function (Blueprint $table) {
            $table->id();
            $table->string('code', 2)->unique(); // IATA code (e.g., BA, SV)
            $table->string('name');
            $table->string('call_sign')->nullable();
            $table->boolean('is_local')->default(false); // For Sudanese carriers
            $table->boolean('gds_enabled')->default(true);
            $table->enum('channel', ['gds', 'local'])->default('gds');
            $table->timestamps();
        });

        // Cabin Classes
        Schema::create('cabin_classes', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // Economy, Business, First
            $table->string('code', 1)->unique(); // Y, J, F
            $table->timestamps();
        });

        // Currencies
        Schema::create('currencies', function (Blueprint $table) {
            $table->id();
            $table->string('code', 3)->unique(); // USD, SDG, AED
            $table->string('name');
            $table->string('symbol');
            $table->integer('decimal_places')->default(2);
            $table->timestamps();
        });

        // Passenger Types
        Schema::create('passenger_types', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // ADT, CHD, INF
            $table->string('name');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('passenger_types');
        Schema::dropIfExists('currencies');
        Schema::dropIfExists('cabin_classes');
        Schema::dropIfExists('airlines');
        Schema::dropIfExists('airports');
        Schema::dropIfExists('cities');
        Schema::dropIfExists('countries');
    }
};
```

### 2.4 Booking Tables

Create orders, order segments, order passengers, quotes, and tickets:

```bash
php artisan make:migration create_booking_tables
```

```php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Order Statuses
        Schema::create('order_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // pending_pricing, pricing_sent, approved, confirmed, cancelled
            $table->string('label');
            $table->timestamps();
        });

        // Orders (main booking table)
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique();
            $table->string('order_number')->unique(); // ORD-DDMMYY-XXXX
            $table->unsignedBigInteger('agency_id');
            $table->unsignedBigInteger('status_id');
            $table->json('itinerary_details')->nullable(); // Temporary storage before segments
            $table->decimal('estimated_price', 15, 3)->nullable();
            $table->integer('total_passengers')->default(0);
            $table->date('departure_date');
            $table->date('return_date')->nullable();
            $table->json('metadata')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->softDeletes();
            $table->timestamps();

            // Foreign keys
            $table->foreign('agency_id')->references('id')->on('travel_agencies')->onDelete('restrict');
            $table->foreign('status_id')->references('id')->on('order_statuses')->onDelete('restrict');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('restrict');

            // Indexes
            $table->index(['agency_id']);
            $table->index(['status_id']);
            $table->index(['order_number']);
            $table->index(['created_at']);
        });

        // Order Segments (flight legs)
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
            $table->integer('sequence')->default(1); // 1st leg, 2nd leg, etc.
            $table->json('metadata')->nullable();
            $table->timestamps();

            // Foreign keys
            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
            $table->foreign('airline_id')->references('id')->on('airlines')->onDelete('restrict');
            $table->foreign('departure_airport_id')->references('id')->on('airports')->onDelete('restrict');
            $table->foreign('arrival_airport_id')->references('id')->on('airports')->onDelete('restrict');
            $table->foreign('cabin_class_id')->references('id')->on('cabin_classes')->onDelete('restrict');

            // Indexes
            $table->index(['order_id']);
            $table->index(['sequence']);
        });

        // Order Passengers
        Schema::create('order_passengers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('passenger_type_id');
            $table->string('title')->nullable(); // Mr, Mrs, Ms
            $table->string('first_name');
            $table->string('last_name');
            $table->string('date_of_birth')->nullable();
            $table->string('passport_number')->nullable();
            $table->date('passport_expiry')->nullable();
            $table->string('nationality')->nullable();
            $table->integer('sequence')->default(1);
            $table->json('metadata')->nullable();
            $table->timestamps();

            // Foreign keys
            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
            $table->foreign('passenger_type_id')->references('id')->on('passenger_types')->onDelete('restrict');

            // Indexes
            $table->index(['order_id']);
        });

        // Quote Statuses
        Schema::create('quote_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // sent, accepted, rejected, expired
            $table->timestamps();
        });

        // Quotes (price proposals)
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

            // Foreign keys
            $table->foreign('order_id')->references('id')->on('orders')->onDelete('restrict');
            $table->foreign('status_id')->references('id')->on('quote_statuses')->onDelete('restrict');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('restrict');

            // Indexes
            $table->index(['order_id']);
            $table->index(['status_id']);
        });

        // Ticket Statuses
        Schema::create('ticket_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // issued, used, cancelled, refunded
            $table->timestamps();
        });

        // Tickets (issued tickets)
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique();
            $table->string('ticket_number')->unique();
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('passenger_id');
            $table->unsignedBigInteger('status_id');
            $table->decimal('price', 15, 3);
            $table->string('currency_code', 3)->default('USD');
            $table->dateTime('issue_date');
            $table->dateTime('validity_date')->nullable();
            $table->json('metadata')->nullable();
            $table->softDeletes();
            $table->timestamps();

            // Foreign keys
            $table->foreign('order_id')->references('id')->on('orders')->onDelete('restrict');
            $table->foreign('status_id')->references('id')->on('ticket_statuses')->onDelete('restrict');

            // Indexes
            $table->index(['order_id']);
            $table->index(['ticket_number']);
            $table->index(['status_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tickets');
        Schema::dropIfExists('ticket_statuses');
        Schema::dropIfExists('quotes');
        Schema::dropIfExists('quote_statuses');
        Schema::dropIfExists('order_passengers');
        Schema::dropIfExists('order_segments');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('order_statuses');
    }
};
```

### 2.5 Financial Tables

Create wallets, transactions, invoices, and refunds:

```bash
php artisan make:migration create_financial_tables
```

```php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Wallets (1:1 with agencies)
        Schema::create('wallets', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique();
            $table->unsignedBigInteger('agency_id')->unique();
            $table->decimal('balance', 15, 3)->default(0); // Cached value
            $table->decimal('paid_balance', 15, 3)->default(0);
            $table->decimal('unpaid_balance', 15, 3)->default(0);
            $table->decimal('credit_limit', 15, 3)->default(0);
            $table->json('metadata')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            // Foreign keys
            $table->foreign('agency_id')->references('id')->on('travel_agencies')->onDelete('restrict');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
        });

        // Wallet Transaction Types
        Schema::create('wallet_transaction_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // topup, booking_debit, refund_credit, adjustment
            $table->string('label');
            $table->timestamps();
        });

        // Wallet Transactions (append-only log)
        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique();
            $table->unsignedBigInteger('wallet_id');
            $table->unsignedBigInteger('transaction_type_id');
            $table->decimal('amount', 15, 3);
            $table->decimal('balance_after', 15, 3); // Balance after transaction
            $table->string('reference_type')->nullable(); // Order, Invoice, Refund
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->timestamps();

            // Foreign keys
            $table->foreign('wallet_id')->references('id')->on('wallets')->onDelete('restrict');
            $table->foreign('transaction_type_id')->references('id')->on('wallet_transaction_types')->onDelete('restrict');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('restrict');

            // Indexes - immutable, so only order matters
            $table->index(['wallet_id']);
            $table->index(['created_at']);
        });

        // Invoices
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique();
            $table->string('invoice_number')->unique();
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('agency_id');
            $table->unsignedBigInteger('wallet_transaction_id')->nullable();
            $table->decimal('subtotal', 15, 3);
            $table->decimal('tax', 15, 3)->default(0);
            $table->decimal('total', 15, 3);
            $table->string('currency_code', 3)->default('USD');
            $table->enum('status', ['draft', 'sent', 'paid', 'overdue', 'cancelled'])->default('draft');
            $table->date('issue_date');
            $table->date('due_date')->nullable();
            $table->date('paid_date')->nullable();
            $table->text('notes')->nullable();
            $table->softDeletes();
            $table->timestamps();

            // Foreign keys
            $table->foreign('order_id')->references('id')->on('orders')->onDelete('restrict');
            $table->foreign('agency_id')->references('id')->on('travel_agencies')->onDelete('restrict');
            $table->foreign('wallet_transaction_id')->references('id')->on('wallet_transactions')->onDelete('set null');

            // Indexes
            $table->index(['agency_id']);
            $table->index(['invoice_number']);
            $table->index(['status']);
        });

        // Invoice Items (service line items)
        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('invoice_id');
            $table->string('description');
            $table->integer('quantity')->default(1);
            $table->decimal('unit_price', 15, 3);
            $table->decimal('amount', 15, 3); // quantity * unit_price
            $table->timestamps();

            // Foreign keys
            $table->foreign('invoice_id')->references('id')->on('invoices')->onDelete('cascade');
        });

        // Refund Reasons
        Schema::create('refund_reasons', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // Refunds
        Schema::create('refunds', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique();
            $table->unsignedBigInteger('ticket_id');
            $table->unsignedBigInteger('agency_id');
            $table->unsignedBigInteger('refund_reason_id')->nullable();
            $table->decimal('ticket_price', 15, 3);
            $table->decimal('supplier_penalty', 15, 3)->default(0);
            $table->decimal('platform_fee', 15, 3)->default(0);
            $table->decimal('refund_amount', 15, 3); // ticket_price - supplier_penalty - platform_fee
            $table->string('currency_code', 3)->default('USD');
            $table->enum('status', ['pending', 'approved', 'credited', 'rejected'])->default('pending');
            $table->text('remarks')->nullable();
            $table->dateTime('requested_at');
            $table->dateTime('approved_at')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->softDeletes();
            $table->timestamps();

            // Foreign keys
            $table->foreign('ticket_id')->references('id')->on('tickets')->onDelete('restrict');
            $table->foreign('agency_id')->references('id')->on('travel_agencies')->onDelete('restrict');
            $table->foreign('refund_reason_id')->references('id')->on('refund_reasons')->onDelete('set null');
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');

            // Indexes
            $table->index(['agency_id']);
            $table->index(['status']);
            $table->index(['requested_at']);
        });

        // Credit Notes (issued on refund approval)
        Schema::create('credit_notes', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique();
            $table->string('credit_note_number')->unique();
            $table->unsignedBigInteger('refund_id');
            $table->unsignedBigInteger('invoice_id');
            $table->decimal('amount', 15, 3);
            $table->string('currency_code', 3)->default('USD');
            $table->date('issue_date');
            $table->text('notes')->nullable();
            $table->softDeletes();
            $table->timestamps();

            // Foreign keys
            $table->foreign('refund_id')->references('id')->on('refunds')->onDelete('restrict');
            $table->foreign('invoice_id')->references('id')->on('invoices')->onDelete('restrict');

            // Indexes
            $table->index(['refund_id']);
            $table->index(['credit_note_number']);
        });

        // Payment Methods
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // bank_transfer, credit_card, check
            $table->string('code')->unique();
            $table->timestamps();
        });

        // Payments
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique();
            $table->unsignedBigInteger('invoice_id');
            $table->unsignedBigInteger('payment_method_id');
            $table->decimal('amount', 15, 3);
            $table->string('currency_code', 3)->default('USD');
            $table->enum('status', ['pending', 'confirmed', 'failed'])->default('pending');
            $table->string('reference_number')->nullable();
            $table->date('payment_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            // Foreign keys
            $table->foreign('invoice_id')->references('id')->on('invoices')->onDelete('restrict');
            $table->foreign('payment_method_id')->references('id')->on('payment_methods')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
        Schema::dropIfExists('payment_methods');
        Schema::dropIfExists('credit_notes');
        Schema::dropIfExists('refunds');
        Schema::dropIfExists('refund_reasons');
        Schema::dropIfExists('invoice_items');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('wallet_transactions');
        Schema::dropIfExists('wallet_transaction_types');
        Schema::dropIfExists('wallets');
    }
};
```

### 2.6 Audit & Configuration Tables

```bash
php artisan make:migration create_audit_and_config_tables
```

```php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Order Status Histories
        Schema::create('order_status_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('from_status_id')->nullable();
            $table->unsignedBigInteger('to_status_id');
            $table->text('reason')->nullable();
            $table->json('metadata')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->timestamps();

            // Foreign keys
            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
            $table->foreign('from_status_id')->references('id')->on('order_statuses')->onDelete('set null');
            $table->foreign('to_status_id')->references('id')->on('order_statuses')->onDelete('restrict');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('restrict');

            // Indexes
            $table->index(['order_id']);
            $table->index(['created_at']);
        });

        // Wallet Balance Snapshots (for audit trail)
        Schema::create('wallet_balance_snapshots', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('wallet_id');
            $table->decimal('balance', 15, 3);
            $table->decimal('paid_balance', 15, 3);
            $table->decimal('unpaid_balance', 15, 3);
            $table->timestamps();

            // Foreign keys
            $table->foreign('wallet_id')->references('id')->on('wallets')->onDelete('cascade');

            // Indexes
            $table->index(['wallet_id']);
            $table->index(['created_at']);
        });

        // Login Histories
        Schema::create('login_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamp('logged_in_at');
            $table->timestamp('logged_out_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            // Foreign keys
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            // Indexes
            $table->index(['user_id']);
            $table->index(['logged_in_at']);
        });

        // Audit Logs
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique();
            $table->string('action'); // create, update, delete, approve, etc.
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            // Foreign keys
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');

            // Indexes
            $table->index(['model_type', 'model_id']);
            $table->index(['action']);
            $table->index(['created_at']);
        });

        // Settings
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->json('value')->nullable();
            $table->string('type')->default('string'); // string, integer, boolean, json
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // Feature Flags
        Schema::create('feature_flags', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->boolean('enabled')->default(false);
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feature_flags');
        Schema::dropIfExists('settings');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('login_histories');
        Schema::dropIfExists('wallet_balance_snapshots');
        Schema::dropIfExists('order_status_histories');
    }
};
```

---

## Section 3: Run Migrations

Execute all migrations:

```bash
cd backend
php artisan migrate
```

Verify tables were created:

```bash
php artisan tinker
>>> Schema::getTableListing();
# Should show all 48 tables
```

---

## Section 4: Create Seeders

### 4.1 Reference Data Seeders

```bash
php artisan make:seeder CountriesSeeder
```

```php
<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CountriesSeeder extends Seeder
{
    public function run()
    {
        $countries = [
            ['name' => 'Sudan', 'code' => 'SD', 'phone_code' => '+249'],
            ['name' => 'United Arab Emirates', 'code' => 'AE', 'phone_code' => '+971'],
            ['name' => 'Saudi Arabia', 'code' => 'SA', 'phone_code' => '+966'],
            ['name' => 'Egypt', 'code' => 'EG', 'phone_code' => '+20'],
            ['name' => 'Qatar', 'code' => 'QA', 'phone_code' => '+974'],
            // Add more...
        ];

        DB::table('countries')->insert($countries);
    }
}
```

Create similar seeders for:
- `AirlinesSeeder` (include Badr, Tarco, Sudan Airways with `is_local=true`)
- `AirportsSeeder`
- `CurrenciesSeeder`
- `CabinClassesSeeder`
- `PassengerTypesSeeder`
- `UserTypesSeeder`
- `AgencyTypesSeeder`
- `OrderStatusesSeeder`
- `QuoteStatusesSeeder`
- `TicketStatusesSeeder`
- `WalletTransactionTypesSeeder`
- `PaymentMethodsSeeder`
- `RefundReasonsSeeder`

### 4.2 User & Agency Seeders

```bash
php artisan make:seeder UsersSeeder
php artisan make:seeder TravelAgenciesSeeder
```

```php
<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\UserType;
use Illuminate\Support\Facades\Hash;

class UsersSeeder extends Seeder
{
    public function run()
    {
        $superAdminType = UserType::where('name', 'super_admin')->first();
        $adminType = UserType::where('name', 'admin')->first();

        // Super Admin
        User::create([
            'name' => 'Super Admin',
            'email' => 'super@barqa.local',
            'password' => Hash::make('password'),
            'user_type_id' => $superAdminType->id,
        ]);

        // Admin
        User::create([
            'name' => 'Admin User',
            'email' => 'admin@barqa.local',
            'password' => Hash::make('password'),
            'user_type_id' => $adminType->id,
        ]);
    }
}
```

### 4.3 Run All Seeders

```bash
php artisan db:seed
```

---

## Section 5: Verification Checklist

- [x] Database created
- [x] All 48 tables created
- [x] Foreign keys in place
- [x] Indexes created
- [x] Reference data seeded
- [x] Admin users created
- [x] Soft deletes configured
- [x] Audit columns added

**Next:** Proceed to Phase 2 for authentication setup
