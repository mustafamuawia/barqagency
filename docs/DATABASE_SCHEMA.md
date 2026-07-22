# Database Schema Reference

**Project:** Advanced Travel Management Platform  
**Version:** 1.0  
**Database:** MySQL 8.0+  

---

## Table of Contents

1. [Core Entity Relationships](#core-entity-relationships)
2. [Standard System Columns](#standard-system-columns)
3. [Module Breakdown](#module-breakdown)
4. [Index Strategy](#index-strategy)

---

## Core Entity Relationships

```
AGENCY (1) ─── 1-1 ─── (1) WALLET
  │
  ├─ 1-N ─────────────────── ORDERS
  │                            │
  │                            ├─ 1-N ─ ORDER_SEGMENTS
  │                            ├─ 1-N ─ ORDER_PASSENGERS
  │                            ├─ 1-N ─ QUOTES
  │                            └─ 1-1 ─ TICKETS
  │
  ├─ 1-N ─────────────────── AGENCY_USERS
  │
  └─ 1-N ─────────────────── AGENCY_DOCUMENTS

WALLET (1) ──── 1-N ────── (∞) WALLET_TRANSACTIONS
               ├─ INVOICES
               └─ PAYMENTS

TICKET (1) ───── 1-N ────── (∞) REFUNDS
               └─ CREDIT_NOTES (1-1)

USERS (∞) ═════════ N-M ════════ ROLES
ROLES (∞) ═════════ N-M ════════ PERMISSIONS
USERS ─── N-M ─── TRAVEL_AGENCIES (agency_users)

AIRLINES (∞) ──── 1-N ────── ORDERS
               (via airline_id)
```

---

## Standard System Columns

Every business table includes these columns (managed via Traits):

```sql
-- Primary Keys
id BIGINT UNSIGNED AUTO_INCREMENT  -- Internal PK for joins
uuid CHAR(36) UNIQUE                -- Public identifier for APIs

-- Multi-tenancy (ready for future)
tenant_id BIGINT UNSIGNED NULL      -- For future multi-company

-- Status
status FK → *_statuses             -- Normalized status values

-- Audit Trail
created_by BIGINT UNSIGNED NULL    -- Who created this record
updated_by BIGINT UNSIGNED NULL    -- Who last updated
deleted_by BIGINT UNSIGNED NULL    -- Who soft-deleted
created_at TIMESTAMP               -- When created
updated_at TIMESTAMP               -- When last modified
deleted_at TIMESTAMP NULL          -- Soft delete marker

-- Flexible Fields
notes TEXT NULL                     -- Internal team notes
remarks TEXT NULL                   -- Notes visible to other party
metadata JSON NULL                  -- Future extensibility
```

**Why These Columns?**
- `id` + `uuid`: Fast joins internally, safe external IDs
- `tenant_id`: Ready for multi-company without painful migration
- `status` FK: Extensible without code changes
- Audit columns: Full accountability & compliance
- Soft delete: Preserves financial integrity
- `metadata`: Schema flexibility without migrations

---

## Module Breakdown

### ⭐ Important: Role-Based Access Control via Spatie

This project uses **Spatie Laravel Permission** for role-based access control (RBAC). Spatie automatically creates and manages the following tables:
- `roles` - Role definitions
- `permissions` - Permission definitions
- `role_user` - User role assignments (pivot)
- `permission_role` - Role permissions (pivot)

You do NOT need to create these tables manually—they're created by Spatie's migrations when you run `php artisan migrate`.

See [Spatie Documentation](https://spatie.be/docs/laravel-permission/v6/introduction) for API reference.

### 1. Identity & Access (4 Custom Tables + Spatie)

#### `users`
Core user accounts with role assignments.

```sql
CREATE TABLE users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid CHAR(36) UNIQUE,
    user_type_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(180) NOT NULL,
    email VARCHAR(180) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email_verified_at TIMESTAMP NULL,
    phone VARCHAR(20) NULL,
    -- Standard columns (§2)
    created_by BIGINT UNSIGNED NULL,
    updated_by BIGINT UNSIGNED NULL,
    deleted_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    
    KEY idx_email (email),
    KEY idx_user_type_id (user_type_id),
    FOREIGN KEY (user_type_id) REFERENCES user_types(id)
);
```

**Enum Values (user_types):**
- `super_admin` - Absolute system control
- `admin` - Platform operations
- `b2b` - Travel agency
- `b2c` - Individual customer (disabled in MVP)

---

#### `roles`, `permissions`, `permission_categories`
Granular permission system for admins.

```sql
CREATE TABLE roles (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) UNIQUE,           -- admin, moderator, etc.
    name VARCHAR(180),
    description TEXT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    -- Standard columns
    ...
);

CREATE TABLE permissions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(100) UNIQUE,          -- order:create, refund:approve
    name VARCHAR(180),
    description TEXT NULL,
    permission_category_id BIGINT UNSIGNED,
    -- Standard columns
    ...
);

CREATE TABLE permission_categories (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) UNIQUE,           -- orders, wallet, users
    name VARCHAR(180),
    sort_order INT DEFAULT 0,
    -- Standard columns
    ...
);

-- Pivot tables
CREATE TABLE role_user (
    role_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY (role_id, user_id)
);

CREATE TABLE permission_role (
    permission_id BIGINT UNSIGNED NOT NULL,
    role_id BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY (permission_id, role_id)
);
```

---

### 2. Agencies (4 Tables)

#### `travel_agencies`
Travel agency accounts with credit management.

```sql
CREATE TABLE travel_agencies (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid CHAR(36) UNIQUE,
    agency_type_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(180) NOT NULL,
    code VARCHAR(20) NOT NULL UNIQUE,  -- AGN-1042
    credit_limit DECIMAL(15,3) DEFAULT 0,
    currency_id BIGINT UNSIGNED,
    status VARCHAR(30) DEFAULT 'active',  -- active, suspended
    -- Standard columns
    created_by, updated_by, ..., metadata
    
    INDEX idx_status (status),
    INDEX idx_agency_type_id (agency_type_id),
    FOREIGN KEY (agency_type_id) REFERENCES agency_types(id),
    CHECK (credit_limit >= 0)
);
```

**Business Rules:**
- `code` is immutable after creation
- Raising `credit_limit` requires permission
- Soft delete only if has bookings (preserve history)
- Multi-currency ready (currency_id)

---

#### `agency_users`
Users who work at an agency (N:M with roles per agency).

```sql
CREATE TABLE agency_users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    agency_id BIGINT UNSIGNED NOT NULL,
    role_id BIGINT UNSIGNED NULL,     -- Role at this agency
    is_primary BOOLEAN DEFAULT FALSE, -- Primary contact
    -- Standard columns
    ...
    
    UNIQUE KEY (user_id, agency_id),
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (agency_id) REFERENCES travel_agencies(id),
    FOREIGN KEY (role_id) REFERENCES roles(id)
);
```

---

### 3. Bookings (8 Tables)

#### `orders`
Ticket requests flowing through approval cycle.

```sql
CREATE TABLE orders (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid CHAR(36) UNIQUE,
    order_number VARCHAR(30) UNIQUE,   -- ORD-260721-0043
    agency_id BIGINT UNSIGNED NOT NULL,
    airline_id BIGINT UNSIGNED,        -- Routes to local/GDS
    channel ENUM('gds','local') NOT NULL,  -- Processing route
    status_id BIGINT UNSIGNED NOT NULL,
    final_price DECIMAL(15,3) NULL,    -- Filled on approval
    -- Standard columns
    created_by, updated_by, ..., remarks
    
    INDEX idx_agency_id (agency_id),
    INDEX idx_status_id (status_id),
    INDEX idx_airline_id (airline_id),
    FOREIGN KEY (agency_id) REFERENCES travel_agencies(id),
    FOREIGN KEY (airline_id) REFERENCES airlines(id),
    FOREIGN KEY (status_id) REFERENCES order_statuses(id)
);
```

**Status Flow:**
```
pending_pricing → pricing_sent → approved → confirmed → cancelled
```

**Business Rules:**
- After confirmed, route details become immutable
- Approval checks: `balance - final_price >= -credit_limit`
- Every status change → order_status_histories entry
- Channel determines processing path (local vs. API)

---

#### `order_segments`
Flight legs (flights can have multiple segments).

```sql
CREATE TABLE order_segments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id BIGINT UNSIGNED NOT NULL,
    sequence INT NOT NULL,
    departure_airport_id BIGINT UNSIGNED NOT NULL,
    arrival_airport_id BIGINT UNSIGNED NOT NULL,
    departure_at DATETIME NOT NULL,
    arrival_at DATETIME NOT NULL,
    cabin_class_id BIGINT UNSIGNED,
    -- Standard columns
    ...
    
    INDEX idx_order_id (order_id),
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
);
```

---

#### `order_passengers`
Traveler details for the order.

```sql
CREATE TABLE order_passengers (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id BIGINT UNSIGNED NOT NULL,
    passenger_type_id BIGINT UNSIGNED,  -- ADT, CHD, INF
    sequence INT NOT NULL,
    full_name VARCHAR(255) NOT NULL,
    date_of_birth DATE NULL,
    passport_number VARCHAR(20) NULL,
    nationality VARCHAR(3) NULL,        -- ISO code
    -- Standard columns
    ...
    
    INDEX idx_order_id (order_id),
    FULLTEXT KEY ft_name (full_name),
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
);
```

---

#### `quotes`
Price proposals before approval.

```sql
CREATE TABLE quotes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id BIGINT UNSIGNED NOT NULL,
    priced_by BIGINT UNSIGNED,         -- Admin who created
    price DECIMAL(15,3),
    itinerary JSON,                    -- Serialized itinerary
    status_id BIGINT UNSIGNED,
    valid_until DATETIME,
    -- Standard columns
    created_by, ...
    
    INDEX idx_order_id (order_id),
    FOREIGN KEY (order_id) REFERENCES orders(id),
    FOREIGN KEY (priced_by) REFERENCES users(id)
);
```

---

#### `tickets`
Issued tickets (1:1 with order after approval).

```sql
CREATE TABLE tickets (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid CHAR(36) UNIQUE,
    order_id BIGINT UNSIGNED NOT NULL UNIQUE,
    pnr VARCHAR(6),                    -- Passenger name record
    issued_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    -- Standard columns
    ...
    
    FOREIGN KEY (order_id) REFERENCES orders(id)
);
```

---

### 4. Finance & Wallet (10 Tables)

#### `wallets`
Agency ledger (1:1 with agency).

```sql
CREATE TABLE wallets (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    agency_id BIGINT UNSIGNED NOT NULL UNIQUE,
    balance DECIMAL(15,3) DEFAULT 0,           -- Cached value
    paid_total DECIMAL(15,3) DEFAULT 0,        -- Verified payments
    unpaid_total DECIMAL(15,3) DEFAULT 0,      -- Outstanding
    currency_id BIGINT UNSIGNED NOT NULL,
    -- Standard columns
    ...
    
    FOREIGN KEY (agency_id) REFERENCES travel_agencies(id),
    FOREIGN KEY (currency_id) REFERENCES currencies(id)
);
```

**Business Rules:**
- `balance` is computed from transactions (never edited directly)
- Negative balance allowed only if `balance >= -agency.credit_limit`
- Changes go through wallet_transactions (source of truth)
- Updated inside DB::transaction with row locks

---

#### `wallet_transactions`
Immutable transaction log (append-only).

```sql
CREATE TABLE wallet_transactions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    wallet_id BIGINT UNSIGNED NOT NULL,
    type_id BIGINT UNSIGNED NOT NULL,          -- topup, booking, refund
    direction ENUM('credit','debit') NOT NULL,
    amount DECIMAL(15,3) NOT NULL,             -- Always positive
    balance_after DECIMAL(15,3),               -- For audit
    reference_type VARCHAR(60),                -- Order, Refund, etc.
    reference_id BIGINT UNSIGNED,
    created_by BIGINT UNSIGNED,
    created_at TIMESTAMP,
    metadata JSON NULL,
    
    PRIMARY KEY (id),
    UNIQUE KEY (wallet_id, created_at),        -- For statements
    KEY idx_reference (reference_type, reference_id),
    FOREIGN KEY (wallet_id) REFERENCES wallets(id)
);
```

**Immutability Rule:** Never updated or deleted—only appended. Corrections via reversing entries.

---

#### `invoices`
Billing document (created on order confirmation).

```sql
CREATE TABLE invoices (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid CHAR(36) UNIQUE,
    invoice_number VARCHAR(30) UNIQUE,
    order_id BIGINT UNSIGNED NOT NULL,
    agency_id BIGINT UNSIGNED NOT NULL,
    total_amount DECIMAL(15,3),
    tax_amount DECIMAL(15,3) DEFAULT 0,
    currency_id BIGINT UNSIGNED,
    status VARCHAR(30) DEFAULT 'issued',       -- issued, paid, cancelled
    issued_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    due_at TIMESTAMP NULL,
    -- Standard columns
    ...
    
    INDEX idx_order_id (order_id),
    INDEX idx_agency_id (agency_id),
    FOREIGN KEY (order_id) REFERENCES orders(id),
    FOREIGN KEY (agency_id) REFERENCES travel_agencies(id)
);
```

---

#### `invoice_items`
Service line items per invoice.

```sql
CREATE TABLE invoice_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    invoice_id BIGINT UNSIGNED NOT NULL,
    description VARCHAR(255),
    quantity INT DEFAULT 1,
    unit_price DECIMAL(15,3),
    amount DECIMAL(15,3),                      -- quantity × unit_price
    tax_amount DECIMAL(15,3) DEFAULT 0,
    -- Standard columns
    ...
    
    FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE
);
```

---

#### `refunds`
Refund requests with approval flow.

```sql
CREATE TABLE refunds (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid CHAR(36) UNIQUE,
    ticket_id BIGINT UNSIGNED NOT NULL,
    agency_id BIGINT UNSIGNED NOT NULL,
    refund_reason_id BIGINT UNSIGNED,
    requested_amount DECIMAL(15,3),
    approved_amount DECIMAL(15,3) NULL,
    supplier_penalty DECIMAL(15,3) DEFAULT 0, -- Carrier charge
    platform_fee DECIMAL(15,3) DEFAULT 0,     -- Our fee
    net_refund DECIMAL(15,3) NULL,             -- approved - penalties
    status VARCHAR(30) DEFAULT 'pending',      -- pending, approved, credited
    approved_by BIGINT UNSIGNED NULL,
    approved_at TIMESTAMP NULL,
    -- Standard columns
    ...
    
    INDEX idx_ticket_id (ticket_id),
    INDEX idx_agency_id (agency_id),
    INDEX idx_status (status),
    FOREIGN KEY (ticket_id) REFERENCES tickets(id),
    FOREIGN KEY (agency_id) REFERENCES travel_agencies(id),
    FOREIGN KEY (approved_by) REFERENCES users(id)
);
```

**Business Rules:**
- FR-REF-02: Clearly separate supplier_penalty from platform_fee
- FR-REF-06: Mandatory admin approval for Sudanese carriers
- net_refund = approved_amount - supplier_penalty (platform keeps fee)

---

#### `credit_notes`
Credit document issued per refund (1:1).

```sql
CREATE TABLE credit_notes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid CHAR(36) UNIQUE,
    note_number VARCHAR(30) UNIQUE,
    refund_id BIGINT UNSIGNED NOT NULL UNIQUE,
    invoice_id BIGINT UNSIGNED NOT NULL,      -- Original invoice
    amount DECIMAL(15,3),
    issued_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    -- Standard columns
    ...
    
    FOREIGN KEY (refund_id) REFERENCES refunds(id),
    FOREIGN KEY (invoice_id) REFERENCES invoices(id)
);
```

**Purpose:** Complete audit chain from refund to original invoice.

---

### 5. Reference/Lookup Tables (11 Tables)

These tables are **read-heavy, cached, and seldom modified**. All follow the pattern:

```sql
CREATE TABLE lookup_table (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) UNIQUE NOT NULL,          -- Stable identifier
    name VARCHAR(180) NOT NULL,
    name_en VARCHAR(180) NULL,                 -- English translation
    description TEXT NULL,
    is_active BOOLEAN DEFAULT TRUE,            -- Disable without deleting
    sort_order INT DEFAULT 0,
    -- Standard columns
    ...
);
```

#### Lookup Tables
- `countries` - ISO codes, phone prefixes
- `cities` - Linked to country_id
- `airports` - IATA/ICAO codes (unique), linked to city
- `airlines` - `iata_code`, `is_local`, `gds_enabled` flags
- `cabin_classes` - Economy, Business, First
- `passenger_types` - ADT (adult), CHD (child), INF (infant) with age bounds
- `currencies` - Code (SDG, USD), exchange_rate
- `payment_methods` - bank_transfer, wallet, gateway
- `refund_reasons` - Customer request, airline error, etc.
- `wallet_transaction_types` - topup, booking_debit, refund_credit, adjustment
- `notification_types` - Email/SMS templates per event

**Caching:** These tables change rarely. Cache them in-app to reduce queries.

---

### 6. Audit & History (4 Tables)

#### `audit_logs`
Complete change history for critical tables.

```sql
CREATE TABLE audit_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED,
    auditable_type VARCHAR(255),              -- Polymorphic: Order, Refund, etc.
    auditable_id BIGINT UNSIGNED,
    event VARCHAR(30),                        -- created, updated, deleted
    old_values JSON,                          -- Previous state
    new_values JSON,                          -- New state
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    created_at TIMESTAMP,
    
    UNIQUE KEY (auditable_type, auditable_id, created_at),
    FOREIGN KEY (user_id) REFERENCES users(id)
);
```

**Mechanism:** Laravel Auditable trait automatically captures changes to observed models.

---

#### `order_status_histories`
Every order status transition.

```sql
CREATE TABLE order_status_histories (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id BIGINT UNSIGNED NOT NULL,
    from_status_id BIGINT UNSIGNED,
    to_status_id BIGINT UNSIGNED NOT NULL,
    changed_by BIGINT UNSIGNED,
    changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    reason TEXT NULL,
    
    KEY idx_order_id (order_id),
    FOREIGN KEY (order_id) REFERENCES orders(id)
);
```

---

#### `wallet_balance_snapshots`
Periodic balance snapshots (daily/monthly).

```sql
CREATE TABLE wallet_balance_snapshots (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    wallet_id BIGINT UNSIGNED NOT NULL,
    balance DECIMAL(15,3),
    paid_total DECIMAL(15,3),
    unpaid_total DECIMAL(15,3),
    snapshot_at TIMESTAMP,
    
    KEY idx_wallet_id (wallet_id),
    KEY idx_snapshot_at (snapshot_at)
);
```

**Purpose:** Fast reconciliation and total validation without recalculating from transactions.

---

#### `login_histories`
User login records.

```sql
CREATE TABLE login_histories (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    logged_in_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    logged_out_at TIMESTAMP NULL,
    
    KEY idx_user_id (user_id),
    KEY idx_logged_in_at (logged_in_at)
);
```

---

### 7. Config & System (6 Tables)

#### `settings`
Key/value configuration (managed from control panel).

```sql
CREATE TABLE settings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    key VARCHAR(255) UNIQUE NOT NULL,
    value LONGTEXT,
    type VARCHAR(50),                         -- string, int, boolean, json
    group VARCHAR(50),                        -- billing, notifications, etc.
    description TEXT NULL,
    is_public BOOLEAN DEFAULT FALSE,
    -- Standard columns
    ...
);
```

**Example Keys:**
- `app.company_name` → "Barqa"
- `booking.mock_pricing_enabled` → true
- `refund.auto_credit_days` → 3

---

#### `feature_flags`
Enable/disable features without code changes (plug-and-play).

```sql
CREATE TABLE feature_flags (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(100) UNIQUE NOT NULL,       -- b2c_enabled, gds_enabled
    name VARCHAR(255),
    description TEXT,
    is_enabled BOOLEAN DEFAULT FALSE,
    rollout_percentage INT DEFAULT 0,        -- 0-100, gradual rollout
    enabled_at TIMESTAMP NULL,
    -- Standard columns
    ...
);
```

**Purpose:** Enable B2C, GDS, payment gateways from UI without redeploying.

---

#### `notifications`
Notification records per event.

```sql
CREATE TABLE notifications (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid CHAR(36) UNIQUE,
    notifiable_type VARCHAR(255),            -- User, Agency
    notifiable_id BIGINT UNSIGNED,
    type VARCHAR(100),                       -- OrderCreated, RefundApproved
    data JSON,                                -- Event data
    read_at TIMESTAMP NULL,
    created_at TIMESTAMP,
    
    KEY idx_notifiable (notifiable_type, notifiable_id),
    KEY idx_read_at (read_at)
);
```

---

#### `jobs` & `failed_jobs`
Laravel queue system (async tasks).

```sql
CREATE TABLE jobs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    queue VARCHAR(255) NOT NULL,
    payload LONGTEXT NOT NULL,
    attempts TINYINT UNSIGNED DEFAULT 0,
    reserved_at BIGINT UNSIGNED NULL,
    available_at BIGINT UNSIGNED NOT NULL,
    created_at BIGINT UNSIGNED NOT NULL,
    
    KEY idx_queue (queue),
    KEY idx_reserved_at (reserved_at)
);

CREATE TABLE failed_jobs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid VARCHAR(255) UNIQUE,
    connection VARCHAR(255),
    queue VARCHAR(255),
    payload LONGTEXT,
    exception LONGTEXT,
    failed_at TIMESTAMP
);
```

---

#### `tenants`
Multi-company support (ready for future).

```sql
CREATE TABLE tenants (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) UNIQUE,
    name VARCHAR(255),
    domain VARCHAR(255) NULL,
    is_active BOOLEAN DEFAULT TRUE,
    -- Standard columns
    ...
);
```

**Purpose:** All tables with `tenant_id` can support multiple companies without schema changes.

---

## Index Strategy

### Foreign Key Indexes
Auto-created on FK columns for join performance:
- `travel_agencies.agency_type_id`
- `orders.agency_id`, `.airline_id`, `.status_id`
- `wallet_transactions.wallet_id`, `.type_id`
- All child table FKs

### Composite Indexes
For queries accessing multiple columns:

```sql
-- Statement retrieval (pagination)
INDEX composite_wallet_statement (wallet_id, created_at)

-- Dashboard filters
INDEX composite_orders_dashboard (agency_id, status_id)

-- Polymorphic lookups
INDEX composite_transactions_lookup (reference_type, reference_id)

-- Booking search
INDEX composite_bookings_search (agency_id, created_at)
```

### Full-Text Indexes
For free-text search:

```sql
FULLTEXT INDEX ft_agency_name ON travel_agencies(name)
FULLTEXT INDEX ft_passenger_name ON order_passengers(full_name)
```

### Partitioning Strategy
Large append-only tables benefit from partitioning:

```sql
-- Monthly partitioning on created_at
CREATE TABLE wallet_transactions (
    ...
) PARTITION BY RANGE (YEAR_MONTH(created_at)) (
    PARTITION p_2026_07 VALUES LESS THAN (202608),
    PARTITION p_2026_08 VALUES LESS THAN (202609),
    ...
);
```

---

## Constraints & Data Integrity

### CHECK Constraints
```sql
ALTER TABLE travel_agencies ADD CHECK (credit_limit >= 0);
ALTER TABLE wallet_transactions ADD CHECK (amount > 0);
ALTER TABLE invoice_items ADD CHECK (quantity > 0);
```

### Cascade Rules (§9.9)

| Relationship | On Delete | Reason |
|---|---|---|
| agency → wallet | RESTRICT | Prevent loss of financial record |
| agency → orders | RESTRICT | Preserve booking history |
| order → segments/passengers | CASCADE | Details belong to order |
| order → quotes/ticket | RESTRICT | Preserve financial trail |
| wallet → transactions | RESTRICT | Immutable log |
| role ↔ permission | CASCADE | Pivot cleanup only |

---

## Migration Checklist

When creating migrations:

1. ✅ Define table in `create_table_name_table.php`
2. ✅ Add all columns including standard system columns
3. ✅ Define indexes (PK, FK, composite, FULLTEXT)
4. ✅ Add constraints (UNIQUE, CHECK, FK)
5. ✅ Use DECIMAL(15,3) for money columns
6. ✅ Use `nullable()` for optional FK fields
7. ✅ Add `softDeletes()` for business tables
8. ✅ Test rollback works: `php artisan migrate:rollback`

---

## Security & Archiving

### Sensitive Data
Encrypt via Laravel Casts:
```php
protected $casts = [
    'passport_number' => 'encrypted',
    'phone' => 'encrypted',
];
```

### Archiving Strategy
Move old records to cold storage:
- `wallet_transactions` → 2+ years to archive table
- `audit_logs` → 3+ years to archive table
- Keep referential integrity via soft deletes

### Backup Strategy
- Daily full backup
- Binary log for point-in-time recovery (PITR)
- Test restore procedures monthly

---

## Future Extensibility

The schema is designed to accommodate:

| Future Feature | Ready Fields |
|---|---|
| GDS Integration | `airlines.gds_enabled`, `orders.channel`, `gds_providers` table |
| Hotels/Visas | `product_type` on orders, specialized child tables |
| Payment Gateways | `payment_methods`, `payments.gateway_reference`, `feature_flags` |
| Mobile APIs | `uuid` + `personal_access_tokens` (Sanctum) |
| Multi-language | `name/name_en` in references, `translations` table |
| Multi-currency | `currency_id` on agencies, wallets, invoices |
| Multi-company | `tenant_id` on all tables |

---

**Document Version:** 1.0  
**Last Updated:** July 22, 2026
