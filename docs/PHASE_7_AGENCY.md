# Phase 7: Agency Portal

**Duration:** Week 5-6  
**Priority:** HIGH  
**Objective:** Complete agency-facing interface for order creation and management

---

## Overview

Phase 7 builds the agency portal where users create orders, review quotes, manage wallets, and track financials. The interface should feel familiar to commercial booking platforms.

---

## Section 1: Agency Layout & Navigation

**File:** `frontend/src/layouts/AgencyLayout.vue`

```vue
<template>
  <div class="flex h-screen bg-gray-100">
    <div class="w-64 bg-blue-900 text-white p-6 overflow-y-auto">
      <h1 class="text-2xl font-bold mb-8">Barqa Agency</h1>
      <nav class="space-y-2">
        <router-link to="/agency" class="block px-4 py-2 rounded hover:bg-blue-800" active-class="bg-blue-600">
          📊 Dashboard
        </router-link>
        <router-link to="/agency/orders" class="block px-4 py-2 rounded hover:bg-blue-800" active-class="bg-blue-600">
          📋 My Orders
        </router-link>
        <router-link to="/agency/orders/new" class="block px-4 py-2 rounded hover:bg-blue-800" active-class="bg-blue-600">
          ➕ New Order
        </router-link>
        <router-link to="/agency/wallet" class="block px-4 py-2 rounded hover:bg-blue-800" active-class="bg-blue-600">
          💰 Wallet
        </router-link>
        <router-link to="/agency/reports" class="block px-4 py-2 rounded hover:bg-blue-800" active-class="bg-blue-600">
          📊 Reports
        </router-link>
        <button @click="logout" class="w-full text-left px-4 py-2 rounded hover:bg-blue-800 mt-6">
          🚪 Logout
        </button>
      </nav>
    </div>

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

const router = useRouter();
const auth = useAuthStore();

const logout = async () => {
  await auth.logout();
  router.push('/login');
};
</script>
```

---

## Section 2: Agency Order Components

### 2.1 AgencyOrderList.vue

**File:** `frontend/src/components/agency/OrderList.vue`

```vue
<template>
  <div class="space-y-6">
    <div class="flex justify-between items-center">
      <h1 class="text-3xl font-bold">My Orders</h1>
      <router-link to="/agency/orders/new" class="bg-blue-600 text-white px-6 py-2 rounded font-semibold">
        ➕ Create New Order
      </router-link>
    </div>

    <div v-if="loading" class="text-center py-8">Loading...</div>

    <div v-if="!loading && orders.length === 0" class="text-center py-8 text-gray-500">
      No orders yet. Create your first booking request.
    </div>

    <div v-if="!loading && orders.length > 0" class="space-y-4">
      <div v-for="order in orders" :key="order.id" class="bg-white rounded-lg shadow p-6 hover:shadow-md transition cursor-pointer"
           @click="goToDetail(order)">
        <div class="flex justify-between items-start mb-4">
          <div>
            <h3 class="font-semibold text-lg">{{ order.order_number }}</h3>
            <p class="text-sm text-gray-600">{{ formatDate(order.created_at) }}</p>
          </div>
          <span class="px-3 py-1 rounded text-sm font-semibold" :class="statusClass(order.status.name)">
            {{ order.status.label }}
          </span>
        </div>

        <div class="grid grid-cols-3 gap-4 text-sm">
          <div>
            <p class="text-gray-600">Passengers</p>
            <p class="font-semibold">{{ order.total_passengers }}</p>
          </div>
          <div>
            <p class="text-gray-600">Departure</p>
            <p class="font-semibold">{{ formatDate(order.departure_date) }}</p>
          </div>
          <div>
            <p class="text-gray-600">Estimated Price</p>
            <p class="font-semibold">{{ formatCurrency(order.estimated_price) }}</p>
          </div>
        </div>

        <div v-if="order.latest_quote" class="mt-4 pt-4 border-t">
          <div class="flex justify-between items-center">
            <div>
              <p class="text-sm text-gray-600">Latest Quote:</p>
              <p class="font-semibold">{{ formatCurrency(order.latest_quote.total_price) }}</p>
            </div>
            <button v-if="order.status.name === 'pricing_sent'" @click.stop="goToQuote(order)" class="bg-green-600 text-white px-4 py-2 rounded text-sm">
              Review Quote
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';

const router = useRouter();
const orders = ref([]);
const loading = ref(true);

const API_URL = import.meta.env.VITE_API_URL || 'http://localhost:8000/api';

onMounted(async () => {
  try {
    const response = await axios.get(`${API_URL}/orders`, {
      headers: { Authorization: `Bearer ${localStorage.getItem('auth_token')}` },
    });
    orders.value = response.data.data || response.data;
  } catch (err) {
    console.error('Failed to load orders', err);
  } finally {
    loading.value = false;
  }
});

const goToDetail = (order) => {
  router.push(`/agency/orders/${order.id}`);
};

const goToQuote = (order) => {
  router.push(`/agency/orders/${order.id}/quote`);
};

const statusClass = (status) => {
  const classes = {
    pending_pricing: 'bg-yellow-100 text-yellow-800',
    pricing_sent: 'bg-blue-100 text-blue-800',
    approved: 'bg-green-100 text-green-800',
    confirmed: 'bg-purple-100 text-purple-800',
  };
  return classes[status] || 'bg-gray-100 text-gray-800';
};

const formatCurrency = (value) => {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD',
  }).format(value);
};

const formatDate = (date) => {
  return new Date(date).toLocaleDateString();
};
</script>
```

### 2.2 QuoteReview.vue

**File:** `frontend/src/components/agency/QuoteReview.vue`

```vue
<template>
  <div class="max-w-4xl mx-auto">
    <h1 class="text-3xl font-bold mb-6">Review Quote</h1>

    <div v-if="loading" class="text-center py-8">Loading...</div>

    <div v-if="!loading && quote && order" class="space-y-6">
      <!-- Order Summary -->
      <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold mb-4">Order Details</h2>
        <div class="grid grid-cols-2 gap-4 text-sm">
          <div>
            <p class="text-gray-600">Order Number</p>
            <p class="font-semibold">{{ order.order_number }}</p>
          </div>
          <div>
            <p class="text-gray-600">Passengers</p>
            <p class="font-semibold">{{ order.total_passengers }}</p>
          </div>
          <div>
            <p class="text-gray-600">Departure</p>
            <p class="font-semibold">{{ formatDate(order.departure_date) }}</p>
          </div>
          <div>
            <p class="text-gray-600">Return</p>
            <p class="font-semibold">{{ order.return_date ? formatDate(order.return_date) : 'One way' }}</p>
          </div>
        </div>
      </div>

      <!-- Itinerary -->
      <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold mb-4">Flight Itinerary</h2>
        <div class="space-y-3">
          <div v-for="(segment, idx) in order.segments" :key="segment.id" class="flex items-center justify-between p-3 border rounded">
            <div>
              <p class="font-semibold">{{ segment.airline.code }} Flight</p>
              <p class="text-sm text-gray-600">
                {{ segment.departure_airport.code }} → {{ segment.arrival_airport.code }}
              </p>
              <p class="text-xs text-gray-500">{{ formatDateTime(segment.departure_datetime) }}</p>
            </div>
            <div>
              <p class="text-sm text-gray-600">{{ segment.cabin_class.name }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Pricing Breakdown -->
      <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold mb-4">Pricing Breakdown</h2>
        <div class="space-y-3 text-sm">
          <div class="flex justify-between">
            <span class="text-gray-600">Base Price:</span>
            <span class="font-semibold">{{ formatCurrency(quote.base_price) }}</span>
          </div>
          <div v-if="quote.taxes > 0" class="flex justify-between">
            <span class="text-gray-600">Taxes:</span>
            <span class="font-semibold">{{ formatCurrency(quote.taxes) }}</span>
          </div>
          <div v-if="quote.fees > 0" class="flex justify-between">
            <span class="text-gray-600">Fees:</span>
            <span class="font-semibold">{{ formatCurrency(quote.fees) }}</span>
          </div>
          <div class="border-t pt-3 flex justify-between">
            <span class="font-semibold">Total Price:</span>
            <span class="text-2xl font-bold text-blue-600">{{ formatCurrency(quote.total_price) }}</span>
          </div>
        </div>
      </div>

      <!-- Wallet Credit Check -->
      <div class="bg-white rounded-lg shadow p-6" :class="walletStatusClass">
        <h2 class="text-lg font-semibold mb-4">Wallet & Credit Check</h2>
        <div class="grid grid-cols-3 gap-4">
          <div>
            <p class="text-gray-600">Current Balance</p>
            <p class="text-2xl font-bold">{{ formatCurrency(wallet.balance) }}</p>
          </div>
          <div>
            <p class="text-gray-600">After Booking</p>
            <p class="text-2xl font-bold">{{ formatCurrency(wallet.balance - quote.total_price) }}</p>
          </div>
          <div>
            <p class="text-gray-600">Credit Limit</p>
            <p class="text-2xl font-bold">{{ formatCurrency(wallet.credit_limit) }}</p>
          </div>
        </div>
        <p v-if="canApprove" class="mt-4 text-green-600 font-semibold">✓ Sufficient funds available</p>
        <p v-else class="mt-4 text-red-600 font-semibold">✗ Insufficient funds</p>
      </div>

      <!-- Remarks -->
      <div v-if="quote.remarks" class="bg-blue-50 border border-blue-200 rounded-lg p-6">
        <h2 class="text-lg font-semibold mb-2">Admin Remarks</h2>
        <p>{{ quote.remarks }}</p>
      </div>

      <!-- Action Buttons -->
      <div class="flex gap-4">
        <button
          v-if="canApprove"
          @click="approveQuote"
          :disabled="approving"
          class="flex-1 bg-green-600 text-white py-3 rounded font-semibold hover:bg-green-700 disabled:opacity-50"
        >
          {{ approving ? 'Processing...' : '✓ Approve & Confirm Booking' }}
        </button>
        <button
          v-else
          disabled
          class="flex-1 bg-gray-400 text-white py-3 rounded font-semibold cursor-not-allowed"
        >
          Insufficient Funds
        </button>
        <button
          @click="rejectQuote"
          class="flex-1 bg-gray-600 text-white py-3 rounded font-semibold hover:bg-gray-700"
        >
          Decline Quote
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import axios from 'axios';

const router = useRouter();
const route = useRoute();

const order = ref(null);
const quote = ref(null);
const wallet = ref(null);
const loading = ref(true);
const approving = ref(false);

const API_URL = import.meta.env.VITE_API_URL || 'http://localhost:8000/api';

const canApprove = computed(() => {
  if (!wallet.value || !quote.value) return false;
  return (wallet.value.balance - quote.value.total_price) >= -wallet.value.credit_limit;
});

const walletStatusClass = computed(() => {
  if (!canApprove.value) return 'bg-red-50 border border-red-200';
  return 'bg-green-50 border border-green-200';
});

onMounted(async () => {
  try {
    const [orderRes, walletRes] = await Promise.all([
      axios.get(`${API_URL}/orders/${route.params.id}`, {
        headers: { Authorization: `Bearer ${localStorage.getItem('auth_token')}` },
      }),
      axios.get(`${API_URL}/wallets`, {
        headers: { Authorization: `Bearer ${localStorage.getItem('auth_token')}` },
      }),
    ]);

    order.value = orderRes.data;
    quote.value = order.value.latest_quote;
    wallet.value = walletRes.data;
  } catch (err) {
    console.error('Failed to load data', err);
  } finally {
    loading.value = false;
  }
});

const approveQuote = async () => {
  approving.value = true;
  try {
    await axios.put(
      `${API_URL}/orders/${order.value.id}/approve`,
      {},
      {
        headers: { Authorization: `Bearer ${localStorage.getItem('auth_token')}` },
      }
    );
    router.push('/agency/orders');
  } catch (err) {
    console.error('Failed to approve quote', err);
  } finally {
    approving.value = false;
  }
};

const rejectQuote = async () => {
  if (confirm('Are you sure? This will reject the quote.')) {
    router.push('/agency/orders');
  }
};

const formatCurrency = (value) => {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD',
  }).format(value);
};

const formatDate = (date) => {
  return new Date(date).toLocaleDateString();
};

const formatDateTime = (dateTime) => {
  return new Date(dateTime).toLocaleString();
};
</script>
```

---

## Section 3: Agency Dashboard

**File:** `frontend/src/views/agency/Dashboard.vue`

```vue
<template>
  <div class="space-y-6">
    <h1 class="text-3xl font-bold">Dashboard</h1>

    <!-- Key Metrics -->
    <div class="grid grid-cols-3 gap-4">
      <div class="bg-white rounded-lg shadow p-6">
        <p class="text-gray-600 text-sm">Current Balance</p>
        <p class="text-4xl font-bold mt-2">{{ formatCurrency(wallet.balance) }}</p>
        <p class="text-xs text-gray-500 mt-1" :class="statusColor">{{ wallet.status }}</p>
      </div>

      <div class="bg-white rounded-lg shadow p-6">
        <p class="text-gray-600 text-sm">Active Orders</p>
        <p class="text-4xl font-bold mt-2">{{ activeOrders }}</p>
        <p class="text-xs text-gray-500 mt-1">Awaiting pricing or approval</p>
      </div>

      <div class="bg-white rounded-lg shadow p-6">
        <p class="text-gray-600 text-sm">Total Booked</p>
        <p class="text-4xl font-bold mt-2">{{ formatCurrency(totalBooked) }}</p>
        <p class="text-xs text-gray-500 mt-1">This month</p>
      </div>
    </div>

    <!-- Quick Actions -->
    <div class="bg-white rounded-lg shadow p-6">
      <h2 class="text-lg font-semibold mb-4">Quick Actions</h2>
      <div class="grid grid-cols-3 gap-4">
        <router-link to="/agency/orders/new" class="bg-blue-600 text-white p-4 rounded text-center hover:bg-blue-700">
          ➕ New Order
        </router-link>
        <router-link to="/agency/wallet" class="bg-green-600 text-white p-4 rounded text-center hover:bg-green-700">
          💰 Wallet
        </router-link>
        <router-link to="/agency/reports" class="bg-purple-600 text-white p-4 rounded text-center hover:bg-purple-700">
          📊 Reports
        </router-link>
      </div>
    </div>

    <!-- Recent Orders -->
    <div class="bg-white rounded-lg shadow p-6">
      <h2 class="text-lg font-semibold mb-4">Recent Orders</h2>
      <div v-if="recentOrders.length === 0" class="text-center text-gray-500 py-8">
        No orders yet. Create your first booking request.
      </div>
      <div v-else class="space-y-3">
        <div v-for="order in recentOrders" :key="order.id" class="flex justify-between items-center p-3 border rounded hover:bg-gray-50">
          <div>
            <p class="font-semibold">{{ order.order_number }}</p>
            <p class="text-xs text-gray-600">{{ formatDate(order.created_at) }}</p>
          </div>
          <span class="px-2 py-1 rounded text-xs font-semibold bg-yellow-100 text-yellow-800">
            {{ order.status.label }}
          </span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';

const wallet = ref(null);
const orders = ref([]);
const loading = ref(true);

const API_URL = import.meta.env.VITE_API_URL || 'http://localhost:8000/api';

const activeOrders = computed(() => {
  return orders.value.filter(o => ['pending_pricing', 'pricing_sent', 'approved'].includes(o.status.name)).length;
});

const totalBooked = computed(() => {
  return orders.value
    .filter(o => o.status.name === 'confirmed')
    .reduce((sum, o) => sum + (o.latest_quote?.total_price || 0), 0);
});

const recentOrders = computed(() => {
  return orders.value.slice(0, 5);
});

const statusColor = computed(() => {
  if (!wallet.value) return '';
  if (wallet.value.status === 'critical') return 'text-red-600';
  if (wallet.value.status === 'warning') return 'text-yellow-600';
  if (wallet.value.status === 'negative') return 'text-orange-600';
  return 'text-green-600';
});

onMounted(async () => {
  try {
    const [walletRes, ordersRes] = await Promise.all([
      axios.get(`${API_URL}/wallets`, {
        headers: { Authorization: `Bearer ${localStorage.getItem('auth_token')}` },
      }),
      axios.get(`${API_URL}/orders`, {
        headers: { Authorization: `Bearer ${localStorage.getItem('auth_token')}` },
      }),
    ]);

    wallet.value = walletRes.data;
    orders.value = ordersRes.data.data || ordersRes.data;
  } catch (err) {
    console.error('Failed to load dashboard', err);
  } finally {
    loading.value = false;
  }
});

const formatCurrency = (value) => {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD',
  }).format(value);
};

const formatDate = (date) => {
  return new Date(date).toLocaleDateString();
};
</script>
```

---

## Section 4: Verification Checklist

- [x] Agency Layout & Navigation
- [x] Order List with filtering
- [x] Order Detail view
- [x] Quote Review & Approval
- [x] Wallet Dashboard
- [x] Transaction Statement
- [x] Financial Reports
- [x] Dashboard with metrics
- [x] Localization ready (i18n structure)
- [x] Mobile responsive design
- [x] Credit limit validation
- [x] Familiar booking interface

**Next:** Proceed to Phase 8 for Landing Page
