# Phase 2: Authentication & Authorization (via Spatie)

**Duration:** Week 1-2  
**Priority:** HIGH  
**Objective:** Secure user access and role-based permissions using Spatie Laravel Permission

---

## Overview

Phase 2 implements authentication (login/logout) and authorization (role-based access control) using Laravel Sanctum and Spatie Laravel Permission. This phase is critical for securing all subsequent endpoints.

---

## Section 1: Install & Configure Spatie

### 1.1 Install Package

```bash
cd backend
composer require spatie/laravel-permission
```

### 1.2 Publish Migrations & Configuration

```bash
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider" --tag="config"
```

### 1.3 Run Migrations

```bash
php artisan migrate
```

This creates Spatie's tables:
- `permissions`
- `roles`
- `role_has_permissions`
- `model_has_roles`
- `model_has_permissions`

### 1.4 Clear Cache

```bash
php artisan config:cache
```

---

## Section 2: User Model Setup

Update the User model to include Spatie's HasRoles trait:

**File:** `app/Models/User.php`

```php
<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable, HasRoles;

    protected $fillable = [
        'name',
        'email',
        'password',
        'user_type_id',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // Relationships
    public function userType()
    {
        return $this->belongsTo(UserType::class);
    }

    public function agencyUsers()
    {
        return $this->hasMany(AgencyUser::class);
    }

    public function agencies()
    {
        return $this->belongsToMany(TravelAgency::class, 'agency_users');
    }

    // Helper methods
    public function isSuperAdmin(): bool
    {
        return $this->hasRole('super_admin');
    }

    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    public function isAgency(): bool
    {
        return $this->hasRole('agency');
    }
}
```

---

## Section 3: Create UserType Model

**File:** `app/Models/UserType.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserType extends Model
{
    protected $fillable = ['name', 'description'];

    public function users()
    {
        return $this->hasMany(User::class);
    }
}
```

---

## Section 4: Create Roles & Permissions Seeder

**Create the seeder:**

```bash
php artisan make:seeder RolesAndPermissionsSeeder
```

**File:** `database/seeders/RolesAndPermissionsSeeder.php`

```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run()
    {
        // Reset cache
        app()['cache']->forget('spatie.permission.cache');

        // Create all permissions
        $permissions = [
            // Order permissions
            'order:create',
            'order:view',
            'order:price',
            'order:approve',

            // Wallet permissions
            'wallet:view',
            'wallet:topup',
            'wallet:set-credit-limit',

            // Refund permissions
            'refund:request',
            'refund:view',
            'refund:approve',

            // Admin permissions
            'users:create',
            'users:edit',
            'users:delete',
            'permissions:manage',
            'agencies:manage',
            'reports:view',
            'settings:manage',
            'feature-flags:manage',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create roles
        $superAdminRole = Role::firstOrCreate(['name' => 'super_admin']);
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $agencyRole = Role::firstOrCreate(['name' => 'agency']);

        // Super Admin: all permissions
        $superAdminRole->syncPermissions(Permission::all());

        // Admin: most permissions except user management
        $adminRole->syncPermissions([
            'order:view',
            'order:price',
            'order:approve',
            'wallet:view',
            'wallet:topup',
            'wallet:set-credit-limit',
            'refund:view',
            'refund:approve',
            'agencies:manage',
            'reports:view',
            'settings:manage',
        ]);

        // Agency: limited permissions
        $agencyRole->syncPermissions([
            'order:create',
            'order:view',
            'order:approve',
            'wallet:view',
            'refund:request',
            'refund:view',
        ]);
    }
}
```

Run the seeder:

```bash
php artisan db:seed --class=RolesAndPermissionsSeeder
```

---

## Section 5: Authentication Controllers

### 5.1 Create LoginController

**File:** `app/Http/Controllers/Auth/LoginController.php`

```php
<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class LoginController extends Controller
{
    public function login(LoginRequest $request)
    {
        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Invalid credentials',
            ], 401);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $this->formatUser($user),
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully']);
    }

    public function currentUser(Request $request)
    {
        return response()->json([
            'user' => $this->formatUser($request->user()),
        ]);
    }

    private function formatUser(User $user)
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'user_type' => $user->userType?->name,
            'roles' => $user->roles->pluck('name')->toArray(),
            'permissions' => $user->getAllPermissions()->pluck('name')->toArray(),
        ];
    }
}
```

### 5.2 Create LoginRequest

**File:** `app/Http/Requests/Auth/LoginRequest.php`

```php
<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => 'required|email|exists:users',
            'password' => 'required|string|min:6',
        ];
    }
}
```

---

## Section 6: API Routes

**File:** `routes/api.php`

```php
<?php

use App\Http\Controllers\Auth\LoginController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [LoginController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [LoginController::class, 'logout']);
    Route::get('/user', [LoginController::class, 'currentUser']);

    // Protected routes will be added in subsequent phases
});
```

---

## Section 7: Authorization Policies

### 7.1 Create OrderPolicy

**File:** `app/Policies/OrderPolicy.php`

```bash
php artisan make:policy OrderPolicy --model=Order
```

```php
<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    public function create(User $user): bool
    {
        return $user->isSuperAdmin() || $user->can('order:create');
    }

    public function view(User $user, Order $order): bool
    {
        return $user->isSuperAdmin() || $user->can('order:view');
    }

    public function price(User $user, Order $order): bool
    {
        return $user->isSuperAdmin() || $user->can('order:price');
    }

    public function approve(User $user, Order $order): bool
    {
        return $user->isSuperAdmin() || $user->can('order:approve');
    }
}
```

Create similar policies for:
- `WalletPolicy`
- `RefundPolicy`
- `TravelAgencyPolicy`
- `UserPolicy`

---

## Section 8: Middleware

### 8.1 Register Policies

**File:** `app/Providers/AppServiceProvider.php`

```php
<?php

namespace App\Providers;

use App\Models\Order;
use App\Models\TravelAgency;
use App\Models\User;
use App\Models\Wallet;
use App\Models\Refund;
use App\Policies\OrderPolicy;
use App\Policies\TravelAgencyPolicy;
use App\Policies\UserPolicy;
use App\Policies\WalletPolicy;
use App\Policies\RefundPolicy;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Register policies
        $this->registerPolicies();
    }

    private function registerPolicies()
    {
        gate()->policy(Order::class, OrderPolicy::class);
        gate()->policy(User::class, UserPolicy::class);
        gate()->policy(TravelAgency::class, TravelAgencyPolicy::class);
        gate()->policy(Wallet::class, WalletPolicy::class);
        gate()->policy(Refund::class, RefundPolicy::class);
    }
}
```

---

## Section 9: Frontend Setup

### 9.1 Create Auth Store

**File:** `frontend/src/stores/auth.js`

```javascript
import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import axios from 'axios';

const API_URL = import.meta.env.VITE_API_URL || 'http://localhost:8000/api';

export const useAuthStore = defineStore('auth', () => {
  const user = ref(null);
  const token = ref(localStorage.getItem('auth_token'));
  const isAuthenticated = computed(() => !!token.value);

  // Set up axios default header
  if (token.value) {
    axios.defaults.headers.common['Authorization'] = `Bearer ${token.value}`;
  }

  const login = async (email, password) => {
    try {
      const response = await axios.post(`${API_URL}/login`, {
        email,
        password,
      });

      token.value = response.data.token;
      user.value = response.data.user;

      localStorage.setItem('auth_token', token.value);
      axios.defaults.headers.common['Authorization'] = `Bearer ${token.value}`;

      return { success: true };
    } catch (error) {
      return {
        success: false,
        message: error.response?.data?.message || 'Login failed',
      };
    }
  };

  const logout = async () => {
    try {
      await axios.post(`${API_URL}/logout`);
    } catch (error) {
      console.error('Logout error:', error);
    } finally {
      token.value = null;
      user.value = null;
      localStorage.removeItem('auth_token');
      delete axios.defaults.headers.common['Authorization'];
    }
  };

  const fetchCurrentUser = async () => {
    if (!token.value) return;

    try {
      const response = await axios.get(`${API_URL}/user`);
      user.value = response.data.user;
    } catch (error) {
      console.error('Fetch user error:', error);
      logout();
    }
  };

  const hasPermission = (permission) => {
    if (!user.value) return false;
    return user.value.permissions?.includes(permission) || false;
  };

  const hasRole = (role) => {
    if (!user.value) return false;
    return user.value.roles?.includes(role) || false;
  };

  const isSuperAdmin = computed(() => hasRole('super_admin'));
  const isAdmin = computed(() => hasRole('admin'));
  const isAgency = computed(() => hasRole('agency'));

  return {
    user,
    token,
    isAuthenticated,
    isSuperAdmin,
    isAdmin,
    isAgency,
    login,
    logout,
    fetchCurrentUser,
    hasPermission,
    hasRole,
  };
});
```

### 9.2 Create Permission Composables

**File:** `frontend/src/composables/usePermissions.js`

```javascript
import { computed } from 'vue';
import { useAuthStore } from '@/stores/auth';

export function useCanI(permission) {
  const auth = useAuthStore();
  return computed(() => auth.hasPermission(permission));
}

export function useHasRole(role) {
  const auth = useAuthStore();
  return computed(() => auth.hasRole(role));
}

export function useIsSuperAdmin() {
  const auth = useAuthStore();
  return computed(() => auth.isSuperAdmin);
}

export function useIsAdmin() {
  const auth = useAuthStore();
  return computed(() => auth.isAdmin);
}

export function useIsAgency() {
  const auth = useAuthStore();
  return computed(() => auth.isAgency);
}
```

### 9.3 Create Login Page

**File:** `frontend/src/views/LoginPage.vue`

```vue
<template>
  <div class="min-h-screen bg-gradient-to-br from-blue-600 to-blue-800 flex items-center justify-center">
    <div class="bg-white rounded-lg shadow-lg p-8 w-full max-w-md">
      <h1 class="text-3xl font-bold text-center text-gray-800 mb-8">Barqa Travel</h1>

      <div v-if="error" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
        {{ error }}
      </div>

      <form @submit.prevent="handleLogin" class="space-y-6">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
          <input
            v-model="form.email"
            type="email"
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none"
            placeholder="admin@barqa.local"
            required
          />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Password</label>
          <input
            v-model="form.password"
            type="password"
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none"
            placeholder="••••••••"
            required
          />
        </div>

        <button
          type="submit"
          :disabled="loading"
          class="w-full bg-blue-600 text-white font-semibold py-2 rounded-lg hover:bg-blue-700 transition disabled:opacity-50"
        >
          {{ loading ? 'Signing in...' : 'Sign In' }}
        </button>
      </form>

      <div class="mt-6 p-4 bg-gray-100 rounded text-sm text-gray-600">
        <p class="font-semibold mb-2">Demo Credentials:</p>
        <p><strong>Super Admin:</strong> super@barqa.local / password</p>
        <p><strong>Admin:</strong> admin@barqa.local / password</p>
        <p><strong>Agency:</strong> agency@barqa.local / password</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useAuthStore } from '@/stores/auth';
import { useRouter } from 'vue-router';

const router = useRouter();
const auth = useAuthStore();
const loading = ref(false);
const error = ref('');
const form = ref({
  email: '',
  password: '',
});

const handleLogin = async () => {
  loading.value = true;
  error.value = '';

  const result = await auth.login(form.email, form.password);

  if (result.success) {
    // Redirect based on role
    if (auth.isSuperAdmin || auth.isAdmin) {
      router.push('/admin');
    } else if (auth.isAgency) {
      router.push('/agency');
    }
  } else {
    error.value = result.message;
  }

  loading.value = false;
};
</script>
```

### 9.4 Create Router Guards

**File:** `frontend/src/router/guards.js`

```javascript
import { useAuthStore } from '@/stores/auth';

export async function setupRouterGuards(router) {
  const auth = useAuthStore();

  router.beforeEach(async (to, from, next) => {
    // Skip auth for public routes
    if (to.meta.public) {
      return next();
    }

    // Fetch current user if not loaded
    if (!auth.user && auth.token) {
      await auth.fetchCurrentUser();
    }

    // Redirect to login if not authenticated
    if (!auth.isAuthenticated) {
      return next('/login');
    }

    // Check role-based access
    if (to.meta.requireRole) {
      if (!auth.hasRole(to.meta.requireRole)) {
        return next('/unauthorized');
      }
    }

    // Check permission-based access
    if (to.meta.requirePermission) {
      if (!auth.hasPermission(to.meta.requirePermission)) {
        return next('/unauthorized');
      }
    }

    next();
  });
}
```

### 9.5 Update Router Configuration

**File:** `frontend/src/router/index.js`

```javascript
import { createRouter, createWebHistory } from 'vue-router';
import { setupRouterGuards } from './guards';
import LoginPage from '@/views/LoginPage.vue';
import LandingPage from '@/views/LandingPage.vue';

const routes = [
  {
    path: '/',
    component: LandingPage,
    meta: { public: true },
  },
  {
    path: '/login',
    component: LoginPage,
    meta: { public: true },
  },
  {
    path: '/admin',
    component: () => import('@/layouts/AdminLayout.vue'),
    meta: { requireRole: 'admin' },
    children: [
      // Admin routes (Phase 6)
    ],
  },
  {
    path: '/agency',
    component: () => import('@/layouts/AgencyLayout.vue'),
    meta: { requireRole: 'agency' },
    children: [
      // Agency routes (Phase 7)
    ],
  },
];

const router = createRouter({
  history: createWebHistory(),
  routes,
});

setupRouterGuards(router);

export default router;
```

### 9.6 Initialize Auth on App Load

**File:** `frontend/src/main.js`

```javascript
import { createApp } from 'vue';
import { createPinia } from 'pinia';
import App from './App.vue';
import router from './router';
import { useAuthStore } from './stores/auth';

const app = createApp(App);

app.use(createPinia());
app.use(router);

// Load current user before mounting
const auth = useAuthStore();
if (auth.token) {
  await auth.fetchCurrentUser();
}

app.mount('#app');
```

---

## Section 10: Testing

### 10.1 Test Login Endpoint

```bash
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "super@barqa.local",
    "password": "password"
  }'
```

**Expected Response:**

```json
{
  "token": "1|abc123xyz...",
  "user": {
    "id": 1,
    "name": "Super Admin",
    "email": "super@barqa.local",
    "user_type": "super_admin",
    "roles": ["super_admin"],
    "permissions": ["order:create", "order:view", ...]
  }
}
```

### 10.2 Test Protected Route

```bash
curl -X GET http://localhost:8000/api/user \
  -H "Authorization: Bearer 1|abc123xyz..."
```

### 10.3 Feature Tests

**File:** `backend/tests/Feature/Auth/LoginTest.php`

```php
<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Models\UserType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_login_with_valid_credentials()
    {
        // Create user type
        $userType = UserType::create(['name' => 'admin']);

        // Create user
        $user = User::factory()->create([
            'email' => 'admin@test.local',
            'password' => bcrypt('password'),
            'user_type_id' => $userType->id,
        ]);

        // Login
        $response = $this->postJson('/api/login', [
            'email' => 'admin@test.local',
            'password' => 'password',
        ]);

        $response->assertStatus(200);
        $response->assertHasPath('token');
        $response->assertHasPath('user.email');
    }

    public function test_user_cannot_login_with_invalid_credentials()
    {
        $response = $this->postJson('/api/login', [
            'email' => 'nonexistent@test.local',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401);
    }

    public function test_user_can_logout()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->postJson('/api/logout');

        $response->assertStatus(200);
    }
}
```

---

## Section 11: Verification Checklist

- [x] Spatie installed and migrated
- [x] Roles created (super_admin, admin, agency)
- [x] Permissions created and assigned
- [x] User model updated with HasRoles trait
- [x] Login/logout endpoints implemented
- [x] Authentication middleware configured
- [x] Authorization policies created
- [x] Frontend auth store implemented
- [x] Permission composables created
- [x] Login page created
- [x] Router guards configured
- [x] Tests written

**Next:** Proceed to Phase 3 for Core Booking System
