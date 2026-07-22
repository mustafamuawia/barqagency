# Implementation Checklist

**Project:** Advanced Travel Management Platform (MVP)  
**Version:** 1.0  
**Last Updated:** July 22, 2026

---

## 📋 How to Use This Checklist

- [ ] = Not Started
- [x] = Completed
- [~] = In Progress

Update this file as work progresses. Copy the checkbox syntax `[ ]` (or `[x]` or `[~]`) to track status.

---

## Phase 1: Project Setup & Database Design

**Duration:** Week 1  
**Status:** ⏳ Not Started  
**Owner:** [Backend Lead]

### 1.1 Database Setup & Migrations
- [ ] Create database: `barqa_travel`
- [ ] Set charset to `utf8mb4_unicode_ci`
- [ ] Create migration: `create_users_table`
- [ ] Create migration: `create_travel_agencies_table`
- [ ] Create migration: `create_wallets_table`
- [ ] Create migration: `create_wallet_transactions_table`
- [ ] Create migration: `create_orders_table`
- [ ] Create migration: `create_order_segments_table`
- [ ] Create migration: `create_order_passengers_table`
- [ ] Create migration: `create_quotes_table`
- [ ] Create migration: `create_tickets_table`
- [ ] Create migration: `create_invoices_table`
- [ ] Create migration: `create_invoice_items_table`
- [ ] Create migration: `create_refunds_table`
- [ ] Create migration: `create_credit_notes_table`
- [ ] Create migration: `create_roles_table`
- [ ] Create migration: `create_permissions_table`
- [ ] Create migration: `create_audit_logs_table`
- [ ] Create migration: `create_order_status_histories_table`
- [ ] Create migration: `create_reference_tables` (countries, airlines, etc.)
- [ ] Create migration: `create_feature_flags_table`
- [ ] Create migration: `create_settings_table`
- [ ] Run all migrations: `php artisan migrate`

### 1.2 Seeder & Factory Setup
- [ ] Create factory: `UserFactory`
- [ ] Create factory: `TravelAgencyFactory`
- [ ] Create factory: `OrderFactory`
- [ ] Create factory: `WalletFactory`
- [ ] Create seeder: `CountriesSeeder`
- [ ] Create seeder: `AirlinesSeeder` (Badr, Tarco, Sudan Airways)
- [ ] Create seeder: `CurrenciesSeeder`
- [ ] Create seeder: `RolesAndPermissionsSeeder`
- [ ] Create seeder: `UsersSeeder` (super admin, admins)
- [ ] Create seeder: `TravelAgenciesSeeder`
- [ ] Create seeder: `AirportsSeeder`
- [ ] Run seeders: `php artisan db:seed`

### 1.3 Laravel Configuration
- [ ] Configure `.env` with database credentials
- [ ] Generate app key: `php artisan key:generate`
- [ ] Configure queue driver (for async tasks)
- [ ] Configure cache driver (Redis recommended)
- [ ] Test database connection
- [ ] Test migrations work: `php artisan migrate:fresh`

---

## Phase 2: Authentication & Authorization (via Spatie)

**Duration:** Week 1-2  
**Status:** ⏳ Not Started  
**Owner:** [Backend Lead]

### 2.1 Install & Configure Spatie Laravel Permission
- [ ] Install: `composer require spatie/laravel-permission`
- [ ] Publish migrations: `php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"`
- [ ] Publish config: `php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider" --tag="config"`
- [ ] Run migrations: `php artisan migrate`
- [ ] Clear config cache: `php artisan config:cache`

### 2.2 User Model Setup
- [ ] Create `User` model
- [ ] Add HasRoles trait: `use Spatie\Permission\Traits\HasRoles;`
- [ ] Create `UserType` reference table (super_admin, admin, b2b, b2c)
- [ ] Create UserFactory with role assignment
- [ ] Test Spatie methods: `hasRole()`, `hasPermission()`, `can()`

### 2.3 Roles & Permissions (Spatie)
- [ ] Create RolesAndPermissionsSeeder
- [ ] Define 3 roles: super_admin, admin, agency
- [ ] Define permissions: order:create, order:view, order:price, wallet:view, wallet:topup, wallet:set-credit-limit, refund:request, refund:approve, users:create, permissions:manage, agencies:manage, reports:view, settings:manage, feature-flags:manage
- [ ] Assign permissions to roles using Spatie's API: `$role->givePermissionTo()`
- [ ] Implement Super Admin bypass: always return true for super_admin role
- [ ] Run seeder: `php artisan db:seed --class=RolesAndPermissionsSeeder`

### 2.4 API Authentication (Sanctum + Spatie)
- [ ] Install Laravel Sanctum: `composer require laravel/sanctum` (if not included)
- [ ] Create `LoginController`
- [ ] Implement login endpoint: `POST /api/login` (returns token)
- [ ] Implement logout endpoint: `POST /api/logout`
- [ ] Implement refresh endpoint: `POST /api/refresh`
- [ ] Create `CurrentUserController`: `GET /api/user` (return user with roles & permissions)
- [ ] Add Sanctum auth middleware to API routes
- [ ] Add Spatie permission middleware: `permission:order:create`
- [ ] Add Spatie role middleware: `role:admin|super_admin`
- [ ] Configure CORS for frontend
- [ ] Test endpoints with Postman/Insomnia

### 2.5 Authorization Policies (with Spatie)
- [ ] Create `UserPolicy` using Spatie permissions
- [ ] Create `TravelAgencyPolicy` using Spatie permissions
- [ ] Create `OrderPolicy` using Spatie permissions
- [ ] Create `WalletPolicy` using Spatie permissions
- [ ] Create `RefundPolicy` using Spatie permissions
- [ ] Use `$user->can('permission:name')` in policies
- [ ] Test authorization with different roles

### 2.6 Route Protection (Spatie Middleware)
- [ ] Apply `permission:order:create` middleware to order creation routes
- [ ] Apply `permission:order:price` middleware to pricing routes
- [ ] Apply `permission:wallet:topup` middleware to wallet topup routes
- [ ] Apply `permission:refund:approve` middleware to refund approval routes
- [ ] Apply `role:admin|super_admin` middleware to admin routes
- [ ] Test route access with different users

### 2.7 Frontend Auth (Pinia Store)
- [ ] Create auth store: `stores/auth.js`
- [ ] Store JWT token in localStorage
- [ ] Fetch user with roles & permissions on app init
- [ ] Implement login action
- [ ] Implement logout action
- [ ] Create composable: `useCanI(permission)` for permission checks
- [ ] Create composable: `useHasRole(role)` for role checks
- [ ] Create route guards using composables
- [ ] Implement login page: `LoginPage.vue`
- [ ] Implement logout functionality
- [ ] Test auth flow end-to-end

---

## Phase 3: Core Booking System (Orders & Requests)

**Duration:** Week 2-3  
**Status:** ⏳ Not Started  
**Owner:** [Full Stack Team]

### 3.1 Order Creation Flow (UC-01)
- [ ] Create `Order` model
- [ ] Create `OrderStatus` reference table
- [ ] Implement status state machine
- [ ] Create `OrderSegment` model (flight legs)
- [ ] Create `OrderPassenger` model (traveler details)
- [ ] Create `OrderController@store` (create order)
- [ ] Implement FR-REQ-01: Agency creates request
- [ ] Implement FR-REQ-02: Request reaches admin dashboard
- [ ] Implement order validation
- [ ] Create order number generation (ORD-DDMMYY-XXXX)
- [ ] Create `OrderForm.vue` (flight details)
- [ ] Create `OrderList.vue` (agency orders)
- [ ] Test order creation with validation

### 3.2 Admin Pricing & Provisioning (UC-02)
- [ ] Create `Quote` model
- [ ] Create `QuoteStatus` reference table
- [ ] Create `OrderController@updatePricing` (submit quote)
- [ ] Implement FR-PRC-02: Admin enters final itinerary & price
- [ ] Implement FR-PRC-03: Send binding offer to agency
- [ ] Create `PricingForm.vue` (admin interface)
- [ ] Create `QuoteReview.vue` (agency review)
- [ ] Implement quote validation
- [ ] Test pricing workflow

### 3.3 Order Status Tracking (FR-REQ-05)
- [ ] Create `OrderStatusHistory` model
- [ ] Create OrderObserver for auto-logging status changes
- [ ] Implement status transitions with validation
- [ ] Create `OrderStatusHistories` table
- [ ] Test status flow: pending → pricing_sent → approved → confirmed
- [ ] Create OrderQueue component for admin dashboard

### 3.4 Sudanese Airline Route (UC-05, FR-SDN)
- [ ] Create airline `is_local` flag
- [ ] Create airline `gds_enabled` flag
- [ ] Implement channel routing: `ENUM('gds', 'local')`
- [ ] Routes with `is_local=true` bypass any API calls
- [ ] Implement FR-SDN-01 to FR-SDN-05
- [ ] Pre-populate Sudanese carriers (Badr, Tarco, Sudan Airways)
- [ ] Test local route never calls APIs
- [ ] Exclude local carriers from future GDS automation

---

## Phase 4: Financial Management (Wallet & Ledger)

**Duration:** Week 3-4  
**Status:** ⏳ Not Started  
**Owner:** [Finance Backend Team]

### 4.1 Wallet & Ledger System (FR-WAL-01 to FR-WAL-05)
- [ ] Create `Wallet` model (1:1 with Agency)
- [ ] Create `Wallet` migration with balance caching
- [ ] Create `WalletObserver` for balance updates
- [ ] Implement balance tracking (paid vs. unpaid)
- [ ] Implement negative balance support (within credit_limit)
- [ ] Create `WalletService` for all operations
- [ ] Create `WalletRepository` for queries
- [ ] Create `WalletPolicy` for authorization
- [ ] Test wallet operations

### 4.2 Wallet Transactions (Immutable Log)
- [ ] Create `WalletTransaction` model
- [ ] Create `WalletTransactionType` reference table
- [ ] Implement append-only transaction log
- [ ] Implement DB::transaction with row locks
- [ ] Create transaction types: topup, booking_debit, refund_credit, adjustment
- [ ] Never allow updates/deletes to transactions
- [ ] Create `WalletTransactionObserver` for balance updates
- [ ] Test transaction immutability

### 4.3 Wallet Top-Up (UC-03)
- [ ] Create `WalletController@topup`
- [ ] Implement FR-WAL-02: Admin tops up after verification
- [ ] Create top-up form: `WalletTopUp.vue`
- [ ] Verify off-platform bank transfer
- [ ] Create top-up workflow
- [ ] Test wallet balance updates

### 4.4 Credit Limit Management (FR-WAL-03, UC-04)
- [ ] Add `credit_limit` to travel_agencies table
- [ ] Implement credit limit setter in AgencyController
- [ ] Implement balance check: `balance - final_price >= -credit_limit`
- [ ] Block booking if exceeds limit
- [ ] Notify agency if limit breached
- [ ] Create credit limit form: `CreditLimitForm.vue`
- [ ] Test credit limit enforcement

### 4.5 Invoice System
- [ ] Create `Invoice` model
- [ ] Create `InvoiceItem` model
- [ ] Create invoice on order confirmation
- [ ] Link invoice to wallet transaction
- [ ] Calculate totals and taxes
- [ ] Create `InvoiceController@show`
- [ ] Create invoice view: `InvoiceView.vue`
- [ ] Test invoice generation

---

## Phase 5: Refunds & Financial Settlement

**Duration:** Week 4  
**Status:** ⏳ Not Started  
**Owner:** [Finance Backend Team]

### 5.1 Refund Calculation & Approval (UC-06, FR-REF)
- [ ] Create `Refund` model
- [ ] Create `RefundReason` reference table
- [ ] Create `RefundStatus` reference table
- [ ] Implement dynamic refund calculation (full/partial/non-refundable)
- [ ] Implement FR-REF-02: Separate supplier_penalty from platform_fee
- [ ] Implement refund workflow: pending → approved → credited
- [ ] Create `RefundService` for calculations
- [ ] Implement FR-REF-06: Mandatory approval for Sudanese carriers
- [ ] Create `RefundController@store` (request refund)
- [ ] Create `RefundController@approve` (admin approval)
- [ ] Test refund calculations

### 5.2 Credit Notes (FR-REF-04)
- [ ] Create `CreditNote` model (1:1 with Refund)
- [ ] Link credit note to original invoice
- [ ] Create credit note on refund approval
- [ ] Make credit notes immutable
- [ ] Create `CreditNoteController@show`
- [ ] Create credit note view: `CreditNoteView.vue`
- [ ] Test credit note generation

### 5.3 Refund Processing
- [ ] On approval: credit wallet immediately
- [ ] Update balance and credit limits
- [ ] Update ledger (paid vs. unpaid)
- [ ] Create WalletTransaction with type 'refund_credit'
- [ ] Notify agency of refund approval
- [ ] Test end-to-end refund flow

---

## Phase 6: Admin Dashboard & Control Panel

**Duration:** Week 4-5  
**Status:** ⏳ Not Started  
**Owner:** [Frontend Team]

### 6.1 Dashboard Overview
- [ ] Create `AdminDashboard.vue`
- [ ] Display pending orders count
- [ ] Display pending refunds count
- [ ] Display pending wallet top-ups
- [ ] Display financial summary (P&L, collected, outstanding)
- [ ] Display agency status overview
- [ ] Create dashboard widgets/cards
- [ ] Test real-time data loading

### 6.2 Order Management
- [ ] Create `OrderQueue.vue` (pending orders)
- [ ] Create order search & filter
- [ ] Create quick view modal
- [ ] Create `PricingForm.vue` (pricing interface)
- [ ] Integrate with admin dashboard
- [ ] Test order queue and filtering

### 6.3 Agency Management
- [ ] Create `AgencyList.vue`
- [ ] Create `AgencyDetail.vue`
- [ ] Implement edit agency details
- [ ] Implement suspend/activate agency
- [ ] Create agency search
- [ ] Display agency wallets and balances
- [ ] Test agency management

### 6.4 Wallet Management
- [ ] Create `WalletTopUp.vue` (top-up form)
- [ ] Create `CreditLimitForm.vue` (set limits)
- [ ] Create wallet statement view
- [ ] Create wallet transaction log
- [ ] Test wallet operations

### 6.5 Refund Management
- [ ] Create `RefundQueue.vue` (pending refunds)
- [ ] Create refund detail view
- [ ] Create approval form
- [ ] Create credit note display
- [ ] Test refund workflow

### 6.6 User & Permission Management (FR-USR-01, FR-USR-02)
- [ ] Create `UserManagement.vue`
- [ ] Create user list & search
- [ ] Create user create/edit form
- [ ] Create role assignment interface
- [ ] Create permission management
- [ ] Create Super Admin override controls
- [ ] Test user management

### 6.7 Financial Reports (FR-DSH-01 to FR-DSH-04)
- [ ] Create `FinancialDashboard.vue`
- [ ] Display real-time financial metrics
- [ ] Display booking history table
- [ ] Display ledger tracking (paid vs. unpaid)
- [ ] Create Net P&L report
- [ ] Separate supplier penalties from platform fees
- [ ] Create date range filters
- [ ] Test report generation

### 6.8 Settings & Feature Flags (FR-USR-05)
- [ ] Create `SettingsPanel.vue`
- [ ] Create feature flag toggles
- [ ] Implement B2C role activation toggle
- [ ] Implement GDS integration flag (future)
- [ ] Implement payment gateway flags (future)
- [ ] Create settings save/restore
- [ ] Test feature flag functionality

### 6.9 Admin Navigation
- [ ] Create admin sidebar/menu
- [ ] Create `AdminLayout.vue`
- [ ] Create route guards for admin access
- [ ] Implement permission-based menu items
- [ ] Test navigation and access control

---

## Phase 7: Agency Portal

**Duration:** Week 5-6  
**Status:** ⏳ Not Started  
**Owner:** [Frontend Team]

### 7.1 Order Creation (FR-REQ-01, UC-01)
- [ ] Create `OrderForm.vue` (flight details)
- [ ] Implement flight details form validation
- [ ] Add passenger management interface
- [ ] Add segment management for multi-leg flights
- [ ] Make interface familiar to live platforms (NFR-05)
- [ ] Test form validation and submission

### 7.2 Order Management
- [ ] Create `OrderList.vue` (orders list)
- [ ] Create `OrderDetail.vue` (order details)
- [ ] Implement order filtering & sorting
- [ ] Display order status with visual indicators
- [ ] Create status history view
- [ ] Test order list and details

### 7.3 Quote Review & Approval (FR-REQ-04, UC-02)
- [ ] Create `QuoteReview.vue`
- [ ] Display binding offer from admin
- [ ] Display itinerary and price
- [ ] Show credit limit check result
- [ ] Create approve/reject buttons
- [ ] Show confirmation after approval
- [ ] Test quote workflow

### 7.4 Wallet Dashboard (FR-DSH-01, FR-DSH-03, US-AGN-04)
- [ ] Create `WalletDashboard.vue`
- [ ] Display current balance (prominently)
- [ ] Display paid vs. unpaid totals
- [ ] Display credit limit and usage
- [ ] Create balance warning indicator
- [ ] Test wallet dashboard

### 7.5 Statement & Transactions
- [ ] Create `StatementView.vue`
- [ ] Display transaction history
- [ ] Create transaction filters (date, type)
- [ ] Create export to CSV/Excel
- [ ] Test transaction statement

### 7.6 Financial Reports (FR-DSH-02, FR-DSH-04)
- [ ] Create `ReportView.vue`
- [ ] Display booking history
- [ ] Display P&L summary
- [ ] Create date range filters
- [ ] Create export functionality
- [ ] Test report views

### 7.7 Credit Notes & Refunds (FR-REF-04, US-AGN-05)
- [ ] Create `RefundRequest.vue` (request refund)
- [ ] Create refund status view
- [ ] Download credit notes
- [ ] Link credit notes to invoices
- [ ] Test refund workflow

### 7.8 Localization (NFR-05)
- [ ] Implement Arabic (RTL) support
- [ ] Implement English (LTR) support
- [ ] Create language toggle
- [ ] Store language preference
- [ ] Translate all UI text
- [ ] Test RTL/LTR rendering

### 7.9 Agency Navigation
- [ ] Create agency sidebar/menu
- [ ] Create `AgencyLayout.vue`
- [ ] Create route guards for agency access
- [ ] Test navigation and access control

---

## Phase 8: Landing Page & Public Site

**Duration:** Week 6  
**Status:** ⏳ Not Started  
**Owner:** [Frontend Team]

### 8.1 Landing Page
- [ ] Create `LandingPage.vue`
- [ ] Add company information
- [ ] Add feature highlights
- [ ] Add CTA buttons (Admin Login, Agency Login)
- [ ] Design responsive layout
- [ ] Test on mobile devices

### 8.2 Login Page
- [ ] Create `LoginPage.vue`
- [ ] Admin login redirect (→ /admin)
- [ ] Agency login redirect (→ /agency)
- [ ] Add forgot password link (future)
- [ ] Add signup link (future)
- [ ] Test login flows

### 8.3 Public Pages
- [ ] Create `AboutPage.vue`
- [ ] Create `ContactPage.vue`
- [ ] Add contact form
- [ ] Style consistently with brand
- [ ] Test responsiveness

---

## Phase 9: Testing & Deployment

**Duration:** Week 6-7  
**Status:** ⏳ Not Started  
**Owner:** [QA Team + DevOps]

### 9.1 Backend Testing
- [ ] Write unit tests for services (WalletService, OrderService, RefundService)
- [ ] Write feature tests for all API endpoints
- [ ] Write database transaction tests
- [ ] Write authorization policy tests
- [ ] Achieve >80% code coverage
- [ ] Run tests: `php artisan test --coverage`

### 9.2 Frontend Testing
- [ ] Write component unit tests (Vue Test Utils)
- [ ] Write store tests (Pinia)
- [ ] Write route guard tests
- [ ] Write E2E tests for critical flows
  - [ ] Order creation to approval
  - [ ] Refund request to approval
  - [ ] Wallet top-up flow
- [ ] Achieve >70% code coverage
- [ ] Run tests: `npm run test`

### 9.3 Manual Testing & QA
- [ ] Test all workflows end-to-end
- [ ] Cross-browser testing (Chrome, Firefox, Safari, Edge)
- [ ] Mobile responsiveness testing
- [ ] RTL (Arabic) text display testing
- [ ] Performance testing (page load time, API response time)
- [ ] Usability testing with stakeholders

### 9.4 Security Testing
- [ ] SQL injection prevention
- [ ] XSS prevention
- [ ] CSRF protection
- [ ] Rate limiting
- [ ] Authorization enforcement
- [ ] Sensitive data encryption (PII)
- [ ] Session security
- [ ] CORS configuration

### 9.5 Deployment
- [ ] Configure production `.env`
- [ ] Run migrations on production database
- [ ] Generate app key
- [ ] Build frontend: `npm run build`
- [ ] Configure web server (Nginx/Apache)
- [ ] Set up SSL/TLS certificates
- [ ] Configure environment variables
- [ ] Set up backups & monitoring
- [ ] Test production environment
- [ ] Create deployment checklist

---

## Cross-Functional Requirements

### Code Quality
- [ ] Follow PSR-12 (backend)
- [ ] Follow Prettier + ESLint (frontend)
- [ ] Use type hints on all methods (backend)
- [ ] Use props validation (frontend)
- [ ] Document complex logic
- [ ] Keep functions small and focused

### Testing Standards
- [ ] Every model has a factory
- [ ] Every endpoint has tests
- [ ] Every component has tests
- [ ] Edge cases are covered
- [ ] Error paths are tested
- [ ] Performance is acceptable

### Documentation
- [ ] Code is self-documenting (clear names)
- [ ] Complex logic has comments
- [ ] API endpoints are documented
- [ ] Database schema is documented
- [ ] README.md is comprehensive

### Security
- [ ] No hardcoded secrets in code
- [ ] All user input is validated
- [ ] All financial operations are logged
- [ ] Soft deletes preserve audit trails
- [ ] Rate limiting is configured
- [ ] CORS is restricted appropriately

---

## Deployment Readiness Checklist

### Pre-Deployment
- [ ] All tests passing (backend & frontend)
- [ ] Code review approved
- [ ] Security review passed
- [ ] Performance acceptable
- [ ] Documentation complete

### Environment Setup
- [ ] Database created and migrated
- [ ] Environment variables configured
- [ ] Queue system configured
- [ ] Cache system configured
- [ ] Email system configured
- [ ] File storage configured

### Monitoring & Operations
- [ ] Error logging configured
- [ ] Performance monitoring configured
- [ ] Daily backups scheduled
- [ ] Monitoring alerts set up
- [ ] Incident response plan ready
- [ ] Runbooks documented

---

## Status Summary

### Overall Progress

| Phase | Tasks | Completed | % |
|-------|-------|-----------|---|
| 1 | 27 | 0 | 0% |
| 2 | 21 | 0 | 0% |
| 3 | 23 | 0 | 0% |
| 4 | 28 | 0 | 0% |
| 5 | 19 | 0 | 0% |
| 6 | 58 | 0 | 0% |
| 7 | 42 | 0 | 0% |
| 8 | 11 | 0 | 0% |
| 9 | 35 | 0 | 0% |
| **Total** | **264** | **0** | **0%** |

### Next Steps
1. Assign team members to phases
2. Set up development environment
3. Create feature branches in Git
4. Begin Phase 1 work
5. Schedule daily standups

---

**Last Updated:** July 22, 2026  
**Next Review:** End of Week 1
