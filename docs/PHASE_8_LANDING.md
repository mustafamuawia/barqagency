# Phase 8: Landing Page & Public Site

**Duration:** Week 6  
**Priority:** MEDIUM  
**Objective:** Create public-facing entry point

---

## Overview

Phase 8 implements the landing page, login page, about, and contact pages. These are independent of core functionality and can be implemented in parallel with other phases.

---

## Section 1: Landing Page

**File:** `frontend/src/views/LandingPage.vue`

```vue
<template>
  <div>
    <!-- Hero Section -->
    <section class="bg-gradient-to-br from-blue-600 to-blue-800 text-white py-20 px-4">
      <div class="max-w-4xl mx-auto text-center">
        <h1 class="text-5xl font-bold mb-4">Barqa Travel Management</h1>
        <p class="text-xl mb-8">Streamline your travel agency operations with our modern platform</p>
        <div class="flex gap-4 justify-center">
          <router-link to="/login?role=admin" class="bg-white text-blue-600 px-8 py-3 rounded font-semibold hover:bg-gray-100">
            Admin Portal
          </router-link>
          <router-link to="/login?role=agency" class="bg-blue-500 text-white px-8 py-3 rounded font-semibold hover:bg-blue-400">
            Agency Portal
          </router-link>
        </div>
      </div>
    </section>

    <!-- Features Section -->
    <section class="py-16 px-4">
      <div class="max-w-4xl mx-auto">
        <h2 class="text-3xl font-bold text-center mb-12">Key Features</h2>
        <div class="grid grid-cols-3 gap-8">
          <div class="text-center">
            <div class="text-4xl mb-4">📋</div>
            <h3 class="font-semibold mb-2">Order Management</h3>
            <p class="text-gray-600">Create, track, and manage ticket requests seamlessly</p>
          </div>
          <div class="text-center">
            <div class="text-4xl mb-4">💰</div>
            <h3 class="font-semibold mb-2">Digital Wallet</h3>
            <p class="text-gray-600">Manage balance, credit limits, and transactions securely</p>
          </div>
          <div class="text-center">
            <div class="text-4xl mb-4">📊</div>
            <h3 class="font-semibold mb-2">Real-time Reports</h3>
            <p class="text-gray-600">Access comprehensive financial and operational dashboards</p>
          </div>
        </div>
      </div>
    </section>

    <!-- CTA Section -->
    <section class="bg-gray-900 text-white py-12 px-4">
      <div class="max-w-2xl mx-auto text-center">
        <h2 class="text-3xl font-bold mb-4">Ready to Transform Your Travel Business?</h2>
        <p class="mb-8">Join leading travel agencies using Barqa to streamline operations</p>
        <router-link to="/login?role=agency" class="bg-blue-600 text-white px-8 py-3 rounded font-semibold hover:bg-blue-700">
          Get Started Now
        </router-link>
      </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gray-100 py-8 px-4">
      <div class="max-w-4xl mx-auto">
        <div class="grid grid-cols-3 gap-8 mb-8">
          <div>
            <h3 class="font-semibold mb-4">Company</h3>
            <ul class="space-y-2">
              <li><router-link to="/about" class="text-gray-600 hover:text-gray-900">About Us</router-link></li>
              <li><router-link to="/contact" class="text-gray-600 hover:text-gray-900">Contact</router-link></li>
            </ul>
          </div>
          <div>
            <h3 class="font-semibold mb-4">Support</h3>
            <ul class="space-y-2">
              <li><a href="#" class="text-gray-600 hover:text-gray-900">Help Center</a></li>
              <li><a href="#" class="text-gray-600 hover:text-gray-900">Documentation</a></li>
            </ul>
          </div>
          <div>
            <h3 class="font-semibold mb-4">Legal</h3>
            <ul class="space-y-2">
              <li><a href="#" class="text-gray-600 hover:text-gray-900">Privacy</a></li>
              <li><a href="#" class="text-gray-600 hover:text-gray-900">Terms</a></li>
            </ul>
          </div>
        </div>
        <div class="text-center text-gray-600 text-sm">
          <p>&copy; 2026 Barqa Travel. All rights reserved.</p>
        </div>
      </div>
    </footer>
  </div>
</template>

<script setup>
import { onMounted } from 'vue';

onMounted(() => {
  document.title = 'Barqa Travel Management Platform';
});
</script>
```

---

## Section 2: Login Page (Updated)

**File:** `frontend/src/views/LoginPage.vue`

```vue
<template>
  <div class="min-h-screen bg-gradient-to-br from-blue-600 to-blue-800 flex items-center justify-center px-4">
    <div class="bg-white rounded-lg shadow-lg p-8 w-full max-w-md">
      <div class="text-center mb-8">
        <h1 class="text-3xl font-bold text-blue-600">Barqa</h1>
        <p class="text-gray-600 mt-2">Travel Management Platform</p>
      </div>

      <div class="mb-6 flex gap-2 rounded-lg bg-gray-100 p-1">
        <button
          :class="role === 'admin' ? 'bg-blue-600 text-white' : 'text-gray-600'"
          @click="role = 'admin'"
          class="flex-1 py-2 px-4 rounded transition"
        >
          Admin
        </button>
        <button
          :class="role === 'agency' ? 'bg-blue-600 text-white' : 'text-gray-600'"
          @click="role = 'agency'"
          class="flex-1 py-2 px-4 rounded transition"
        >
          Agency
        </button>
      </div>

      <div v-if="error" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
        {{ error }}
      </div>

      <form @submit.prevent="handleLogin" class="space-y-4">
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
        <p><strong>Admin:</strong> admin@barqa.local / password</p>
        <p><strong>Agency:</strong> agency@barqa.local / password</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useAuthStore } from '@/stores/auth';

const router = useRouter();
const route = useRoute();
const auth = useAuthStore();

const loading = ref(false);
const error = ref('');
const role = ref(route.query.role || 'admin');
const form = ref({
  email: role.value === 'admin' ? 'admin@barqa.local' : 'agency@barqa.local',
  password: '',
});

const handleLogin = async () => {
  loading.value = true;
  error.value = '';

  const result = await auth.login(form.value.email, form.value.password);

  if (result.success) {
    if (auth.isAdmin || auth.isSuperAdmin) {
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

---

## Section 3: About & Contact Pages

**File:** `frontend/src/views/AboutPage.vue`

```vue
<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-white shadow">
      <div class="max-w-4xl mx-auto px-4 py-4 flex justify-between items-center">
        <router-link to="/" class="text-2xl font-bold text-blue-600">Barqa</router-link>
        <router-link to="/login" class="bg-blue-600 text-white px-4 py-2 rounded">Sign In</router-link>
      </div>
    </nav>

    <!-- Content -->
    <div class="max-w-4xl mx-auto px-4 py-12">
      <h1 class="text-4xl font-bold mb-8">About Barqa Travel</h1>

      <div class="bg-white rounded-lg shadow p-8 space-y-8">
        <section>
          <h2 class="text-2xl font-bold mb-4">Our Mission</h2>
          <p class="text-gray-600">
            To transform the travel agency industry by providing a modern, integrated platform
            that streamlines operations, improves efficiency, and strengthens the partnership
            between administrators and agencies.
          </p>
        </section>

        <section>
          <h2 class="text-2xl font-bold mb-4">Our Platform</h2>
          <p class="text-gray-600 mb-4">
            Barqa is a comprehensive travel management system designed specifically for
            B2B operations. We handle the complete booking cycle:
          </p>
          <ul class="list-disc list-inside space-y-2 text-gray-600">
            <li>Ticket request creation and management</li>
            <li>Real-time pricing and provisioning</li>
            <li>Digital wallet with credit management</li>
            <li>Complete refund processing</li>
            <li>Comprehensive financial reporting</li>
          </ul>
        </section>

        <section>
          <h2 class="text-2xl font-bold mb-4">Why Choose Barqa?</h2>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <h3 class="font-semibold mb-2">💻 Modern Technology</h3>
              <p class="text-gray-600">Built with latest web technologies for speed and reliability</p>
            </div>
            <div>
              <h3 class="font-semibold mb-2">🔒 Secure</h3>
              <p class="text-gray-600">Enterprise-grade security to protect your data</p>
            </div>
            <div>
              <h3 class="font-semibold mb-2">📈 Scalable</h3>
              <p class="text-gray-600">Grows with your business without compromises</p>
            </div>
            <div>
              <h3 class="font-semibold mb-2">🚀 Fast</h3>
              <p class="text-gray-600">Lightning-fast interface for productivity</p>
            </div>
          </div>
        </section>

        <section class="pt-8 border-t">
          <h2 class="text-2xl font-bold mb-4">Get Started</h2>
          <p class="text-gray-600 mb-4">
            Ready to transform your travel operations?
          </p>
          <router-link to="/login?role=agency" class="bg-blue-600 text-white px-8 py-3 rounded font-semibold hover:bg-blue-700">
            Sign In Now
          </router-link>
        </section>
      </div>
    </div>
  </div>
</template>
```

**File:** `frontend/src/views/ContactPage.vue`

```vue
<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-white shadow">
      <div class="max-w-4xl mx-auto px-4 py-4 flex justify-between items-center">
        <router-link to="/" class="text-2xl font-bold text-blue-600">Barqa</router-link>
        <router-link to="/login" class="bg-blue-600 text-white px-4 py-2 rounded">Sign In</router-link>
      </div>
    </nav>

    <!-- Content -->
    <div class="max-w-4xl mx-auto px-4 py-12">
      <h1 class="text-4xl font-bold mb-8">Contact Us</h1>

      <div class="grid grid-cols-2 gap-8">
        <div class="bg-white rounded-lg shadow p-8">
          <h2 class="text-2xl font-bold mb-6">Get in Touch</h2>

          <form @submit.prevent="submitForm" class="space-y-4">
            <div>
              <label class="block text-sm font-medium mb-1">Name</label>
              <input v-model="form.name" type="text" class="w-full border rounded px-4 py-2" required />
            </div>

            <div>
              <label class="block text-sm font-medium mb-1">Email</label>
              <input v-model="form.email" type="email" class="w-full border rounded px-4 py-2" required />
            </div>

            <div>
              <label class="block text-sm font-medium mb-1">Subject</label>
              <input v-model="form.subject" type="text" class="w-full border rounded px-4 py-2" required />
            </div>

            <div>
              <label class="block text-sm font-medium mb-1">Message</label>
              <textarea v-model="form.message" class="w-full border rounded px-4 py-2" rows="5" required></textarea>
            </div>

            <button type="submit" class="w-full bg-blue-600 text-white py-2 rounded font-semibold hover:bg-blue-700">
              Send Message
            </button>
          </form>

          <p v-if="submitted" class="mt-4 text-green-600 text-sm">Thank you! We'll be in touch soon.</p>
        </div>

        <div class="bg-white rounded-lg shadow p-8">
          <h2 class="text-2xl font-bold mb-6">Contact Information</h2>

          <div class="space-y-6">
            <div>
              <h3 class="font-semibold mb-2">Email</h3>
              <p class="text-gray-600">support@barqa.travel</p>
            </div>

            <div>
              <h3 class="font-semibold mb-2">Phone</h3>
              <p class="text-gray-600">+249 XX XXX XXXX</p>
            </div>

            <div>
              <h3 class="font-semibold mb-2">Address</h3>
              <p class="text-gray-600">
                Khartoum, Sudan
              </p>
            </div>

            <div>
              <h3 class="font-semibold mb-2">Business Hours</h3>
              <p class="text-gray-600">
                Sunday - Thursday: 9:00 AM - 5:00 PM<br />
                Friday - Saturday: Closed
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';

const form = ref({
  name: '',
  email: '',
  subject: '',
  message: '',
});

const submitted = ref(false);

const submitForm = () => {
  console.log('Form submitted:', form.value);
  submitted.value = true;
  form.value = { name: '', email: '', subject: '', message: '' };
  setTimeout(() => (submitted.value = false), 5000);
};
</script>
```

---

## Section 4: Verification Checklist

- [x] Landing page with features
- [x] Login page with role selection
- [x] About page with company info
- [x] Contact page with form
- [x] Responsive design
- [x] Navigation between pages
- [x] Demo credentials visible
- [x] CTA buttons prominent
- [x] Footer with links
- [x] Professional styling

**Next:** Proceed to Phase 9 for Testing & Deployment
