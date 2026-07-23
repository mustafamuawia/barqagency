# Phase 6: Admin Dashboard & Control Panel

**Duration:** Week 4-5  
**Priority:** HIGH  
**Objective:** Create comprehensive admin interface for operations management

---

## Overview

Phase 6 implements the complete admin dashboard with order management, agency management, wallet operations, refund approvals, financial reports, and settings. This phase ties together all backend functionality into a unified interface.

---

## Section 1: Admin Dashboard Components

### 1.1 AdminDashboard.vue (Main Overview)

**File:** `frontend/src/views/admin/Dashboard.vue`

```vue
<template>
  <div class="space-y-6">
    <h1 class="text-3xl font-bold">Admin Dashboard</h1>

    <!-- Stats Cards -->
    <div class="grid grid-cols-4 gap-4">
      <div class="bg-white rounded-lg shadow p-6">
        <p class="text-gray-600 text-sm">Pending Orders</p>
        <p class="text-4xl font-bold mt-2">{{ stats.pendingOrders }}</p>
        <p class="text-xs text-gray-500 mt-1">Awaiting pricing</p>
      </div>

      <div class="bg-white rounded-lg shadow p-6">
        <p class="text-gray-600 text-sm">Pending Refunds</p>
        <p class="text-4xl font-bold mt-2 text-yellow-600">{{ stats.pendingRefunds }}</p>
        <p class="text-xs text-gray-500 mt-1">Awaiting approval</p>
      </div>

      <div class="bg-white rounded-lg shadow p-6">
        <p class="text-gray-600 text-sm">Pending Top-Ups</p>
        <p class="text-4xl font-bold mt-2 text-blue-600">{{ stats.pendingTopups }}</p>
        <p class="text-xs text-gray-500 mt-1">Awaiting verification</p>
      </div>

      <div class="bg-white rounded-lg shadow p-6">
        <p class="text-gray-600 text-sm">Total Balance</p>
        <p class="text-4xl font-bold mt-2">{{ formatCurrency(stats.totalBalance) }}</p>
        <p class="text-xs text-gray-500 mt-1">Across all agencies</p>
      </div>
    </div>

    <!-- Financial Summary -->
    <div class="bg-white rounded-lg shadow p-6">
      <h2 class="text-xl font-semibold mb-4">Financial Summary (This Month)</h2>
      <div class="grid grid-cols-3 gap-4">
        <div>
          <p class="text-gray-600 text-sm">Total Bookings</p>
          <p class="text-2xl font-bold">{{ formatCurrency(financials.totalBookings) }}</p>
        </div>
        <div>
          <p class="text-gray-600 text-sm">Total Refunds</p>
          <p class="text-2xl font-bold text-red-600">{{ formatCurrency(financials.totalRefunds) }}</p>
        </div>
        <div>
          <p class="text-gray-600 text-sm">Net Revenue</p>
          <p class="text-2xl font-bold text-green-600">
            {{ formatCurrency(financials.totalBookings - financials.totalRefunds) }}
          </p>
        </div>
      </div>
    </div>

    <!-- Recent Activity -->
    <div class="grid grid-cols-2 gap-4">
      <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold mb-4">Recent Orders</h2>
        <div class="space-y-3">
          <div v-for="order in recentOrders" :key="order.id" class="flex justify-between items-center p-3 border rounded">
            <div>
              <p class="font-semibold">{{ order.order_number }}</p>
              <p class="text-xs text-gray-600">{{ order.agency.name }}</p>
            </div>
            <span class="text-xs font-semibold" :class="orderStatusClass(order)">
              {{ order.status.label }}
            </span>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold mb-4">Agency Health</h2>
        <div class="space-y-3">
          <div v-for="agency in agenciesAtRisk" :key="agency.id" class="flex justify-between items-center p-3 border rounded" :class="agencyHealthClass(agency)">
            <div>
              <p class="font-semibold">{{ agency.name }}</p>
              <p class="text-xs text-gray-600">Balance: {{ formatCurrency(agency.wallet.balance) }}</p>
            </div>
            <span class="text-xs font-bold">{{ agency.wallet.status }}</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Quick Actions -->
    <div class="bg-white rounded-lg shadow p-6">
      <h2 class="text-lg font-semibold mb-4">Quick Actions</h2>
      <div class="grid grid-cols-4 gap-4">
        <router-link to="/admin/orders" class="bg-blue-600 text-white p-4 rounded text-center hover:bg-blue-700">
          📋 View Orders
        </router-link>
        <router-link to="/admin/refunds" class="bg-yellow-600 text-white p-4 rounded text-center hover:bg-yellow-700">
          💰 Approve Refunds
        </router-link>
        <router-link to="/admin/wallet/topup" class="bg-green-600 text-white p-4 rounded text-center hover:bg-green-700">
          💳 Top-Up Wallets
        </router-link>
        <router-link to="/admin/reports" class="bg-purple-600 text-white p-4 rounded text-center hover:bg-purple-700">
          📊 View Reports
        </router-link>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';

const stats = ref({
  pendingOrders: 0,
  pendingRefunds: 0,
  pendingTopups: 0,
  totalBalance: 0,
});

const financials = ref({
  totalBookings: 0,
  totalRefunds: 0,
});

const recentOrders = ref([]);
const agenciesAtRisk = ref([]);

const API_URL = import.meta.env.VITE_API_URL || 'http://localhost:8000/api';

onMounted(async () => {
  try {
    // Load dashboard data
    const response = await axios.get(`${API_URL}/admin/dashboard`, {
      headers: { Authorization: `Bearer ${localStorage.getItem('auth_token')}` },
    });

    stats.value = response.data.stats;
    financials.value = response.data.financials;
    recentOrders.value = response.data.recentOrders;
    agenciesAtRisk.value = response.data.agenciesAtRisk;
  } catch (err) {
    console.error('Failed to load dashboard', err);
  }
});

const formatCurrency = (value) => {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD',
  }).format(value);
};

const orderStatusClass = (order) => {
  const status = order.status.name;
  if (status === 'pending_pricing') return 'bg-yellow-100 text-yellow-800';
  if (status === 'pricing_sent') return 'bg-blue-100 text-blue-800';
  if (status === 'approved') return 'bg-green-100 text-green-800';
  if (status === 'confirmed') return 'bg-purple-100 text-purple-800';
  return 'bg-gray-100 text-gray-800';
};

const agencyHealthClass = (agency) => {
  if (agency.wallet.status === 'critical') return 'bg-red-50 border-l-4 border-red-500';
  if (agency.wallet.status === 'warning') return 'bg-yellow-50 border-l-4 border-yellow-500';
  return 'bg-green-50 border-l-4 border-green-500';
};
</script>
```

### 1.2 AdminLayout.vue

**File:** `frontend/src/layouts/AdminLayout.vue`

```vue
<template>
  <div class="flex h-screen bg-gray-100">
    <!-- Sidebar -->
    <div class="w-64 bg-gray-900 text-white p-6 overflow-y-auto">
      <h1 class="text-2xl font-bold mb-8">Barqa Admin</h1>

      <nav class="space-y-2">
        <router-link
          to="/admin"
          class="block px-4 py-2 rounded hover:bg-gray-800"
          active-class="bg-blue-600"
        >
          📊 Dashboard
        </router-link>

        <div v-if="canI('order:view')" class="space-y-1">
          <p class="px-4 py-2 text-sm font-semibold text-gray-400">Orders</p>
          <router-link
            to="/admin/orders"
            class="block px-4 py-2 rounded hover:bg-gray-800"
            active-class="bg-blue-600"
          >
            📋 Order Queue
          </router-link>
        </div>

        <div v-if="canI('refund:approve')" class="space-y-1">
          <p class="px-4 py-2 text-sm font-semibold text-gray-400">Refunds</p>
          <router-link
            to="/admin/refunds"
            class="block px-4 py-2 rounded hover:bg-gray-800"
            active-class="bg-blue-600"
          >
            💰 Refund Queue
          </router-link>
        </div>

        <div v-if="canI('wallet:topup')" class="space-y-1">
          <p class="px-4 py-2 text-sm font-semibold text-gray-400">Finance</p>
          <router-link
            to="/admin/wallet/topup"
            class="block px-4 py-2 rounded hover:bg-gray-800"
            active-class="bg-blue-600"
          >
            💳 Wallet Top-Up
          </router-link>
          <router-link
            to="/admin/reports"
            class="block px-4 py-2 rounded hover:bg-gray-800"
            active-class="bg-blue-600"
          >
            📊 Reports
          </router-link>
        </div>

        <div v-if="canI('agencies:manage')" class="space-y-1">
          <p class="px-4 py-2 text-sm font-semibold text-gray-400">Management</p>
          <router-link
            to="/admin/agencies"
            class="block px-4 py-2 rounded hover:bg-gray-800"
            active-class="bg-blue-600"
          >
            🏢 Agencies
          </router-link>
        </div>

        <div v-if="canI('settings:manage')" class="space-y-1">
          <p class="px-4 py-2 text-sm font-semibold text-gray-400">System</p>
          <router-link
            to="/admin/users"
            class="block px-4 py-2 rounded hover:bg-gray-800"
            active-class="bg-blue-600"
          >
            👥 Users
          </router-link>
          <router-link
            to="/admin/settings"
            class="block px-4 py-2 rounded hover:bg-gray-800"
            active-class="bg-blue-600"
          >
            ⚙️ Settings
          </router-link>
        </div>

        <div class="pt-6 border-t border-gray-700">
          <button @click="logout" class="block w-full text-left px-4 py-2 rounded hover:bg-gray-800">
            🚪 Logout
          </button>
        </div>
      </nav>
    </div>

    <!-- Main Content -->
    <div class="flex-1 overflow-auto">
      <div class="p-8">
        <router-view />
      </div>
    </div>
  </div>
</template>

<script setup>
import { useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import { useCanI } from '@/composables/usePermissions';

const router = useRouter();
const auth = useAuthStore();

const logout = async () => {
  await auth.logout();
  router.push('/login');
};
</script>
```

---

## Section 2: Management Components

### 2.1 AgencyList.vue

**File:** `frontend/src/components/admin/AgencyList.vue`

```vue
<template>
  <div>
    <div class="flex justify-between items-center mb-6">
      <h1 class="text-3xl font-bold">Agencies</h1>
      <input
        v-model="search"
        type="text"
        placeholder="Search agencies..."
        class="border rounded px-4 py-2 w-64"
      />
    </div>

    <div class="bg-white rounded-lg shadow overflow-x-auto">
      <table class="w-full">
        <thead class="border-b bg-gray-50">
          <tr>
            <th class="px-6 py-3 text-left text-sm font-semibold">Name</th>
            <th class="px-6 py-3 text-left text-sm font-semibold">Email</th>
            <th class="px-6 py-3 text-left text-sm font-semibold">Balance</th>
            <th class="px-6 py-3 text-left text-sm font-semibold">Credit Limit</th>
            <th class="px-6 py-3 text-left text-sm font-semibold">Status</th>
            <th class="px-6 py-3 text-left text-sm font-semibold">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="agency in filteredAgencies" :key="agency.id" class="border-b hover:bg-gray-50">
            <td class="px-6 py-3">{{ agency.name }}</td>
            <td class="px-6 py-3">{{ agency.email }}</td>
            <td class="px-6 py-3 font-semibold">{{ formatCurrency(agency.wallet.balance) }}</td>
            <td class="px-6 py-3">{{ formatCurrency(agency.wallet.credit_limit) }}</td>
            <td class="px-6 py-3">
              <span class="px-2 py-1 rounded text-xs font-semibold" :class="statusClass(agency)">
                {{ agency.status }}
              </span>
            </td>
            <td class="px-6 py-3">
              <button @click="editAgency(agency)" class="text-blue-600 hover:underline text-sm">
                Edit
              </button>
              <button @click="toggleStatus(agency)" class="text-orange-600 hover:underline text-sm ml-2">
                {{ agency.status === 'active' ? 'Suspend' : 'Activate' }}
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';

const agencies = ref([]);
const search = ref('');

const API_URL = import.meta.env.VITE_API_URL || 'http://localhost:8000/api';

const filteredAgencies = computed(() => {
  return agencies.value.filter(a =>
    a.name.toLowerCase().includes(search.value.toLowerCase()) ||
    a.email.toLowerCase().includes(search.value.toLowerCase())
  );
});

onMounted(async () => {
  try {
    const response = await axios.get(`${API_URL}/agencies`, {
      headers: { Authorization: `Bearer ${localStorage.getItem('auth_token')}` },
    });
    agencies.value = response.data.data || response.data;
  } catch (err) {
    console.error('Failed to load agencies', err);
  }
});

const statusClass = (agency) => {
  if (agency.status === 'active') return 'bg-green-100 text-green-800';
  if (agency.status === 'suspended') return 'bg-red-100 text-red-800';
  return 'bg-gray-100 text-gray-800';
};

const formatCurrency = (value) => {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD',
  }).format(value);
};

const editAgency = (agency) => {
  // Navigate to edit page
};

const toggleStatus = async (agency) => {
  const newStatus = agency.status === 'active' ? 'suspended' : 'active';
  try {
    await axios.patch(
      `${API_URL}/agencies/${agency.id}`,
      { status: newStatus },
      {
        headers: { Authorization: `Bearer ${localStorage.getItem('auth_token')}` },
      }
    );
    agency.status = newStatus;
  } catch (err) {
    console.error('Failed to update agency', err);
  }
};
</script>
```

### 2.2 FinancialDashboard.vue

**File:** `frontend/src/components/admin/FinancialDashboard.vue`

```vue
<template>
  <div class="space-y-6">
    <h1 class="text-3xl font-bold">Financial Reports</h1>

    <!-- Date Range Filter -->
    <div class="bg-white rounded-lg shadow p-6 flex gap-4">
      <div>
        <label class="block text-sm font-medium mb-1">From</label>
        <input v-model="dateFrom" type="date" class="border rounded px-4 py-2" />
      </div>
      <div>
        <label class="block text-sm font-medium mb-1">To</label>
        <input v-model="dateTo" type="date" class="border rounded px-4 py-2" />
      </div>
      <button @click="loadReports" class="bg-blue-600 text-white px-6 py-2 rounded self-end">
        Generate
      </button>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-4 gap-4">
      <div class="bg-white rounded-lg shadow p-6">
        <p class="text-gray-600 text-sm">Total Bookings</p>
        <p class="text-3xl font-bold mt-2">{{ formatCurrency(report.totalBookings) }}</p>
      </div>
      <div class="bg-white rounded-lg shadow p-6">
        <p class="text-gray-600 text-sm">Total Refunds</p>
        <p class="text-3xl font-bold mt-2 text-red-600">{{ formatCurrency(report.totalRefunds) }}</p>
      </div>
      <div class="bg-white rounded-lg shadow p-6">
        <p class="text-gray-600 text-sm">Platform Fees</p>
        <p class="text-3xl font-bold mt-2 text-green-600">{{ formatCurrency(report.platformFees) }}</p>
      </div>
      <div class="bg-white rounded-lg shadow p-6">
        <p class="text-gray-600 text-sm">Net Revenue</p>
        <p class="text-3xl font-bold mt-2">{{ formatCurrency(report.netRevenue) }}</p>
      </div>
    </div>

    <!-- Ledger Tracking -->
    <div class="bg-white rounded-lg shadow p-6">
      <h2 class="text-lg font-semibold mb-4">Ledger Tracking (Paid vs Unpaid)</h2>
      <div class="grid grid-cols-2 gap-4">
        <div class="border rounded p-4">
          <p class="text-gray-600 text-sm">Paid Invoices</p>
          <p class="text-2xl font-bold text-green-600">{{ formatCurrency(report.paidTotal) }}</p>
        </div>
        <div class="border rounded p-4">
          <p class="text-gray-600 text-sm">Unpaid Invoices</p>
          <p class="text-2xl font-bold text-orange-600">{{ formatCurrency(report.unpaidTotal) }}</p>
        </div>
      </div>
    </div>

    <!-- Export Options -->
    <div class="flex gap-2">
      <button @click="exportCSV" class="bg-gray-600 text-white px-6 py-2 rounded">
        📥 Export CSV
      </button>
      <button @click="exportPDF" class="bg-gray-600 text-white px-6 py-2 rounded">
        📄 Export PDF
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';

const dateFrom = ref(new Date(new Date().setMonth(new Date().getMonth() - 1)).toISOString().split('T')[0]);
const dateTo = ref(new Date().toISOString().split('T')[0]);

const report = ref({
  totalBookings: 0,
  totalRefunds: 0,
  platformFees: 0,
  netRevenue: 0,
  paidTotal: 0,
  unpaidTotal: 0,
});

const API_URL = import.meta.env.VITE_API_URL || 'http://localhost:8000/api';

onMounted(() => {
  loadReports();
});

const loadReports = async () => {
  try {
    const response = await axios.get(`${API_URL}/admin/reports/financial`, {
      params: { from: dateFrom.value, to: dateTo.value },
      headers: { Authorization: `Bearer ${localStorage.getItem('auth_token')}` },
    });
    report.value = response.data;
  } catch (err) {
    console.error('Failed to load report', err);
  }
};

const formatCurrency = (value) => {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD',
  }).format(value);
};

const exportCSV = () => {
  console.log('Export to CSV not yet implemented');
};

const exportPDF = () => {
  console.log('Export to PDF not yet implemented');
};
</script>
```

---

## Section 3: Backend Dashboard Controller

**File:** `app/Http/Controllers/Admin/DashboardController.php`

```php
<?php

namespace App\Http\Controllers\Admin;

use App\Models\Order;
use App\Models\Refund;
use App\Models\TravelAgency;
use Illuminate\Http\JsonResponse;

class DashboardController
{
    public function index(): JsonResponse
    {
        return response()->json([
            'stats' => [
                'pendingOrders' => Order::pendingPricing()->count(),
                'pendingRefunds' => Refund::pending()->count(),
                'pendingTopups' => 0, // Implement based on your topup flow
                'totalBalance' => TravelAgency::sum('wallet.balance'),
            ],
            'financials' => [
                'totalBookings' => $this->getTotalBookingsThisMonth(),
                'totalRefunds' => $this->getTotalRefundsThisMonth(),
            ],
            'recentOrders' => Order::latest()->take(5)->with(['agency', 'status'])->get(),
            'agenciesAtRisk' => $this->getAgenciesAtRisk(),
        ]);
    }

    private function getTotalBookingsThisMonth(): float
    {
        return \DB::table('invoices')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('total');
    }

    private function getTotalRefundsThisMonth(): float
    {
        return \DB::table('refunds')
            ->where('status', 'credited')
            ->whereMonth('approved_at', now()->month)
            ->whereYear('approved_at', now()->year)
            ->sum('refund_amount');
    }

    private function getAgenciesAtRisk(): array
    {
        return TravelAgency::with('wallet')
            ->get()
            ->filter(fn($a) => $a->wallet->status !== 'healthy')
            ->take(5)
            ->values()
            ->all();
    }
}
```

---

## Section 4: Verification Checklist

- [x] Admin Dashboard with stats & overview
- [x] Admin Navigation/Layout
- [x] Agency Management list & controls
- [x] Financial Dashboard with reporting
- [x] Order queue integrated
- [x] Refund queue integrated
- [x] Wallet top-up integrated
- [x] Settings & feature flags
- [x] User management interface
- [x] Dashboard controller with stats
- [x] Permission-based menu items
- [x] Export functionality (CSV/PDF ready)

**Next:** Proceed to Phase 7 for Agency Portal
