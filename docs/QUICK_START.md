# Quick Start Guide

**Advanced Travel Management Platform MVP**  
**Get up and running in 30 minutes**

---

## Prerequisites

- **PHP:** 8.3+
- **Node.js:** 18+
- **MySQL:** 8.0+
- **Git:** Latest version
- **Composer:** Latest version
- **npm or yarn:** Package manager

---

## Project Structure Overview

```
barqagency/
├── backend/                 # Laravel API (PHP)
│   ├── app/Models/         # Database models
│   ├── app/Services/       # Business logic
│   ├── app/Http/Controllers/
│   ├── database/migrations/
│   ├── routes/api.php
│   └── .env.example        # Copy to .env
│
├── frontend/               # Vue.js SPA (JavaScript)
│   ├── src/
│   │   ├── components/
│   │   ├── views/
│   │   ├── router/         # Route definitions
│   │   ├── stores/         # Pinia state management
│   │   └── main.js
│   ├── vite.config.js
│   └── package.json
│
├── docs/                   # Documentation
│   ├── DEVELOPMENT_PLAN.md # Full development plan
│   ├── DATABASE_SCHEMA.md  # Schema details
│   └── QUICK_START.md      # This file
│
└── ui images/              # UI mockups and design references
```

---

## Backend Setup (Laravel)

### 1. Install Dependencies

```bash
cd backend
composer install

# Install Spatie Laravel Permission (for role-based access control)
composer require spatie/laravel-permission
```

### 2. Configure Environment

```bash
# Copy example env file
cp .env.example .env

# Generate app key
php artisan key:generate
```

### 3. Configure Spatie Permission

```bash
# Publish Spatie's migrations and config
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider" --tag="config"

# Clear config cache
php artisan config:cache
```

### 4. Configure Database

Edit `.env` and update database credentials:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=barqa_travel
DB_USERNAME=root
DB_PASSWORD=
```

### 5. Create Database & Run Migrations

```bash
# Create database
mysql -u root -p -e "CREATE DATABASE barqa_travel CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Run migrations (includes Spatie's role/permission tables)
php artisan migrate

# Seed reference data (countries, airlines, roles, permissions, etc.)
php artisan db:seed
```

### 6. Create Storage Link (for file uploads)

```bash
php artisan storage:link
```

### 7. Start Development Server

```bash
php artisan serve
```

Server runs at: `http://127.0.0.1:8000`

---

## Frontend Setup (Vue.js)

### 1. Install Dependencies

```bash
cd frontend
npm install
```

### 2. Configure API Endpoint

Edit `src/main.js` or create `.env`:

```env
VITE_API_URL=http://127.0.0.1:8000/api
```

### 3. Start Development Server

```bash
npm run dev
```

Frontend runs at: `http://127.0.0.1:5173`

---

## Verify Installation

### Backend Health Check

```bash
# Check API is running
curl http://127.0.0.1:8000/api/health

# Expected response: {"status":"ok"}
```

### Frontend Health Check

Navigate to `http://127.0.0.1:5173` in your browser. You should see the landing page.

---

## Login Credentials (Development)

After running seeders, use these credentials:

### Super Admin
- **Email:** `super@barqa.local`
- **Password:** `password`
- **Access:** `http://127.0.0.1:5173/admin`

### Regular Admin
- **Email:** `admin@barqa.local`
- **Password:** `password`
- **Access:** `http://127.0.0.1:5173/admin`

### Travel Agency
- **Email:** `agency@barqa.local`
- **Password:** `password`
- **Access:** `http://127.0.0.1:5173/agency`

---

## Key URLs

### Admin Panel
```
http://127.0.0.1:5173/admin
```

### Agency Portal
```
http://127.0.0.1:5173/agency
```

### Public Landing Page
```
http://127.0.0.1:5173
```

### API Base URL
```
http://127.0.0.1:8000/api
```

---

## Development Workflow

### Phase 1: Database & API
1. Create migration: `php artisan make:migration create_orders_table`
2. Edit migration file
3. Run migration: `php artisan migrate`
4. Create model: `php artisan make:model Order`
5. Create controller: `php artisan make:controller OrderController`
6. Define routes in `routes/api.php`
7. Test API endpoints with Postman/Insomnia

### Phase 2: Frontend
1. Create Vue component: `frontend/src/components/OrderForm.vue`
2. Create route in `router/index.js`
3. Use Pinia store for state management
4. Test component in browser

### Phase 3: Integration
1. Connect frontend to API endpoints
2. Test full workflow
3. Debug issues with browser console + Laravel logs

---

## Common Commands

### Backend (Laravel)

```bash
# Create model + migration + controller
php artisan make:model Order -mcr

# Run tests
php artisan test

# Clear cache
php artisan cache:clear

# Database
php artisan migrate:rollback      # Undo last migration
php artisan migrate:fresh         # Fresh database (⚠️ destructive)
php artisan db:seed               # Run seeders

# Debugging
php artisan tinker               # Interactive shell
```

### Frontend (Vue.js)

```bash
# Dev server
npm run dev

# Build for production
npm run build

# Run tests
npm run test

# Lint code
npm run lint
```

---

## File Organization Best Practices

### Backend Structure

**Models** (`app/Models/Order.php`)
- Database mapping
- Relationships
- Scopes for queries
- Accessors/mutators

**Services** (`app/Services/OrderService.php`)
- Business logic
- Complex operations
- Reusable functions
- No framework dependencies

**Controllers** (`app/Http/Controllers/OrderController.php`)
- Route handling
- Request validation
- Calling services
- Returning responses

**Requests** (`app/Http/Requests/StoreOrderRequest.php`)
- Input validation
- Authorization
- Transformation

**Policies** (`app/Policies/OrderPolicy.php`)
- Authorization rules
- Used by middleware

**Observers** (`app/Observers/OrderObserver.php`)
- Model event listeners
- Automatic status logging

### Frontend Structure

**Components** (`src/components/OrderForm.vue`)
- Reusable UI pieces
- Props + emit pattern
- Pure presentation logic

**Views** (`src/views/OrdersPage.vue`)
- Full page components
- Compose smaller components
- Page-level logic

**Stores** (`src/stores/orderStore.js`)
- State management (Pinia)
- API calls
- State mutations

**Composables** (`src/composables/useOrders.js`)
- Reusable logic (non-UI)
- Form handling
- Custom hooks

**Router** (`src/router/index.js`)
- Route definitions
- Meta guards
- Authentication checks

---

## Database Seeding

### Run All Seeders

```bash
php artisan db:seed
```

### Run Specific Seeder

```bash
php artisan db:seed --class=AgenciesSeeder
```

### Key Seeders to Create

1. **ReferenceSeeder** - Countries, currencies, airlines
2. **UsersSeeder** - Super admin, admins
3. **AgenciesSeeder** - Test agencies
4. **AirlinesSeeder** - Sudanese carriers (Badr, Tarco, Sudan Airways)

---

## API Authentication

### Login & Get Token

```bash
curl -X POST http://127.0.0.1:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@barqa.local",
    "password": "password"
  }'
```

**Response:**
```json
{
  "token": "1|abc123xyz789...",
  "user": {
    "id": 1,
    "email": "admin@barqa.local",
    "name": "Admin User"
  }
}
```

### Use Token in Requests

```bash
curl -X GET http://127.0.0.1:8000/api/orders \
  -H "Authorization: Bearer 1|abc123xyz789..."
```

### Frontend (Axios)

```javascript
// In composable or store
const token = localStorage.getItem('auth_token');
const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL,
  headers: {
    'Authorization': `Bearer ${token}`
  }
});
```

---

## Testing

### Backend Tests

```bash
cd backend

# Run all tests
php artisan test

# Run specific test
php artisan test tests/Feature/Orders/CreateOrderTest.php

# With coverage
php artisan test --coverage
```

### Frontend Tests

```bash
cd frontend

# Run tests
npm run test

# Watch mode
npm run test:watch
```

---

## Debugging

### Backend
- **Logs:** `backend/storage/logs/laravel.log`
- **Debugbar:** Access `http://127.0.0.1:8000/` and check bottom bar
- **Tinker:** `php artisan tinker` for interactive queries

### Frontend
- **DevTools:** F12 in browser
- **Vue DevTools:** Install browser extension
- **Network tab:** Check API calls
- **Console:** Check for JavaScript errors

---

## Common Issues & Solutions

### Database Connection Error
```
SQLSTATE[HY000]: General error: 1030 Got error...
```
**Solution:** Check `.env` database credentials and ensure MySQL is running.

### CORS Error
```
Access to XMLHttpRequest has been blocked by CORS policy
```
**Solution:** Check `config/cors.php` in backend allows frontend origin.

### Vue Component Not Loading
```
Cannot find module './OrderForm.vue'
```
**Solution:** Check file path and naming (use PascalCase for component files).

### Port Already in Use
```
Port 8000 is already in use
```
**Solution:** Kill process or use different port: `php artisan serve --port=8001`

---

## Performance Optimization

### Backend
1. **Caching:** Use Redis for session/cache
2. **Indexes:** Ensure database indexes on FKs
3. **Eager Loading:** Use `with()` to avoid N+1 queries
4. **Pagination:** Always paginate large result sets

### Frontend
1. **Lazy Loading:** Lazy load routes and components
2. **Code Splitting:** Let Vite handle bundling
3. **Memoization:** Use `computed()` for derived state
4. **Virtualization:** Use virtual lists for large data

---

## Next Steps

1. **Read the Full Plan:** `docs/DEVELOPMENT_PLAN.md`
2. **Understand the Schema:** `docs/DATABASE_SCHEMA.md`
3. **Start Phase 1:** Create database tables and models
4. **Implement Phase 2:** Authentication system
5. **Build Incrementally:** Follow the phased approach

---

## Getting Help

### Documentation
- Laravel docs: https://laravel.com/docs
- Vue 3 docs: https://vuejs.org/
- Pinia docs: https://pinia.vuejs.org/

### Project Docs
- Development Plan: `docs/DEVELOPMENT_PLAN.md`
- Database Schema: `docs/DATABASE_SCHEMA.md`
- SRS Document: `SRS-Document-English.pdf`

### Debugging
- Check logs: `backend/storage/logs/laravel.log`
- Use tinker: `php artisan tinker`
- Use browser DevTools: F12
- Check network requests: DevTools → Network tab

---

## Team Guidelines

### Code Style
- **Backend:** PSR-12 (Laravel standard)
- **Frontend:** Prettier + ESLint (Vue 3)
- **Commits:** Conventional commits (feat:, fix:, docs:, etc.)

### Branching
```
main                    # Production
├── develop             # Integration branch
├── feature/order-api   # Feature branches
├── bugfix/wallet-sync  # Bug fixes
└── docs/schema         # Documentation
```

### Pull Request Process
1. Create feature branch from `develop`
2. Make changes
3. Write tests
4. Submit PR with description
5. Code review (min. 1 approval)
6. Merge to `develop`

---

**Happy coding! 🚀**

For detailed information, refer to `DEVELOPMENT_PLAN.md`
