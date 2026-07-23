# Advanced Travel Management Platform - Documentation

**Project:** Advanced Travel Management Platform (MVP)  
**Version:** 1.0  
**Status:** In Development  
**Date:** July 22, 2026

---

## Welcome! 👋

This folder contains all documentation for the Advanced Travel Management Platform project. Whether you're a new developer joining the team or looking to understand the system architecture, this guide will point you to the right resources.

---

## 📋 Documentation Files

### 1. [QUICK_START.md](QUICK_START.md) - **START HERE** ⭐
**Duration:** 30 minutes  
**Best for:** New developers, getting environment set up

- Installation instructions (backend & frontend)
- Database setup
- Running the development servers
- Login credentials for testing
- Common commands and troubleshooting
- API authentication guide

**Quick Links:**
- Backend: `http://127.0.0.1:8000`
- Frontend: `http://127.0.0.1:5173`
- Admin: `/admin`
- Agency: `/agency`

---

### 2. [DEVELOPMENT_PLAN.md](DEVELOPMENT_PLAN.md) - **PROJECT BLUEPRINT** 📐
**Duration:** Read through gradually  
**Best for:** Understanding the full project scope, planning work

**Contains:**
- **Project Overview** (§1) - What we're building and why
- **Architecture** (§2) - Tech stack, project structure, design patterns
- **9 Development Phases** (§3) - Week-by-week breakdown:
  - Phase 1: Project Setup & Database (Week 1)
  - Phase 2: Authentication & Authorization (Week 1-2)
  - Phase 3: Core Booking System (Week 2-3)
  - Phase 4: Financial Management - Wallets (Week 3-4)
  - Phase 5: Refunds & Settlement (Week 4)
  - Phase 6: Admin Dashboard (Week 4-5)
  - Phase 7: Agency Portal (Week 5-6)
  - Phase 8: Landing Page (Week 6)
  - Phase 9: Testing & Deployment (Week 6-7)
- **URL Structure** (§4) - All routes for public, admin, and agency sections
- **Database Schema Summary** (§5) - 48 tables organized by module
- **Feature Checklist** (§6) - MVP requirements mapped to SRS
- **Implementation Guidelines** (§7) - Code quality, testing strategy, deployment

**Key Sections:**
- Copy and reference the detailed phase breakdowns when starting new work
- Use the feature checklist to track progress
- Refer to constraints and business rules when implementing

---

### 3. [DATABASE_SCHEMA.md](DATABASE_SCHEMA.md) - **DATA STRUCTURE** 🗄️
**Duration:** Reference as needed  
**Best for:** Developers implementing database features

**Contains:**
- **Entity Relationship Diagram** (§1) - Visual relationships between tables
- **Standard System Columns** (§2) - Audit, timestamps, soft deletes, etc.
- **Complete Module Breakdown** (§3) - 7 modules with 48 tables:
  - Identity & Access (7 tables)
  - Agencies (4 tables)
  - Bookings (8 tables)
  - Finance & Wallet (10 tables)
  - Reference/Lookup (11 tables)
  - Audit & History (4 tables)
  - Config & System (6 tables)
- **Index Strategy** (§4) - Performance optimization
- **Constraints & Cascade Rules** (§5) - Data integrity
- **Security & Archiving** (§6) - Encryption, backup strategy

**How to Use:**
- Before creating migrations, read the table specifications
- Copy the table definitions as templates
- Ensure foreign keys follow cascade rules
- Always use DECIMAL(15,3) for money columns

---

### 4. [ARCHITECTURE.md](ARCHITECTURE.md) - **BACKEND STRUCTURE** 🏗️
**Duration:** 10 minutes
**Best for:** Developers working in the Laravel backend

- Current (not aspirational) layered/domain structure of `backend/app/`
- Responsibility of each layer: Controllers, Requests, DTOs, Services, Exceptions, Resources, Traits, Helpers
- When a DTO is created vs. a scalar passed straight through
- Enums decision and API versioning (`/api/v1`)

---

### 5. [../SRS-Document-English.pdf](../SRS-Document-English.pdf) - **REQUIREMENTS** 📄
**Duration:** Read once, reference frequently  
**Best for:** Understanding business requirements and constraints

**Contains:**
- Detailed functional requirements (FR-USR-01 through FR-DSH-04)
- Non-functional requirements (NFR-01 through NFR-07)
- Use cases (UC-01 through UC-06)
- User stories (US-ADM-01 through US-SUP-02)
- Data model and workflow diagrams
- Assumptions and constraints

**Key Sections:**
- **Functional Requirements** (§03) - What the system must do
- **Use Cases** (§04) - Detailed workflows with actors
- **Data Model** (§08) - ERD and workflow diagram
- **Database Design** (§09) - Complete schema specification

---

## 🎯 Project at a Glance

### The Problem
Travel booking coordination between platform and agencies is currently **ad-hoc and error-prone**. Communication is scattered, orders are tracked manually, and financial reconciliation is complex.

### The Solution
A **centralized, digitized platform** that:
- Structures the complete booking cycle (request → pricing → approval → ticket issuance)
- Provides real-time dashboards for both admin and agencies
- Implements secure wallet & credit management
- Maintains complete audit trails for compliance
- Supports future expansion (B2C, GDS integration, payment gateways)

### MVP Scope
- **Travel Agencies (B2B)** as exclusive business user
- **Manual ticket request cycle** via admin dashboard
- **Hybrid pricing** (mock for now, manual from live gateways)
- **Offline Sudanese airline route** (manual, no APIs)
- **Digital wallet & credit system**

### Success Criteria
✅ Agencies can create requests  
✅ Admins can price & provision tickets  
✅ Agencies can approve and pay  
✅ Complete financial tracking & audit trail  
✅ Real-time dashboards  
✅ Secure, role-based access  

---

## 🏗️ Technology Stack

### Backend (Laravel 11)
```
Laravel 11 + PHP 8.3
├── REST API with Sanctum auth
├── MySQL 8.0 database
├── Queue system for async tasks
└── Pest testing framework
```

### Frontend (Vue 3)
```
Vue 3 + Vite
├── Vue Router for navigation
├── Pinia for state management
├── Tailwind CSS for styling
└── Vitest for testing
```

### Key Patterns
- RESTful API design
- Feature flags for extensibility
- Immutable transaction logs
- Role-based access control
- Plug-and-play architecture

---

## 📍 URL Structure

### Public Routes
```
/                    Landing page
/login               User login
/about               About us
/contact             Contact info
```

### Admin Panel (`/admin`)
```
/admin                          Dashboard & overview
/admin/orders                   Queue of pending orders
/admin/orders/:id/price         Pricing & provisioning form
/admin/agencies                 Agency management
/admin/wallet                   Wallet operations
/admin/refunds                  Refund approvals
/admin/reports                  Financial reports
/admin/users                    Admin user management
/admin/settings                 Feature flags & settings
```

### Agency Portal (`/agency`)
```
/agency                         Dashboard
/agency/orders                  My orders list
/agency/orders/new              Create new order
/agency/orders/:id              Order details
/agency/orders/:id/quote        Review & approve quote
/agency/wallet                  Wallet & balance
/agency/reports                 Financial reports
```

### API Routes (`/api/v1`)
All API endpoints are RESTful, versioned under `/api/v1`. See [ARCHITECTURE.md](ARCHITECTURE.md) for how the backend code behind them is organized:
```
POST   /api/v1/login                Login (returns token)
GET    /api/v1/user                 Current user info
GET    /api/v1/orders               List orders
POST   /api/v1/orders               Create order
GET    /api/v1/orders/:id           Order details
PUT    /api/v1/orders/:id/quote     Price & send offer
PUT    /api/v1/orders/:id/approve   Approve booking
GET    /api/v1/wallets/:id          Wallet details
POST   /api/v1/wallets/:id/topup    Top-up wallet
GET    /api/v1/refunds              Pending refunds
POST   /api/v1/refunds/:id/approve  Approve refund
```

---

## 🚀 Getting Started

### 1. First Day (Setup)
- Read [QUICK_START.md](QUICK_START.md)
- Clone the repository
- Set up backend: `cd backend && composer install && php artisan migrate`
- Set up frontend: `cd frontend && npm install && npm run dev`
- Test login with provided credentials
- Explore the UI in browser

### 2. First Week (Foundation)
- Read [DEVELOPMENT_PLAN.md](DEVELOPMENT_PLAN.md) Phases 1-2
- Familiarize with database schema [DATABASE_SCHEMA.md](DATABASE_SCHEMA.md)
- Review SRS requirements for assigned features
- Set up your development environment (Git, editor, tools)
- Attend project kickoff and technical architecture review

### 3. Implementation (Ongoing)
- Work through phases sequentially
- Reference requirements from SRS
- Check database schema before implementing features
- Write tests as you go
- Follow code quality guidelines in DEVELOPMENT_PLAN.md
- Submit PRs with clear descriptions

---

## 📊 Development Phases

The project is organized into 9 phases spanning 7 weeks:

| Phase | Duration | Focus | Status |
|-------|----------|-------|--------|
| 1 | Week 1 | Database & Project Setup | Pending |
| 2 | Week 1-2 | Authentication & Authorization | Pending |
| 3 | Week 2-3 | Core Booking System | Pending |
| 4 | Week 3-4 | Wallet & Financial Management | Pending |
| 5 | Week 4 | Refunds & Settlement | Pending |
| 6 | Week 4-5 | Admin Dashboard | Pending |
| 7 | Week 5-6 | Agency Portal | Pending |
| 8 | Week 6 | Landing Page | Pending |
| 9 | Week 6-7 | Testing & Deployment | Pending |

**Track progress:** Update phase status in [DEVELOPMENT_PLAN.md](DEVELOPMENT_PLAN.md#development-phases)

---

## 🎓 Key Concepts to Understand

### 1. Order Workflow
```
Agency creates request
        ↓
Admin receives notification
        ↓
Admin prices & provisions manually
        ↓
Quote sent to agency
        ↓
Agency approves (credit check)
        ↓
Wallet debited, ticket issued
```

### 2. Wallet System
- Each agency has ONE wallet (1:1 relationship)
- Balance can go negative within credit_limit
- All changes go through immutable transaction log
- Balance is cached but computed from transactions
- Updates use DB::transaction with row locks

### 3. Sudanese Carrier Route
- Special handling for Badr, Tarco, Sudan Airways
- NO API calls—always manual
- Excluded from future GDS automation
- Mandatory admin approval for refunds

### 4. Plug-and-Play Architecture
- Features behind flags: B2C, GDS, payment gateways
- Enable/disable from settings without code changes
- Ready for future expansion

---

## 🔗 Cross-References

### SRS → DEVELOPMENT_PLAN Mapping
- **Functional Requirements (§03)** → Organized into phases in DEVELOPMENT_PLAN
- **Use Cases (§04)** → Detailed in respective phase task descriptions
- **User Stories (§05)** → Listed in phase frontend components
- **Data Model (§08)** → Complete schema in DATABASE_SCHEMA.md

### Example: Booking Workflow
```
SRS Requirement: FR-REQ-01 (Agency creates request)
  ↓
Development: Phase 3, Task 1 "Order Creation Flow"
  ↓
Implementation: OrderController, OrderForm.vue, orders table
  ↓
Testing: UC-01 "Submit a ticket request"
  ↓
Database: orders, order_segments, order_passengers tables
```

---

## 💻 Development Tips

### Before Starting Work
1. Check [DEVELOPMENT_PLAN.md](DEVELOPMENT_PLAN.md) for the phase you're in
2. Read the relevant SRS requirements
3. Review database schema in [DATABASE_SCHEMA.md](DATABASE_SCHEMA.md)
4. Check feature checklist to avoid duplicates

### While Implementing
1. Write tests as you code (TDD mindset)
2. Follow naming conventions in DEVELOPMENT_PLAN.md
3. Use services for business logic, controllers for routing
4. Eager load relationships (`with()`) to avoid N+1 queries
5. Use immutable transaction logs for financial records

### When Reviewing Code
1. Check database relationships match schema
2. Ensure proper authorization checks
3. Verify no hardcoded values (use reference tables)
4. Check financial operations use DB::transaction
5. Ensure tests cover happy path + edge cases

---

## 🐛 Troubleshooting

**Setup Issues?** → Check [QUICK_START.md](QUICK_START.md#common-issues--solutions)

**Database Questions?** → Check [DATABASE_SCHEMA.md](DATABASE_SCHEMA.md)

**Feature Requirements?** → Check [SRS-Document-English.pdf](../SRS-Document-English.pdf)

**Development Questions?** → Check [DEVELOPMENT_PLAN.md](DEVELOPMENT_PLAN.md#implementation-guidelines)

---

## 📞 Contact & Support

- **Project Lead:** [To be added]
- **Tech Lead:** [To be added]
- **QA Lead:** [To be added]

### Meetings
- **Standup:** Daily 10:00 AM
- **Code Review:** Wednesday 3:00 PM
- **Sprint Planning:** Every Monday 9:00 AM
- **Demo:** Every Friday 4:00 PM

---

## 📚 Additional Resources

### Official Documentation
- [Laravel Docs](https://laravel.com/docs)
- [Vue 3 Docs](https://vuejs.org/)
- [Pinia Docs](https://pinia.vuejs.org/)
- [Tailwind CSS](https://tailwindcss.com/)
- [MySQL Docs](https://dev.mysql.com/doc/)

### Design References
- UI Mockups: `/ui images/` folder
- Design System: [To be created]
- API Specs: [To be created]

### Project References
- Requirements: `SRS-Document-English.pdf`
- Feature List: `DEVELOPMENT_PLAN.md#feature-checklist`
- Database: `DATABASE_SCHEMA.md`

---

## ✅ Project Checklist

### Pre-Development
- [ ] Clone repository
- [ ] Install dependencies (backend + frontend)
- [ ] Configure database
- [ ] Run migrations & seeders
- [ ] Start dev servers
- [ ] Test login with provided credentials
- [ ] Explore UI with design references

### During Development
- [ ] Follow phases in DEVELOPMENT_PLAN.md
- [ ] Reference SRS for requirements
- [ ] Check DATABASE_SCHEMA.md before DB work
- [ ] Write tests alongside code
- [ ] Submit PRs with clear descriptions
- [ ] Update phase progress

### Before Deployment
- [ ] All tests passing (>80% coverage)
- [ ] Code review approved
- [ ] Database migrations ready
- [ ] Environment variables configured
- [ ] API documentation updated
- [ ] Deployment checklist complete

---

## 🎉 You're Ready!

Everything you need to succeed is documented. Start with [QUICK_START.md](QUICK_START.md), then dive into [DEVELOPMENT_PLAN.md](DEVELOPMENT_PLAN.md) for your assigned phase.

**Questions?** Check the relevant documentation file above, and if not found, ask your team lead.

**Happy coding! 🚀**

---

**Document Version:** 1.0  
**Last Updated:** July 22, 2026  
**Next Update:** Upon first phase completion
