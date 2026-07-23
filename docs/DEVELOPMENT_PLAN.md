# Advanced Travel Management Platform - Development Plan

**Project:** Advanced Travel Management Platform (MVP)  
**Version:** 1.0  
**Status:** In Development  
**Date:** July 22, 2026  

---

## Table of Contents

1. [Project Overview](#project-overview)
2. [Architecture](#architecture)
3. [Development Phases](#development-phases)
4. [URL Structure](#url-structure)
5. [Technology Stack](#technology-stack)
6. [Database Schema Summary](#database-schema-summary)
7. [Feature Checklist](#feature-checklist)

---

## Project Overview

The Advanced Travel Management Platform is a **hybrid manual B2B system** designed to digitize and centralize the operational booking cycle between platform administration and travel agencies. The MVP phase focuses on:

- **Travel Agencies (B2B)** as the exclusive business user
- **Manual ticket-request cycle** via the admin panel
- **Hybrid pricing** (mock + manual from live gateways)
- **Offline domestic Sudanese airline route**
- **Digital wallet and credit system**

### Value Proposition

*"Turning chaos into an organized system"* — Communication and order tracking between administration and agencies is currently ad-hoc and error-prone. The platform digitizes, centralizes, and fully structures the operational cycle.

### Key Roles

| Role | Status | Key Features |
|------|--------|--------------|
| **Super Admin** | Active | Absolute override, admin management, global settings |
| **Admin** | Active | Pricing orders, provisioning tickets, wallet top-up, refund approval |
| **B2B (Agencies)** | Active | Create requests, approve quotes, manage wallet, financial dashboards |
| **B2C (Individuals)** | Disabled | Built in (disabled via settings, enable later) |

---

## Architecture

### Project Structure

```
barqagency/
├── backend/              # Laravel API
│   ├── app/
│   │   ├── Models/       # Eloquent Models
│   │   ├── Http/
│   │   │   ├── Controllers/
│   │   │   └── Requests/
│   │   ├── Services/     # Business Logic
│   │   ├── Policies/     # Authorization
│   │   └── Observers/    # Event Listeners
│   ├── database/
│   │   ├── migrations/
│   │   ├── factories/
│   │   └── seeders/
│   ├── routes/
│   │   ├── api.php       # API routes
│   │   └── web.php
│   └── tests/
├── frontend/             # Vue.js SPA
│   ├── src/
│   │   ├── components/
│   │   │   ├── admin/    # Admin dashboard
│   │   │   ├── agency/   # Agency portal
│   │   │   └── shared/   # Shared components
│   │   ├── views/
│   │   ├── stores/       # Pinia state management
│   │   ├── composables/  # Vue composables
│   │   └── router/       # Vue Router setup
│   └── public/
├── docs/                 # Documentation
│   ├── DEVELOPMENT_PLAN.md
│   ├── DATABASE_SCHEMA.md
│   └── API_REFERENCE.md
└── ui images/            # UI mockups and assets
```

### Technology Stack

**Backend:**
- Laravel 11 (PHP 8.3+)
- MySQL 8.0+ with InnoDB
- Sanctum (API authentication)
- **Spatie Laravel Permission** (role-based access control)
- Queue system (for async tasks)
- Pest/PHPUnit (testing)

**Backend Libraries:**
```json
{
  "spatie/laravel-permission": "^6.0"
}
```

**Frontend:**
- Vue 3 (Composition API)
- Pinia (state management)
- Vue Router (routing)
- Tailwind CSS (styling)
- Axios (HTTP client)

**Architecture Pattern:**
- RESTful API
- Clean Code Principles
- DDD (Domain-Driven Design)
- SOLID Principles
- Plug-and-Play design (feature flags)
- Role-based Access Control (RBAC) via Spatie

---

## Development Phases

### Phase 1: Project Setup & Database Design

**Duration:** Week 1  
**Priority:** HIGH  
**Objective:** Establish foundation for all future work

#### Tasks

1. **Database Setup & Migrations**
   - [ ] Create all 48 tables as per SRS (§9.3)
   - [ ] Define relationships and cascade rules (§9.9)
   - [ ] Create indexes for performance (§9.10)
   - [ ] Set up soft deletes for audit trail

2. **Seeder & Factory Setup**
   - [ ] Create seeders for reference tables (countries, airlines, currencies, etc.)
   - [ ] Create factories for testing data
   - [ ] Pre-populate Sudanese carriers (Badr Airlines, Tarco Aviation, Sudan Airways)

3. **Laravel Configuration**
   - [ ] Environment setup (.env files)
   - [ ] Database connection verification
   - [ ] Queue driver configuration
   - [ ] Cache setup (Redis/file-based)

#### Key Tables to Create
- **Identity & Access:** users, user_types, agency_users, agency_documents
  - *Note: roles, permissions, role_user, permission_role are created by Spatie's migrations*
- **Agencies:** travel_agencies, agency_types, agency_users, agency_documents
- **Bookings:** orders, order_statuses, order_segments, order_passengers, quotes, quote_statuses, tickets, ticket_statuses
- **Finance:** wallets, wallet_transactions, wallet_transaction_types, invoices, invoice_items, payments, refunds, credit_notes
- **Reference:** countries, cities, airports, airlines, cabin_classes, currencies, payment_methods
- **Audit & History:** audit_logs, order_status_histories, wallet_balance_snapshots, login_histories
- **Config:** settings, feature_flags

#### Database Design Highlights
- DECIMAL(15,3) for all money values (never FLOAT)
- UUID + BIGINT for secure public IDs
- Standard system columns: id, uuid, status, created_by, updated_by, deleted_by, timestamps, metadata
- Soft deletes for financial records
- Immutable append-only transaction logs

---

### Phase 2: Authentication & Authorization (via Spatie)

**Duration:** Week 1-2  
**Priority:** HIGH  
**Objective:** Secure user access and role-based permissions using Spatie Laravel Permission

#### Tasks

1. **Install & Configure Spatie Laravel Permission**
   - [ ] Install: `composer require spatie/laravel-permission`
   - [ ] Publish migrations: `php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"`
   - [ ] Publish config: `php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider" --tag="config"`
   - [ ] Run migrations: `php artisan migrate`
   - [ ] Clear config cache: `php artisan config:cache`

2. **User Model Setup**
   - [ ] Add HasRoles trait to User model: `use Spatie\Permission\Traits\HasRoles;`
   - [ ] Create User model with types (super_admin, admin, b2b, b2c)
   - [ ] Add relationships: `roles()`, `permissions()`, `hasRole()`, `hasPermission()`
   - [ ] Create UserFactory with role assignment
   - [ ] Create UserType reference table (super_admin, admin, b2b, b2c)

3. **Roles & Permissions Setup (Spatie)**
   - [ ] Create RolesAndPermissionsSeeder
   - [ ] Define roles: super_admin, admin, agency
   - [ ] Define permissions: (see list below)
   - [ ] Assign permissions to roles
   - [ ] Implement Super Admin bypass: `if($user->role === 'super_admin') return true;`

4. **Permission Definitions**

   **Order Permissions:**
   - `order:create` - Create ticket request (agencies)
   - `order:view` - View orders
   - `order:price` - Price orders (admins)
   - `order:approve` - Approve orders (agencies)

   **Wallet Permissions:**
   - `wallet:view` - View wallet balance
   - `wallet:topup` - Top-up wallet (admins)
   - `wallet:set-credit-limit` - Set credit limits (super_admin)

   **Refund Permissions:**
   - `refund:request` - Request refund (agencies)
   - `refund:approve` - Approve refund (admins)
   - `refund:view` - View refunds

   **Admin Permissions:**
   - `users:create` - Create admin users
   - `users:edit` - Edit users
   - `users:delete` - Delete users
   - `permissions:manage` - Manage roles/permissions
   - `agencies:manage` - Manage agencies
   - `reports:view` - View financial reports
   - `settings:manage` - Manage global settings
   - `feature-flags:manage` - Toggle feature flags

5. **API Authentication (Sanctum)**
   - [ ] Implement token-based authentication
   - [ ] Create auth endpoints (login, logout, refresh)
   - [ ] Add permission middleware: `permission:order:create`
   - [ ] Add role middleware: `role:admin|super_admin`
   - [ ] Implement API rate limiting

6. **Authorization Policies (with Spatie)**
   - [ ] Create `UserPolicy` - Check with Spatie permissions
   - [ ] Create `TravelAgencyPolicy` - Check with Spatie permissions
   - [ ] Create `OrderPolicy` - Check with Spatie permissions
   - [ ] Create `WalletPolicy` - Check with Spatie permissions
   - [ ] Create `RefundPolicy` - Check with Spatie permissions
   
   **Example Policy Usage:**
   ```php
   public function create(User $user)
   {
       return $user->can('order:create');
   }
   ```

7. **Middleware Setup**
   - [ ] Use Spatie's built-in middleware: `permission:order:create`
   - [ ] Use Spatie's role middleware: `role:admin`
   - [ ] Create custom middleware if needed (e.g., SuperAdminOnly)
   - [ ] Apply middleware to routes

8. **Frontend Auth State (Pinia)**
   - [ ] Create auth store with Spatie integration
   - [ ] Store user roles & permissions from API
   - [ ] Create composable: `useCanI()` for permission checks
   - [ ] Create composable: `useHasRole()` for role checks
   - [ ] Route guards check permissions via API
   - [ ] Role-based page access

#### Controllers to Create
- `Auth/LoginController` - User authentication (use Spatie roles)
- `Auth/LogoutController` - Session termination
- `Auth/RefreshController` - Refresh token
- `Admin/UserController` - Create/manage admin users (assign Spatie roles)
- `Admin/PermissionController` - View/list permissions (read-only, managed in seeder)

#### Frontend Composables
```javascript
// composables/usePermissions.js
export function useCanI(permission) {
  const authStore = useAuthStore();
  return authStore.user?.permissions?.includes(permission);
}

export function useHasRole(role) {
  const authStore = useAuthStore();
  return authStore.user?.roles?.includes(role);
}
```

#### Usage Examples

**Backend (Blade/Controller):**
```php
// Check permission
if ($user->can('order:price')) {
    // User can price orders
}

// Check role
if ($user->hasRole('admin')) {
    // User is admin
}

// Middleware
Route::post('/api/orders/{id}/price', [OrderController::class, 'price'])
    ->middleware(['auth:sanctum', 'permission:order:price']);
```

**Frontend (Vue):**
```vue
<template>
  <button v-if="useCanI('order:price')" @click="priceOrder">
    Price Order
  </button>
</template>

<script setup>
import { useCanI } from '@/composables/usePermissions';
</script>
```

---

### Phase 3: Core Booking System (Orders & Requests)

**Duration:** Week 2-3  
**Priority:** HIGH  
**Objective:** Implement the ticket request workflow

#### Tasks

1. **Order Creation Flow (UC-01)**
   - [ ] Create Order model with status state machine
   - [ ] Implement FR-REQ-01: Agency creates request with flight details
   - [ ] Implement FR-REQ-02: Request reaches admin dashboard immediately
   - [ ] Create OrderSegment & OrderPassenger models for 3NF
   - [ ] Validate completeness before creation
   - [ ] Set initial status: "Pending Pricing"

2. **Admin Pricing & Provisioning (UC-02)**
   - [ ] Create Quote model for price proposals
   - [ ] Implement FR-PRC-02: Admin enters final itinerary & price
   - [ ] Implement FR-PRC-03: Send binding offer to agency
   - [ ] Create pricing interface with mock/live API options

3. **Domestic Sudanese Route (UC-05)**
   - [ ] Implement FR-SDN-01 to FR-SDN-05: Offline manual route
   - [ ] Set `channel = 'local'` for Sudanese carriers
   - [ ] Bypass any API calls for local carriers
   - [ ] Exclude from future GDS automation

4. **Order Status Tracking (FR-REQ-05)**
   - [ ] Create OrderStatusHistory model
   - [ ] Log every status transition
   - [ ] Implement state machine: pending_pricing → pricing_sent → approved → confirmed → cancelled
   - [ ] Real-time updates via WebSocket (future) or polling

#### Models to Create
- `Order` (state machine, event listeners)
- `OrderSegment` (flight legs)
- `OrderPassenger` (traveler details)
- `Quote` (price proposals)
- `Ticket` (issued ticket)

#### Controllers to Create
- `Agency/OrderController` - Create orders
- `Admin/OrderController` - Review, price, provision
- `Shared/OrderController` - View order details

#### Frontend Components
- `agency/OrderForm.vue` - Flight details form
- `agency/OrderList.vue` - Agency's orders
- `admin/OrderQueue.vue` - Admin dashboard order list
- `admin/PricingForm.vue` - Manual pricing interface

---

### Phase 4: Financial Management (Wallet & Ledger)

**Duration:** Week 3-4  
**Priority:** HIGH  
**Objective:** Implement wallet, transactions, and refunds

#### Tasks

1. **Wallet & Ledger System (FR-WAL-01 to FR-WAL-05)**
   - [ ] Create Wallet model (1:1 with Agency)
   - [ ] Implement balance tracking (paid vs. unpaid)
   - [ ] Allow negative balance within credit_limit
   - [ ] WalletService for all balance operations

2. **Wallet Top-Up (UC-03)**
   - [ ] Admin manually tops up wallet after bank verification
   - [ ] Create WalletTransaction with type 'topup'
   - [ ] Validate bank transfer off-platform
   - [ ] Update wallet balance and ledger totals

3. **Credit Limit Management (FR-WAL-03, UC-04)**
   - [ ] Admin sets credit_limit per agency
   - [ ] Booking validation: `balance - final_price >= -credit_limit`
   - [ ] Reject booking if exceeds limit
   - [ ] Notify agency

4. **Wallet Transactions (Immutable)**
   - [ ] Create WalletTransaction append-only log
   - [ ] Support types: topup, booking_debit, refund_credit, adjustment
   - [ ] Use DB::transaction + locks for race condition prevention
   - [ ] Never update—only reversing entries for corrections

5. **Invoice System**
   - [ ] Create Invoice model on confirmed booking
   - [ ] InvoiceItem for each service (ticket, fee)
   - [ ] Invoice → WalletTransaction relationship
   - [ ] Support PDF generation (future)

#### Models to Create
- `Wallet` (ledger header)
- `WalletTransaction` (append-only transaction log)
- `Invoice` (billing document)
- `InvoiceItem` (service lines)

#### Services to Create
- `WalletService` - Top-up, debit, balance queries
- `InvoiceService` - Create invoices, calculate totals

#### Controllers to Create
- `Admin/WalletController` - Top-up, set credit limits
- `Agency/WalletController` - View balance, statement
- `Shared/InvoiceController` - View/download invoices

#### Frontend Components
- `agency/WalletDashboard.vue` - Balance, statement, top-up requests
- `admin/WalletTopUp.vue` - Manually top up agency wallets
- `admin/CreditLimitForm.vue` - Set agency credit limits
- `admin/TransactionLog.vue` - View all transactions

---

### Phase 5: Refunds & Financial Settlement

**Duration:** Week 4  
**Priority:** HIGH  
**Objective:** Handle refunds with proper audit trail

#### Tasks

1. **Refund Calculation & Approval (UC-06, FR-REF-01 to FR-REF-06)**
   - [ ] Create Refund model
   - [ ] Implement dynamic refund rules (fully/partial/non-refundable)
   - [ ] FR-REF-02: Separate supplier_penalty from platform_fee
   - [ ] FR-REF-06: Mandatory admin approval for Sudanese carriers
   - [ ] Refund status: pending → approved → credited

2. **Credit Notes (FR-REF-04)**
   - [ ] Create CreditNote model (1:1 with Refund)
   - [ ] Link to original Invoice
   - [ ] Immutable audit record
   - [ ] Support PDF generation

3. **Refund Processing**
   - [ ] On approval: credit wallet immediately
   - [ ] FR-REF-03: Update balance and credit limits
   - [ ] FR-REF-05: Ledger sync (paid vs. unpaid)
   - [ ] Emit notification to agency

#### Models to Create
- `Refund` (refund request)
- `CreditNote` (issued credit)

#### Services to Create
- `RefundService` - Calculate, approve, process refunds

#### Controllers to Create
- `Admin/RefundController` - Review and approve refunds
- `Agency/RefundController` - Request refunds

#### Frontend Components
- `agency/RefundRequest.vue` - Request refund
- `admin/RefundQueue.vue` - Pending refund approvals
- `admin/CreditNoteView.vue` - View issued credit notes

---

### Phase 6: Admin Dashboard & Control Panel

**Duration:** Week 4-5  
**Priority:** HIGH  
**Objective:** Complete admin interface for operations

#### Tasks

1. **Dashboard Overview**
   - [ ] Real-time stats: pending orders, wallet top-ups, refunds
   - [ ] Financial summary: P&L, collected vs. outstanding
   - [ ] Agency status: active, suspended, credit limit warnings

2. **Order Management**
   - [ ] Queue of pending requests (FR-REQ-02)
   - [ ] Pricing interface (FR-PRC-02, FR-PRC-03)
   - [ ] Order status tracking (FR-REQ-05)
   - [ ] Search & filter by agency, date, status

3. **Agency Management**
   - [ ] View all agencies
   - [ ] Edit agency details, credit limits
   - [ ] Suspend/activate agencies
   - [ ] View agency transactions

4. **Wallet Management**
   - [ ] Top-up form (UC-03)
   - [ ] Verify bank transfers
   - [ ] Set credit limits (UC-04)
   - [ ] View balance snapshots

5. **Refund Management**
   - [ ] Queue of pending refunds (UC-06)
   - [ ] Approve/reject with notes
   - [ ] View credit notes issued

6. **User & Permission Management (FR-USR-01, FR-USR-02)**
   - [ ] Create/edit admin accounts
   - [ ] Assign roles and permissions
   - [ ] Super Admin override controls
   - [ ] Audit login history

7. **Financial Reports (FR-DSH-01 to FR-DSH-04)**
   - [ ] Real-time financial dashboard
   - [ ] Booking history
   - [ ] Ledger tracking (paid vs. unpaid)
   - [ ] Net P&L reports
   - [ ] Supplier penalty vs. platform fee separation

8. **Settings & Feature Flags (FR-USR-05, Feature Flag Pattern)**
   - [ ] Toggle B2C role activation (FR-USR-05)
   - [ ] Toggle payment gateways (FR-PAY-02, future)
   - [ ] Toggle GDS integration (future)
   - [ ] Global settings management

#### Controllers to Create
- `Admin/DashboardController` - Overview stats
- `Admin/AgencyController` - Agency CRUD
- `Admin/OrderController` - Order pricing/provisioning
- `Admin/FinancialReportController` - Reports
- `Admin/SettingsController` - Global settings & feature flags
- `Admin/UserController` - Admin account management

#### Frontend Components
- `admin/Dashboard.vue` - Main overview
- `admin/OrderQueue.vue` - Pending orders
- `admin/PricingForm.vue` - Price & provision
- `admin/AgencyList.vue` - Agency management
- `admin/WalletTopUp.vue` - Top-up form
- `admin/RefundQueue.vue` - Refund approvals
- `admin/FinancialDashboard.vue` - Reports
- `admin/UserManagement.vue` - Admin accounts
- `admin/SettingsPanel.vue` - Feature flags & settings
- `admin/SideNavigation.vue` - Admin menu

#### URL Routes
```
/admin                          # Admin dashboard
/admin/orders                   # Order queue
/admin/orders/:id/price         # Pricing form
/admin/agencies                 # Agency management
/admin/agencies/:id/edit        # Edit agency
/admin/wallet                   # Wallet management
/admin/wallet/topup             # Top-up form
/admin/refunds                  # Refund queue
/admin/reports                  # Financial reports
/admin/users                    # Admin user management
/admin/settings                 # Global settings
```

---

### Phase 7: Agency Portal

**Duration:** Week 5-6  
**Priority:** HIGH  
**Objective:** Complete agency-facing interface

#### Tasks

1. **Order Creation (FR-REQ-01, UC-01)**
   - [ ] Flight details form (departure, arrival, dates, passengers)
   - [ ] Familiar interface mimicking live platforms (NFR-05)
   - [ ] Form validation & completeness check
   - [ ] Create request and show confirmation

2. **Order Management**
   - [ ] List agency's orders with status
   - [ ] Order detail view
   - [ ] Track status changes (FR-REQ-05)
   - [ ] View quotes and pricing

3. **Quote Review & Approval (FR-REQ-04, UC-02)**
   - [ ] Display binding offer from admin
   - [ ] Review itinerary and price
   - [ ] Credit limit check before approval (UC-04)
   - [ ] Approve or reject quote
   - [ ] Confirmation page

4. **Wallet Dashboard (FR-DSH-01, FR-DSH-03, US-AGN-04)**
   - [ ] Current balance display
   - [ ] Paid vs. unpaid totals
   - [ ] Transaction history/statement
   - [ ] Credit limit display
   - [ ] Balance warnings if approaching limit

5. **Financial Reports (FR-DSH-02, FR-DSH-04)**
   - [ ] Booking history
   - [ ] P&L summary (if visible to agencies)
   - [ ] Date range filtering
   - [ ] Export/download options

6. **Refund Requests** (future enhancement)
   - [ ] Request refund on bookings
   - [ ] Track refund status
   - [ ] Download credit notes (FR-REF-04, US-AGN-05)

7. **Localization (NFR-05)**
   - [ ] Support Arabic (RTL) and English (LTR)
   - [ ] Language toggle
   - [ ] Store preference

#### Controllers to Create
- `Agency/OrderController` - Create & view orders
- `Agency/QuoteController` - Review & approve quotes
- `Agency/WalletController` - Wallet dashboard
- `Agency/ReportController` - Financial reports

#### Frontend Components
- `agency/OrderForm.vue` - New order creation
- `agency/OrderList.vue` - Orders list
- `agency/OrderDetail.vue` - Order details
- `agency/QuoteReview.vue` - Quote approval
- `agency/WalletDashboard.vue` - Balance & transactions
- `agency/StatementView.vue` - Transaction history
- `agency/ReportView.vue` - Financial reports
- `agency/SideNavigation.vue` - Agency menu

#### URL Routes
```
/agency                         # Agency dashboard
/agency/orders                  # Orders list
/agency/orders/new              # Create order
/agency/orders/:id              # Order details
/agency/orders/:id/quote        # Quote review & approval
/agency/wallet                  # Wallet dashboard
/agency/reports                 # Financial reports
/agency/refunds                 # Refund requests (future)
```

---

### Phase 8: Landing Page & Public Site

**Duration:** Week 6  
**Priority:** MEDIUM  
**Objective:** Public-facing entry point

#### Tasks

1. **Landing Page**
   - [ ] Company information
   - [ ] Feature highlights
   - [ ] Call-to-action buttons

2. **Login Page**
   - [ ] Admin login (→ /admin)
   - [ ] Agency login (→ /agency)
   - [ ] Forgot password (future)

3. **Public Resources**
   - [ ] About us
   - [ ] Contact information
   - [ ] Help/FAQ

#### Frontend Components
- `public/LandingPage.vue`
- `public/LoginPage.vue`
- `public/AboutPage.vue`
- `public/ContactPage.vue`

#### URL Routes
```
/                               # Landing page
/login                          # Login page
/about                          # About us
/contact                        # Contact info
```

---

### Phase 9: Testing & Deployment

**Duration:** Week 6-7  
**Priority:** HIGH  
**Objective:** Ensure quality and readiness for production

#### Tasks

1. **Backend Testing**
   - [ ] Unit tests for services (WalletService, OrderService, RefundService)
   - [ ] Feature tests for API endpoints
   - [ ] Database transaction tests (critical for wallet)
   - [ ] Authorization policy tests
   - [ ] Test coverage: >80%

2. **Frontend Testing**
   - [ ] Component unit tests (Vue Test Utils)
   - [ ] Store tests (Pinia)
   - [ ] Route guard tests
   - [ ] E2E tests for critical flows (order creation, refund)
   - [ ] Test coverage: >70%

3. **Manual Testing & QA**
   - [ ] End-to-end workflow testing
   - [ ] Cross-browser testing
   - [ ] Mobile responsiveness
   - [ ] RTL (Arabic) text display
   - [ ] Performance testing (NFR-01: lightweight interface)

4. **Security Testing**
   - [ ] SQL injection prevention
   - [ ] XSS prevention
   - [ ] CSRF protection
   - [ ] Rate limiting
   - [ ] Authorization enforcement
   - [ ] Sensitive data encryption (PII columns)

5. **Deployment**
   - [ ] Environment configuration
   - [ ] Database migrations
   - [ ] Asset compilation
   - [ ] CI/CD pipeline setup
   - [ ] Server provisioning
   - [ ] SSL/TLS setup
   - [ ] Monitoring & logging setup

#### Testing Files to Create
```
backend/tests/
├── Unit/
│   ├── Services/WalletServiceTest.php
│   ├── Services/OrderServiceTest.php
│   └── Services/RefundServiceTest.php
├── Feature/
│   ├── Auth/LoginTest.php
│   ├── Orders/CreateOrderTest.php
│   ├── Orders/PriceOrderTest.php
│   ├── Wallet/TopUpTest.php
│   └── Refunds/ProcessRefundTest.php
└── Policies/

frontend/tests/
├── unit/
│   ├── stores/
│   └── composables/
└── e2e/
    ├── order-creation.spec.js
    └── refund-process.spec.js
```

---

## URL Structure

### Public Routes
```
GET  /                           # Landing page
GET  /login                      # Login page
GET  /about                      # About us
GET  /contact                    # Contact info
```

### Admin Routes
```
GET  /admin                      # Dashboard
GET  /admin/orders               # Order queue
GET  /admin/orders/:id/price     # Pricing form
POST /admin/orders/:id/price     # Submit pricing
GET  /admin/agencies             # Agency management
GET  /admin/agencies/:id/edit    # Edit agency
POST /admin/agencies/:id         # Update agency
GET  /admin/wallet               # Wallet overview
POST /admin/wallet/topup         # Top-up form
GET  /admin/refunds              # Refund queue
POST /admin/refunds/:id/approve  # Approve refund
GET  /admin/reports              # Financial reports
GET  /admin/users                # Admin management
GET  /admin/settings             # Global settings
POST /admin/settings             # Save settings
```

### Agency Routes
```
GET  /agency                     # Dashboard
GET  /agency/orders              # Orders list
GET  /agency/orders/new          # Create order
POST /agency/orders              # Submit order
GET  /agency/orders/:id          # Order details
GET  /agency/orders/:id/quote    # Quote review
POST /agency/orders/:id/approve  # Approve quote
GET  /agency/wallet              # Wallet dashboard
GET  /agency/reports             # Financial reports
GET  /agency/refunds             # Refund requests
```

### API Routes (Backend)
```
POST /api/login                  # Authentication
POST /api/logout                 # Logout
GET  /api/user                   # Current user info

GET  /api/orders                 # List orders
POST /api/orders                 # Create order
GET  /api/orders/:id             # Order details
PUT  /api/orders/:id/quote       # Submit quote
PUT  /api/orders/:id/approve     # Approve order

GET  /api/wallets/:id            # Wallet details
POST /api/wallets/:id/topup      # Top-up
GET  /api/wallets/:id/statement  # Transactions

GET  /api/refunds                # List refunds
POST /api/refunds                # Create refund request
PUT  /api/refunds/:id/approve    # Approve refund

GET  /api/agencies               # List agencies
POST /api/agencies               # Create agency
GET  /api/agencies/:id           # Agency details
PUT  /api/agencies/:id           # Update agency

GET  /api/invoices/:id           # Invoice details
GET  /api/credit-notes/:id       # Credit note details

GET  /api/reports/financial      # Financial reports
GET  /api/reports/bookings       # Booking history
```

---

## Technology Stack

### Backend (Laravel 11)

**Core Packages:**
```json
{
  "php": "^8.3",
  "laravel/framework": "^11.0",
  "laravel/sanctum": "^4.0",
  "laravel/tinker": "^2.9"
}
```

**Database:**
- MySQL 8.0+
- Migrations for version control
- Query builder + Eloquent ORM

**Libraries:**
- `nesbot/carbon` - Date/time handling
- `league/csv` - CSV export for reports
- `barryvdh/laravel-snappy` - PDF generation (future)

**Testing:**
```json
{
  "pestphp/pest": "^2.0",
  "pestphp/pest-plugin-laravel": "^2.0"
}
```

**Development:**
```json
{
  "barryvdh/laravel-ide-helper": "^2.13",
  "barryvdh/laravel-debugbar": "^3.8"
}
```

### Frontend (Vue 3)

**Core:**
```json
{
  "vue": "^3.4",
  "vue-router": "^4.3",
  "pinia": "^2.1",
  "axios": "^1.6"
}
```

**Styling:**
- Tailwind CSS ^3.0
- HeadlessUI for components

**Utilities:**
- `date-fns` - Date formatting
- `numeral` - Number formatting
- `i18n-js` - Localization

**Testing:**
```json
{
  "vitest": "^1.0",
  "@vue/test-utils": "^2.4",
  "cypress": "^13.0"
}
```

### Development Tools

**Build:**
- Vite (frontend bundling)
- npm/yarn (package management)

**Version Control:**
- Git
- Conventional commits

**Documentation:**
- Markdown (.md files in `/docs`)
- API docs via comments

---

## Database Schema Summary

### Core Modules

#### 1. Identity & Access (7 tables)
- `users` - User accounts
- `user_types` - User classifications
- `roles` - Role definitions
- `permissions` - Permission definitions
- `permission_categories` - Organize permissions
- `role_user` - User role assignments (pivot)
- `permission_role` - Role permissions (pivot)

#### 2. Agencies (4 tables)
- `travel_agencies` - Agency accounts
- `agency_types` - Agency classifications
- `agency_users` - Users per agency
- `agency_documents` - Supporting documents

#### 3. Bookings (8 tables)
- `orders` - Ticket requests (header)
- `order_statuses` - Status reference
- `order_segments` - Flight legs
- `order_passengers` - Traveler details
- `quotes` - Price proposals
- `quote_statuses` - Quote status reference
- `tickets` - Issued tickets
- `ticket_statuses` - Ticket status reference

#### 4. Finance & Wallet (8 tables)
- `wallets` - Agency ledgers (1:1)
- `wallet_transactions` - Transaction log (append-only)
- `wallet_transaction_types` - Transaction classifications
- `invoices` - Billing documents
- `invoice_items` - Service line items
- `payments` - Payment records
- `payment_methods` - Payment type reference
- `refunds` - Refund requests
- `refund_reasons` - Refund classification
- `credit_notes` - Credit documents

#### 5. Reference/Lookup (11 tables)
- `countries` - Countries list
- `cities` - Cities list
- `airports` - Airport codes (IATA/ICAO)
- `airlines` - Airline data (includes is_local flag)
- `cabin_classes` - Cabin types
- `passenger_types` - ADT/CHD/INF classifications
- `currencies` - Currency codes & exchange
- `notification_types` - Alert templates

#### 6. Audit & History (4 tables)
- `audit_logs` - Complete audit trail
- `order_status_histories` - Status transitions
- `wallet_balance_snapshots` - Balance history
- `login_histories` - Login records

#### 7. Config & System (4 tables)
- `settings` - Key/value settings
- `feature_flags` - Feature toggles (B2C, GDS, Payment gateways)
- `notifications` - Notification records
- `notification_user` - Notification per-user read state
- `jobs` - Queue jobs
- `failed_jobs` - Failed queue jobs

**Total: 47 tables**

### Key Constraints

| Relationship | Type | On Delete | Reason |
|---|---|---|---|
| agency → wallet | 1:1 | RESTRICT | Prevent loss of financial record |
| agency → orders | 1:N | RESTRICT | Preserve booking history |
| order → segments/passengers | 1:N | CASCADE | Details belong to order |
| order → quotes/ticket | 1:N/1:1 | RESTRICT | Preserve financial trail |
| wallet → transactions | 1:N | RESTRICT | Financial records immutable |
| ticket → refund → credit_note | 1:N/1:1 | RESTRICT | Complete audit chain |

---

## Feature Checklist

### High Priority (Required for MVP Launch)

#### User Management (§3.1)
- [ ] FR-USR-01: Multiple admin accounts with granular permissions
- [ ] FR-USR-02: Super Admin absolute override
- [ ] FR-USR-03: B2B exclusive to travel agencies
- [ ] FR-USR-04: B2C features built but disabled
- [ ] FR-USR-05: B2C role can be activated via settings

#### Ticket Request Cycle (§3.2)
- [ ] FR-REQ-01: Agency creates request with flight details
- [ ] FR-REQ-02: Request reaches admin dashboard immediately
- [ ] FR-REQ-03: Admin manually enters itinerary & price
- [ ] FR-REQ-04: Agency reviews and approves offer
- [ ] FR-REQ-05: Order status tracking

#### Pricing & Provisioning (§3.3)
- [ ] FR-PRC-01: Mock/live pricing API integration
- [ ] FR-PRC-02: Admin fetches official prices
- [ ] FR-PRC-03: Admin sends binding offer

#### Domestic Sudanese Route (§3.4)
- [ ] FR-SDN-01: Independent route isolated from APIs
- [ ] FR-SDN-02: Manual data entry and bookings
- [ ] FR-SDN-03: Support Badr, Tarco, Sudan Airways
- [ ] FR-SDN-04: Fixed manual workflow
- [ ] FR-SDN-05: Exclude from future GDS automation

#### Wallet & Credit (§3.5)
- [ ] FR-WAL-01: Virtual ledger per agency
- [ ] FR-WAL-02: Admin tops up wallet manually
- [ ] FR-WAL-03: Set credit limit per agency
- [ ] FR-WAL-04: Allow negative balance within limit
- [ ] FR-WAL-05: Track paid vs. unpaid

#### Refunds & Settlement (§3.7)
- [ ] FR-REF-01: Dynamic refund calculation
- [ ] FR-REF-02: Separate supplier penalty from platform fee
- [ ] FR-REF-03: Approved refunds credited to wallet
- [ ] FR-REF-04: Issue credit notes linked to invoices
- [ ] FR-REF-05: Real-time ledger sync
- [ ] FR-REF-06: Mandatory admin approval for Sudanese refunds

#### Dashboards & Reporting (§3.8)
- [ ] FR-DSH-01: Real-time financial dashboards
- [ ] FR-DSH-02: Complete booking history
- [ ] FR-DSH-03: Ledger tracking (paid vs. unpaid)
- [ ] FR-DSH-04: Net P&L reports

### Medium Priority (Important, Deferrable)

#### Future Payment Gateways (§3.6)
- [ ] FR-PAY-01: Ready configuration for Stripe, PayPal, Checkout.com
- [ ] FR-PAY-02: Admin toggles to enable/disable gateways
- [ ] FR-PAY-03: Full automation cycle
- [ ] FR-PAY-04: Plug-and-play architecture

### Non-Functional Requirements

#### Performance (NFR-01)
- [ ] Order-cycle interface lightweight & fast
- [ ] Optimize database queries (composite indexes)
- [ ] Cache reference tables (countries, airlines, etc.)
- [ ] Pagination for large lists

#### Scalability (NFR-02)
- [ ] Plug-and-play architecture
- [ ] Feature flags for B2C, GDS, payment gateways
- [ ] Settings managed from control panel
- [ ] No code rewrites needed

#### Security & Access (NFR-03)
- [ ] Granular role-based access control
- [ ] Super Admin override confined
- [ ] API rate limiting
- [ ] Sensitive data encryption

#### Accounting Accuracy (NFR-04)
- [ ] Real-time ledger sync
- [ ] Paid vs. unpaid match to last digit
- [ ] Transaction atomicity (DB::transaction)
- [ ] Row-level locks for wallet updates

#### Usability (NFR-05)
- [ ] Realistic interface mimicking live platforms
- [ ] Arabic (RTL) + English (LTR) support
- [ ] Familiar booking experience
- [ ] Quick learning curve

#### Auditability (NFR-06)
- [ ] Full audit trail via credit notes
- [ ] Linked to original invoices
- [ ] Clean accounting traceability
- [ ] Immutable transaction logs

#### Operational Isolation (NFR-07)
- [ ] Sudanese route independent from APIs
- [ ] Manual continuity assured
- [ ] Excluded from future GDS automation

---

## Implementation Guidelines

### Code Quality Standards

1. **Naming Conventions**
   - Controllers: `{Resource}Controller` (e.g., `OrderController`)
   - Models: Singular capitalized (e.g., `Order`, `Agency`)
   - Database: Plural snake_case (e.g., `travel_agencies`, `wallet_transactions`)
   - Methods: camelCase, descriptive verbs (e.g., `createOrder`, `approveRefund`)
   - Variables: camelCase (e.g., `$finalPrice`, `$creditLimit`)

2. **Laravel Best Practices**
   - Use Models for all database access
   - Services for business logic
   - Policies for authorization
   - Observers for event-driven actions
   - Type hints on all methods
   - Request classes for validation

3. **Vue Best Practices**
   - Composition API with setup()
   - Composables for reusable logic
   - Props validation
   - Proper component naming (PascalCase)
   - Pinia stores for state management
   - Router guards for auth/permissions

4. **Database Best Practices**
   - All transactions use `DB::transaction()` with locks
   - Soft deletes for financial records
   - Indexes on all FK and searchable columns
   - Never use FLOAT for money (always DECIMAL)
   - Append-only transaction logs (no updates)

### Testing Strategy

1. **Backend**
   - Unit tests: Services, Models, Policies
   - Feature tests: API endpoints, workflows
   - Database tests: Transactions, constraints
   - Coverage goal: >80%

2. **Frontend**
   - Component tests: Isolated component behavior
   - Store tests: State mutations
   - E2E tests: Critical user flows
   - Coverage goal: >70%

### Deployment Checklist

- [ ] Environment variables configured (.env)
- [ ] Database migrations run (`php artisan migrate`)
- [ ] Seeders executed for reference data
- [ ] Frontend built (`npm run build`)
- [ ] Assets optimized for production
- [ ] Caching enabled
- [ ] SSL/TLS certificates installed
- [ ] Monitoring tools configured
- [ ] Database backups scheduled
- [ ] Error logging configured

---

## Key Business Rules

1. **Wallet Balance:** Can go negative only if within `credit_limit`
2. **Booking Approval:** Blocked if `balance - final_price < -credit_limit`
3. **Sudanese Carriers:** Always use manual route, never API
4. **Refund Approval:** Mandatory for Sudanese airlines (UC-06)
5. **Transactions:** Immutable and append-only (no updates/deletes)
6. **Status Immutability:** After confirmed, order details locked
7. **Multi-Currency:** Planned (currency_id field ready)
8. **Audit Trail:** Every financial change logged in audit_logs

---

## Future Enhancements (Phase 2)

- [ ] B2C role activation and individual customer portal
- [ ] Direct GDS integration (Amadeus, Sabre, Galileo)
- [ ] Automated payment gateways (Stripe, PayPal)
- [ ] Hotel, visa, insurance products
- [ ] Mobile apps (iOS/Android)
- [ ] Public APIs for partners
- [ ] Advanced reporting and analytics
- [ ] AI-powered pricing recommendations
- [ ] Chat/support system

---

## Document References

- **SRS Document:** `SRS-Document-English.pdf` - Complete requirements
- **Database Design:** Phase 3 (§09) - Database schema details
- **UI Mockups:** `/ui images/` folder - Design references
- **API Documentation:** To be created in `docs/API_REFERENCE.md`
- **Deployment Guide:** To be created in `docs/DEPLOYMENT.md`

---

**Document Version:** 1.0  
**Last Updated:** July 22, 2026  
**Next Review:** Upon phase completion
