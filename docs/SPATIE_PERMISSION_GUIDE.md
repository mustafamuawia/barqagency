# Spatie Laravel Permission Integration Guide

**Project:** Advanced Travel Management Platform  
**Package:** spatie/laravel-permission v6.x  
**Last Updated:** July 22, 2026

---

## Overview

This project uses **Spatie Laravel Permission** for role-based access control (RBAC). Spatie is a battle-tested Laravel package that provides:

- ✅ Easy role and permission management
- ✅ Blade directives for conditional rendering
- ✅ Middleware for route protection
- ✅ Policy integration
- ✅ Caching for performance

**Why Spatie?**
- Industry standard in Laravel community
- Minimal configuration needed
- Well-documented API
- Active maintenance & support
- Better than custom implementation

---

## Installation

### Step 1: Install Package

```bash
composer require spatie/laravel-permission
```

### Step 2: Publish Migrations & Config

```bash
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider" --tag="config"
```

### Step 3: Run Migrations

```bash
php artisan migrate
```

This creates the following tables:
- `permissions` - Permission definitions
- `roles` - Role definitions
- `role_has_permissions` - Permission-to-role assignments
- `model_has_roles` - Role-to-user assignments
- `model_has_permissions` - Direct permission-to-user assignments

### Step 4: Clear Cache

```bash
php artisan config:cache
```

---

## Configuration

Edit `config/permission.php`:

```php
return [
    'models' => [
        'permission' => Spatie\Permission\Models\Permission::class,
        'role' => Spatie\Permission\Models\Role::class,
    ],

    'table_names' => [
        'roles' => 'roles',
        'permissions' => 'permissions',
        'model_has_roles' => 'model_has_roles',
        'model_has_permissions' => 'model_has_permissions',
        'role_has_permissions' => 'role_has_permissions',
    ],

    'cache' => [
        'expiration_time' => \DateInterval::createFromDateString('24 hours'),
    ],
];
```

---

## Setup: User Model

Add the `HasRoles` trait to your User model:

```php
<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasRoles;

    protected $fillable = [
        'name',
        'email',
        'password',
        'user_type_id',
    ];

    // Spatie provides: hasRole(), hasAnyRole(), hasAllRoles()
    // Spatie provides: hasPermissionTo(), hasDirectPermission(), etc.
}
```

---

## Creating Roles & Permissions

### Method 1: Seeder (Recommended)

Create a seeder to set up roles and permissions:

```bash
php artisan make:seeder RolesAndPermissionsSeeder
```

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
        // Reset cached roles and permissions
        app()['cache']->forget('spatie.permission.cache');

        // Create permissions
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

        // Assign permissions to roles

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

### Method 2: Artisan Commands (for quick testing)

```bash
# Create a role
php artisan tinker
>>> use Spatie\Permission\Models\Role;
>>> Role::create(['name' => 'admin']);

# Create a permission
>>> use Spatie\Permission\Models\Permission;
>>> Permission::create(['name' => 'order:create']);

# Assign permission to role
>>> $role = Role::findByName('admin');
>>> $role->givePermissionTo('order:create');
```

---

## Using Roles & Permissions

### In Controllers

```php
<?php

namespace App\Http\Controllers;

use App\Models\Order;

class OrderController extends Controller
{
    public function store(StoreOrderRequest $request)
    {
        // Check permission
        if (!auth()->user()->can('order:create')) {
            abort(403, 'Unauthorized');
        }

        // Or use a policy (see below)
    }

    public function price(Order $order, PriceOrderRequest $request)
    {
        // Check permission
        if (!auth()->user()->can('order:price')) {
            abort(403, 'Unauthorized');
        }

        // Process...
    }
}
```

### In Policies

Create a policy:

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
    public function create(User $user)
    {
        return $user->can('order:create');
    }

    public function view(User $user, Order $order)
    {
        return $user->can('order:view');
    }

    public function price(User $user, Order $order)
    {
        return $user->can('order:price');
    }

    public function approve(User $user, Order $order)
    {
        return $user->can('order:approve');
    }
}
```

Use in controller:

```php
class OrderController extends Controller
{
    public function price(Order $order)
    {
        // Authorize using policy
        $this->authorize('price', $order);

        // Process...
    }
}
```

### In Middleware

Apply to routes:

```php
Route::middleware('auth:sanctum')->group(function () {
    // Require specific permission
    Route::post('/orders', [OrderController::class, 'store'])
        ->middleware('permission:order:create');

    Route::post('/orders/{id}/price', [OrderController::class, 'price'])
        ->middleware('permission:order:price');

    // Require specific role
    Route::post('/users', [UserController::class, 'store'])
        ->middleware('role:super_admin');

    // Require any of multiple roles
    Route::get('/reports', [ReportController::class, 'index'])
        ->middleware('role:admin|super_admin');

    // Require any of multiple permissions
    Route::get('/wallets', [WalletController::class, 'index'])
        ->middleware('permission:wallet:view|wallet:topup');
});
```

### In Blade Templates

```blade
@can('order:create')
    <button>Create Order</button>
@endcan

@if (auth()->user()->can('order:price'))
    <button>Price Order</button>
@endif

@role('admin')
    <div>Admin content</div>
@endrole

@if (auth()->user()->hasRole('super_admin'))
    <div>Super Admin only</div>
@endif
```

---

## Frontend: Checking Permissions

### Setup Auth Store

```javascript
// stores/auth.js
import { defineStore } from 'pinia';

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: null,
    token: null,
  }),

  actions: {
    async login(email, password) {
      const response = await fetch('/api/login', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ email, password }),
      });

      const data = await response.json();
      this.token = data.token;
      this.user = data.user; // Includes roles & permissions
      localStorage.setItem('token', this.token);
    },

    hasPermission(permission) {
      return this.user?.permissions?.includes(permission);
    },

    hasRole(role) {
      return this.user?.roles?.includes(role);
    },

    logout() {
      this.user = null;
      this.token = null;
      localStorage.removeItem('token');
    },
  },
});
```

### Create Composables

```javascript
// composables/usePermissions.js
import { useAuthStore } from '@/stores/auth';

export function useCanI(permission) {
  const auth = useAuthStore();
  return computed(() => auth.hasPermission(permission));
}

export function useHasRole(role) {
  const auth = useAuthStore();
  return computed(() => auth.hasRole(role));
}
```

### Use in Components

```vue
<template>
  <div>
    <button v-if="canCreateOrder">Create Order</button>
    <button v-if="canPriceOrder">Price Order</button>
    
    <AdminPanel v-if="isAdmin" />
  </div>
</template>

<script setup>
import { useCanI, useHasRole } from '@/composables/usePermissions';

const canCreateOrder = useCanI('order:create');
const canPriceOrder = useCanI('order:price');
const isAdmin = useHasRole('admin');
</script>
```

---

## API: Return User with Roles & Permissions

Update your user endpoint to return roles and permissions:

```php
<?php

namespace App\Http\Controllers;

class AuthController extends Controller
{
    public function user(Request $request)
    {
        return response()->json([
            'user' => [
                'id' => $request->user()->id,
                'name' => $request->user()->name,
                'email' => $request->user()->email,
                'roles' => $request->user()->roles->pluck('name')->toArray(),
                'permissions' => $request->user()->getAllPermissions()->pluck('name')->toArray(),
            ],
        ]);
    }

    public function hasPermission(Request $request, $permission)
    {
        return response()->json([
            'hasPermission' => $request->user()->can($permission),
        ]);
    }
}
```

---

## Common Patterns

### Super Admin Override

```php
// Always allow super admins
public function authorize($user, $permission)
{
    if ($user->hasRole('super_admin')) {
        return true;
    }

    return $user->can($permission);
}

// Or in policies:
public function create(User $user)
{
    if ($user->hasRole('super_admin')) {
        return true;
    }

    return $user->can('order:create');
}
```

### Dynamic Permissions

```php
// Give permission to specific user
$user->givePermissionTo('order:price');

// Revoke permission
$user->revokePermissionTo('order:price');

// Check direct permission (not through role)
$user->hasDirectPermission('order:create');

// Sync permissions (replace all)
$user->syncPermissions(['order:create', 'order:view']);
```

### Role Management

```php
// Assign role to user
$user->assignRole('admin');

// Remove role
$user->removeRole('admin');

// Sync roles (replace all)
$user->syncRoles(['admin', 'moderator']);

// Check if has any role
$user->hasAnyRole(['admin', 'super_admin']);

// Check if has all roles
$user->hasAllRoles(['admin', 'moderator']);
```

---

## Caching

Spatie caches permissions and roles for performance. When you change permissions, clear the cache:

```php
// In artisan commands or seeders
app()['cache']->forget('spatie.permission.cache');

// Or use the Artisan command
php artisan cache:forget spatie.permission.cache
```

---

## Testing

### Test Permissions in Feature Tests

```php
<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class OrderTest extends TestCase
{
    public function test_user_can_create_order()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('order:create');

        $response = $this->actingAs($user)
            ->postJson('/api/orders', [
                'departure' => 'KRT',
                'arrival' => 'DXB',
                // ... more data
            ]);

        $response->assertStatus(201);
    }

    public function test_user_cannot_create_order_without_permission()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/orders', [
                'departure' => 'KRT',
                'arrival' => 'DXB',
            ]);

        $response->assertStatus(403);
    }

    public function test_super_admin_can_always_act()
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $response = $this->actingAs($user)
            ->postJson('/api/orders', [/* ... */]);

        $response->assertStatus(201);
    }
}
```

---

## Troubleshooting

### Permissions not working?

1. **Clear cache:** `php artisan cache:forget spatie.permission.cache`
2. **Check trait:** Ensure User model has `use HasRoles;`
3. **Verify migration:** Check that Spatie tables exist: `php artisan tinker` → `\Spatie\Permission\Models\Permission::all()`
4. **Check middleware:** Ensure routes have correct middleware

### Permission denied unexpectedly?

1. **Check authorization policy:** Make sure policy allows the action
2. **Verify user role:** `$user->getRoleNames()` should show assigned roles
3. **Check super admin:** Super admins may need explicit bypass

### Cache issues?

```bash
# Clear all Spatie cache
php artisan cache:forget spatie.permission.cache

# Or clear entire cache
php artisan cache:clear

# Or disable caching temporarily (config/permission.php)
'cache' => [
    'enable' => false,
],
```

---

## Resources

- **Official Docs:** https://spatie.be/docs/laravel-permission
- **GitHub:** https://github.com/spatie/laravel-permission
- **API Reference:** https://spatie.be/docs/laravel-permission/v6/api

---

**Questions?** Refer to Spatie's excellent documentation or ask your team lead.

Good luck! 🚀
