# Implementation Phases Overview

**Project:** Advanced Travel Management Platform (MVP)  
**Total Duration:** 7 weeks (9 phases)  
**Status:** Ready for Implementation

---

## Quick Navigation

Each phase has a dedicated implementation guide with step-by-step instructions and code examples:

| Phase | Duration | Status | Guide |
|-------|----------|--------|-------|
| **1** | Week 1 | ✅ | [PHASE_1_SETUP.md](PHASE_1_SETUP.md) |
| **2** | Week 1-2 | ✅ | [PHASE_2_AUTH.md](PHASE_2_AUTH.md) |
| **3** | Week 2-3 | ✅ | [PHASE_3_BOOKING.md](PHASE_3_BOOKING.md) |
| **4** | Week 3-4 | ✅ | [PHASE_4_WALLET.md](PHASE_4_WALLET.md) |
| **5** | Week 4 | ✅ | [PHASE_5_REFUNDS.md](PHASE_5_REFUNDS.md) |
| **6** | Week 4-5 | ✅ | [PHASE_6_ADMIN.md](PHASE_6_ADMIN.md) |
| **7** | Week 5-6 | ✅ | [PHASE_7_AGENCY.md](PHASE_7_AGENCY.md) |
| **8** | Week 6 | ✅ | [PHASE_8_LANDING.md](PHASE_8_LANDING.md) |
| **9** | Week 6-7 | ✅ | [PHASE_9_TESTING.md](PHASE_9_TESTING.md) |

✅ = Complete implementation guide available with code examples

---

## Phase 1: Project Setup & Database Design

**Duration:** Week 1  
**Owner:** Backend Lead  
**Key Deliverables:**
- All 48 database tables created
- Database migrations and seeders
- Reference data populated
- Test data for development

**What You'll Create:**
- ✅ Database migrations (users, agencies, orders, wallets, etc.)
- ✅ Model factories for testing
- ✅ Seeders for reference data
- ✅ Test admin accounts

**Success Criteria:**
- All migrations run successfully
- Database connection verified
- Seeders populate data correctly
- Admin users created with credentials

**Read:** [PHASE_1_SETUP.md](PHASE_1_SETUP.md)

---

## Phase 2: Authentication & Authorization (via Spatie)

**Duration:** Week 1-2  
**Owner:** Backend Lead  
**Key Deliverables:**
- User authentication (login/logout)
- Role-based access control via Spatie
- Permission system implementation
- Frontend auth state management

**What You'll Create:**
- ✅ Spatie Laravel Permission setup
- ✅ 3 roles: super_admin, admin, agency
- ✅ 15+ permissions (order, wallet, refund, admin)
- ✅ LoginController with Sanctum tokens
- ✅ Authorization policies (Order, User, etc.)
- ✅ Frontend auth store (Pinia)
- ✅ Permission composables
- ✅ Login page & router guards

**Success Criteria:**
- Login endpoint returns JWT token
- User endpoint returns roles & permissions
- Frontend stores token in localStorage
- Route guards prevent unauthorized access
- Login page functional and styled

**Read:** [PHASE_2_AUTH.md](PHASE_2_AUTH.md)

---

## Phase 3: Core Booking System (Orders & Requests)

**Duration:** Week 2-3  
**Owner:** Full Stack Team  
**Key Deliverables:**
- Agency order creation workflow
- Admin pricing & provisioning interface
- Order status tracking & history
- Sudanese domestic airline route

**What You'll Create:**
- ✅ Order model with status state machine
- ✅ OrderSegment (flight legs) and OrderPassenger models
- ✅ Quote model for price proposals
- ✅ OrderStatusHistory for audit trail
- ✅ OrderController endpoints (create, price, approve)
- ✅ Order form component (agency side)
- ✅ Pricing form component (admin side)
- ✅ Order queue for admin dashboard
- ✅ Sudanese carrier logic (local route, no API calls)

**Key Components:**
- `OrderForm.vue` - Flight details form
- `OrderList.vue` - Agency's orders
- `OrderQueue.vue` - Admin pending orders
- `PricingForm.vue` - Manual pricing interface
- `QuoteReview.vue` - Agency quote approval

**Success Criteria:**
- Agency can create ticket request
- Request appears immediately on admin dashboard
- Admin can price and send binding offer
- Agency can approve quote with validation
- Order status changes logged in history

---

## Phase 4: Financial Management (Wallet & Ledger)

**Duration:** Week 3-4  
**Owner:** Finance Backend Team  
**Key Deliverables:**
- Wallet system with balance tracking
- Transaction log (append-only)
- Wallet top-up workflow
- Credit limit management
- Invoice generation

**What You'll Create:**
- ✅ Wallet model (1:1 with Agency)
- ✅ WalletTransaction append-only log
- ✅ WalletService for all operations
- ✅ Invoice and InvoiceItem models
- ✅ WalletController (top-up, set credit limit)
- ✅ WalletDashboard component
- ✅ Transaction statement view
- ✅ Credit limit form (admin)

**Key Business Logic:**
- Balance can go negative within credit_limit
- All transactions use DB::transaction with locks
- Wallets cached but computed from transactions
- Invoices created on order confirmation

**Success Criteria:**
- Admin can top-up agency wallet
- Balance updates in real-time
- Credit limit blocks bookings if exceeded
- Transaction log is immutable
- Invoices generated and linked to wallets

---

## Phase 5: Refunds & Financial Settlement

**Duration:** Week 4  
**Owner:** Finance Backend Team  
**Key Deliverables:**
- Refund request workflow
- Refund calculation & approval
- Credit notes generation
- Ledger reconciliation

**What You'll Create:**
- ✅ Refund model with dynamic calculations
- ✅ CreditNote model (1:1 with Refund)
- ✅ RefundService for approvals
- ✅ RefundController endpoints
- ✅ RefundQueue component (admin)
- ✅ RefundRequest component (agency)
- ✅ CreditNoteView for display

**Key Business Logic:**
- Separate supplier_penalty from platform_fee
- Mandatory admin approval for Sudanese carriers
- Refund status: pending → approved → credited
- Credit notes linked to original invoices

**Success Criteria:**
- Agency can request refund on bookings
- Admin can approve/reject with notes
- Approved refunds credited to wallet immediately
- Credit notes generated automatically
- Ledger stays in sync (paid vs. unpaid)

---

## Phase 6: Admin Dashboard & Control Panel

**Duration:** Week 4-5  
**Owner:** Frontend Team  
**Key Deliverables:**
- Admin overview dashboard
- Order management queue
- Agency management interface
- Wallet operations panel
- Refund approval queue
- User & permission management
- Financial reports
- Settings & feature flags

**What You'll Create:**
- ✅ AdminDashboard.vue (overview)
- ✅ OrderQueue.vue with search & filter
- ✅ PricingForm.vue with integration
- ✅ AgencyList.vue with CRUD
- ✅ WalletTopUp.vue form
- ✅ CreditLimitForm.vue
- ✅ RefundQueue.vue with approval
- ✅ FinancialDashboard.vue (reports)
- ✅ UserManagement.vue (admin accounts)
- ✅ SettingsPanel.vue (feature flags)
- ✅ AdminLayout.vue (navigation)

**Key Features:**
- Real-time stats (pending orders, refunds, top-ups)
- Financial summary (P&L, collected vs. outstanding)
- Agency status overview with warnings
- Booking history with filters
- Ledger tracking (paid vs. unpaid)
- Net P&L reports
- Role & permission assignment

**Success Criteria:**
- Admin sees real-time overview on dashboard
- Can process all orders from queue
- Can manage agencies (edit, suspend, view)
- Can top-up wallets and set limits
- Can approve refunds with notes
- Can view detailed financial reports
- Can toggle feature flags

---

## Phase 7: Agency Portal

**Duration:** Week 5-6  
**Owner:** Frontend Team  
**Key Deliverables:**
- Order creation interface
- Order management & tracking
- Quote review & approval
- Wallet dashboard & balance
- Statement & transaction history
- Financial reports
- Localization (Arabic/English)

**What You'll Create:**
- ✅ OrderForm.vue (flight details)
- ✅ OrderList.vue with status
- ✅ OrderDetail.vue full view
- ✅ QuoteReview.vue (approval)
- ✅ WalletDashboard.vue (balance)
- ✅ StatementView.vue (transactions)
- ✅ ReportView.vue (financials)
- ✅ RefundRequest.vue (future)
- ✅ AgencyLayout.vue (navigation)
- ✅ Localization setup (i18n)

**Key Features:**
- Familiar booking interface (like live platforms)
- Real-time order status tracking
- Balance display with warnings
- Transaction history with export
- P&L summary if visible
- RTL support for Arabic

**Success Criteria:**
- Agency can create order in < 2 minutes
- Can review and approve quotes
- Sees current balance prominently
- Can view transaction history
- Can download statements
- Supports Arabic & English
- Mobile responsive

---

## Phase 8: Landing Page & Public Site

**Duration:** Week 6  
**Owner:** Frontend Team  
**Key Deliverables:**
- Public landing page
- Login page with role separation
- About & contact pages

**What You'll Create:**
- ✅ LandingPage.vue
- ✅ LoginPage.vue
- ✅ AboutPage.vue
- ✅ ContactPage.vue
- ✅ Public layout & navigation

**Success Criteria:**
- Landing page showcases platform
- Clear CTAs to admin/agency login
- About page describes company
- Contact page with form
- Mobile responsive design

---

## Phase 9: Testing & Deployment

**Duration:** Week 6-7  
**Owner:** QA Team + DevOps  
**Key Deliverables:**
- Complete test coverage (>80% backend, >70% frontend)
- Security testing & hardening
- Performance optimization
- Deployment preparation
- Production readiness

**What You'll Create:**
- ✅ Unit tests for services
- ✅ Feature tests for API endpoints
- ✅ Database transaction tests
- ✅ Authorization policy tests
- ✅ Component & store tests (frontend)
- ✅ E2E tests for critical flows
- ✅ Security audit & fixes
- ✅ Deployment scripts

**Success Criteria:**
- All tests passing
- >80% backend coverage
- >70% frontend coverage
- Security review passed
- Performance acceptable
- Documentation complete
- Ready for production deployment

---

## How to Use These Guides

### For Project Managers
1. Review the phases overview above
2. Track progress using [IMPLEMENTATION_CHECKLIST.md](IMPLEMENTATION_CHECKLIST.md)
3. Update status weekly

### For Developers
1. **Phase 1 starting?** Read [PHASE_1_SETUP.md](PHASE_1_SETUP.md)
2. **Phase 2 starting?** Read [PHASE_2_AUTH.md](PHASE_2_AUTH.md)
3. Each guide includes:
   - Objectives & deliverables
   - Step-by-step instructions
   - Code examples (copy-paste ready)
   - Database migrations
   - Test examples
   - Verification checklist

### For Architects
- Reference [DEVELOPMENT_PLAN.md](DEVELOPMENT_PLAN.md) for strategic decisions
- Reference [DATABASE_SCHEMA.md](DATABASE_SCHEMA.md) for data modeling
- Reference [SPATIE_PERMISSION_GUIDE.md](SPATIE_PERMISSION_GUIDE.md) for auth patterns

---

## Phase Dependencies

```
Phase 1 (Database)
    ↓
Phase 2 (Auth) ← Spatie setup
    ↓
Phase 3 (Booking) ← Uses auth & database
    ↓
Phase 4 (Wallet) ← Uses booking & auth
    ↓
Phase 5 (Refunds) ← Uses wallet & booking
    ↓
Phase 6 (Admin Dashboard) ← Uses phases 2-5
    ↓
Phase 7 (Agency Portal) ← Uses phases 2-5
    ↓
Phase 8 (Landing Page) ← Independent
    ↓
Phase 9 (Testing & Deploy) ← Everything
```

Each phase builds on previous ones. You must complete Phase 1 before starting Phase 2, etc.

---

## Key Files by Phase

### Phase 1
- `/backend/database/migrations/` - All migration files
- `/backend/database/seeders/` - Seeder files
- `/backend/.env` - Configuration

### Phase 2
- `/backend/app/Models/User.php` - User model
- `/backend/app/Http/Controllers/Auth/LoginController.php`
- `/backend/app/Policies/*` - Authorization policies
- `/frontend/src/stores/auth.js` - Auth store
- `/frontend/src/views/LoginPage.vue` - Login UI

### Phase 3
- `/backend/app/Models/Order.php`, `OrderSegment.php`, `OrderPassenger.php`, `Quote.php`
- `/backend/app/Http/Controllers/OrderController.php`
- `/frontend/src/components/agency/OrderForm.vue`
- `/frontend/src/components/admin/OrderQueue.vue`

### Phase 4
- `/backend/app/Models/Wallet.php`, `WalletTransaction.php`, `Invoice.php`
- `/backend/app/Services/WalletService.php`
- `/frontend/src/components/WalletDashboard.vue`

### Phase 5
- `/backend/app/Models/Refund.php`, `CreditNote.php`
- `/backend/app/Services/RefundService.php`
- `/frontend/src/components/RefundQueue.vue`

### Phase 6
- `/frontend/src/views/admin/Dashboard.vue`
- `/frontend/src/layouts/AdminLayout.vue`
- All admin components in `/frontend/src/components/admin/`

### Phase 7
- `/frontend/src/layouts/AgencyLayout.vue`
- All agency components in `/frontend/src/components/agency/`

### Phase 8
- `/frontend/src/views/LandingPage.vue`
- `/frontend/src/views/LoginPage.vue`
- `/frontend/src/views/AboutPage.vue`
- `/frontend/src/views/ContactPage.vue`

### Phase 9
- `/backend/tests/` - All test files
- `/frontend/tests/` - All test files
- Deployment scripts & documentation

---

## Testing Strategy by Phase

### Phase 1
- Migration tests (verify tables exist)
- Seeder tests (verify data populated)

### Phase 2
- Login endpoint tests
- Permission & role tests
- Policy tests

### Phase 3
- Order creation tests
- Status transition tests
- Validation tests

### Phase 4
- Wallet balance tests
- Transaction immutability tests
- Invoice generation tests

### Phase 5
- Refund calculation tests
- Credit note generation tests

### Phase 6-7
- Component unit tests
- Integration tests
- E2E tests

### Phase 8
- UI/UX tests
- Responsiveness tests

### Phase 9
- Full coverage reporting
- Security audit
- Performance testing

---

## Deployment Checklist

Before deploying to production, ensure:

- [ ] All migrations run successfully
- [ ] All tests passing (>80% backend, >70% frontend)
- [ ] Code review approved
- [ ] Security review passed
- [ ] Database backups configured
- [ ] Monitoring & logging setup
- [ ] SSL/TLS certificates installed
- [ ] Environment variables configured
- [ ] Performance acceptable
- [ ] Documentation complete

---

## Support & Questions

### Common Questions

**Q: Can I work on multiple phases in parallel?**  
A: No. Phases have dependencies. Complete Phase 1 before Phase 2, etc. Phases 6 & 7 can start before Phase 5 is done.

**Q: How long does each phase typically take?**  
A: Times in the guide are estimates. Your team's velocity may vary. Adjust timelines based on experience.

**Q: What if I get stuck?**  
A: Check the phase guide first. If still stuck, refer to [DEVELOPMENT_PLAN.md](DEVELOPMENT_PLAN.md) or [SRS-Document-English.pdf](../SRS-Document-English.pdf).

**Q: Can I skip a phase?**  
A: No. All phases are required for MVP launch. Some can be simplified, but none should be skipped.

**Q: What about future phases?**  
A: Phases beyond these 9 are listed in [DEVELOPMENT_PLAN.md](DEVELOPMENT_PLAN.md#future-enhancements-phase-2). Focus on MVP first.

---

## Progress Tracking

Use [IMPLEMENTATION_CHECKLIST.md](IMPLEMENTATION_CHECKLIST.md) to track:
- [ ] Tasks completed per phase
- [ ] Blockers encountered
- [ ] Estimated vs. actual time
- [ ] Code review status
- [ ] Test coverage

Update the checklist at end of each day/week.

---

## Next Steps

1. **Start Phase 1:** Follow [PHASE_1_SETUP.md](PHASE_1_SETUP.md)
2. **Create database:** Run all migrations
3. **Seed test data:** Run seeders
4. **Verify setup:** Check database connection
5. **Begin Phase 2:** Follow [PHASE_2_AUTH.md](PHASE_2_AUTH.md)

---

**Last Updated:** July 22, 2026  
**Version:** 1.0

Good luck with implementation! 🚀
