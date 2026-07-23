# Phase 4: Financial Management (Wallet & Ledger)

**Duration:** Week 3-4  
**Priority:** HIGH  
**Objective:** Implement wallet system with balance tracking, transaction logs, and invoice generation

---

## Overview

Phase 4 implements the digital wallet system where agencies maintain a balance. Admins can top-up wallets, set credit limits, and track all transactions. This phase requires careful attention to financial integrity.

---

## Section 1: Create Models

### 1.1 Wallet Model

**File:** `app/Models/Wallet.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Wallet extends Model
{
    protected $fillable = [
        'agency_id',
        'balance',
        'paid_balance',
        'unpaid_balance',
        'credit_limit',
        'metadata',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'balance' => 'decimal:3',
        'paid_balance' => 'decimal:3',
        'unpaid_balance' => 'decimal:3',
        'credit_limit' => 'decimal:3',
        'metadata' => 'json',
    ];

    public function agency(): BelongsTo
    {
        return $this->belongsTo(TravelAgency::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class)->orderByDesc('created_at');
    }

    public function snapshots(): HasMany
    {
        return $this->hasMany(WalletBalanceSnapshot::class)->orderByDesc('created_at');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // Helper methods
    public function canDebit(float $amount): bool
    {
        return ($this->balance - $amount) >= -$this->credit_limit;
    }

    public function hasWarning(): bool
    {
        $threshold = $this->credit_limit * 0.2;
        return ($this->balance - $threshold) <= 0;
    }

    public function getStatusAttribute(): string
    {
        if ($this->balance < -($this->credit_limit * 0.8)) {
            return 'critical';
        }
        if ($this->balance < -($this->credit_limit * 0.5)) {
            return 'warning';
        }
        if ($this->balance < 0) {
            return 'negative';
        }
        return 'healthy';
    }
}
```

### 1.2 WalletTransaction Model

**File:** `app/Models/WalletTransaction.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WalletTransaction extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'wallet_id',
        'transaction_type_id',
        'amount',
        'balance_after',
        'reference_type',
        'reference_id',
        'description',
        'metadata',
        'created_by',
        'created_at',
    ];

    protected $casts = [
        'amount' => 'decimal:3',
        'balance_after' => 'decimal:3',
        'metadata' => 'json',
        'created_at' => 'datetime',
    ];

    // Immutable - no updates allowed
    protected static function boot()
    {
        parent::boot();

        // Prevent updates
        static::updating(function () {
            throw new \Exception('Wallet transactions are immutable');
        });

        // Prevent deletes
        static::deleting(function () {
            throw new \Exception('Wallet transactions cannot be deleted');
        });
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    public function transactionType(): BelongsTo
    {
        return $this->belongsTo(WalletTransactionType::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeByType($query, string $type)
    {
        return $query->whereHas('transactionType', fn($q) => $q->where('name', $type));
    }
}
```

### 1.3 WalletTransactionType Model

**File:** `app/Models/WalletTransactionType.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WalletTransactionType extends Model
{
    public $timestamps = false;

    protected $fillable = ['name', 'label'];

    public function transactions()
    {
        return $this->hasMany(WalletTransaction::class, 'transaction_type_id');
    }
}
```

### 1.4 WalletBalanceSnapshot Model

**File:** `app/Models/WalletBalanceSnapshot.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WalletBalanceSnapshot extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'wallet_id',
        'balance',
        'paid_balance',
        'unpaid_balance',
        'created_at',
    ];

    protected $casts = [
        'balance' => 'decimal:3',
        'paid_balance' => 'decimal:3',
        'unpaid_balance' => 'decimal:3',
        'created_at' => 'datetime',
    ];

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }
}
```

### 1.5 Invoice & InvoiceItem Models

**File:** `app/Models/Invoice.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'invoice_number',
        'order_id',
        'agency_id',
        'wallet_transaction_id',
        'subtotal',
        'tax',
        'total',
        'currency_code',
        'status',
        'issue_date',
        'due_date',
        'paid_date',
        'notes',
    ];

    protected $casts = [
        'subtotal' => 'decimal:3',
        'tax' => 'decimal:3',
        'total' => 'decimal:3',
        'issue_date' => 'date',
        'due_date' => 'date',
        'paid_date' => 'date',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function agency(): BelongsTo
    {
        return $this->belongsTo(TravelAgency::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function walletTransaction(): BelongsTo
    {
        return $this->belongsTo(WalletTransaction::class);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    public function markAsPaid(): void
    {
        $this->update([
            'status' => 'paid',
            'paid_date' => now()->toDateString(),
        ]);
    }
}
```

**File:** `app/Models/InvoiceItem.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceItem extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'invoice_id',
        'description',
        'quantity',
        'unit_price',
        'amount',
    ];

    protected $casts = [
        'unit_price' => 'decimal:3',
        'amount' => 'decimal:3',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }
}
```

---

## Section 2: Create Services

### 2.1 WalletService

**File:** `app/Services/WalletService.php`

```php
<?php

namespace App\Services;

use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Models\WalletTransactionType;
use App\Models\WalletBalanceSnapshot;
use Illuminate\Support\Facades\DB;

class WalletService
{
    /**
     * Top-up wallet balance
     */
    public function topUp(Wallet $wallet, float $amount, string $description = null): WalletTransaction
    {
        return DB::transaction(function () use ($wallet, $amount, $description) {
            // Get transaction type
            $type = WalletTransactionType::where('name', 'topup')->firstOrFail();

            // Calculate new balance
            $newBalance = $wallet->balance + $amount;

            // Create transaction
            $transaction = $wallet->transactions()->create([
                'transaction_type_id' => $type->id,
                'amount' => $amount,
                'balance_after' => $newBalance,
                'description' => $description ?? 'Wallet top-up',
                'created_by' => auth()->id(),
                'created_at' => now(),
            ]);

            // Update wallet balance
            $wallet->update(['balance' => $newBalance]);

            // Create snapshot
            $this->takeSnapshot($wallet);

            return $transaction;
        });
    }

    /**
     * Debit wallet (for orders, fees, etc.)
     */
    public function debit(
        Wallet $wallet,
        float $amount,
        string $referenceType = null,
        int $referenceId = null,
        string $description = null
    ): WalletTransaction {
        return DB::transaction(function () use ($wallet, $amount, $referenceType, $referenceId, $description) {
            // Validate credit limit
            $newBalance = $wallet->balance - $amount;
            if ($newBalance < -$wallet->credit_limit) {
                throw new \Exception('Insufficient wallet balance. Credit limit would be exceeded.');
            }

            // Get transaction type
            $type = WalletTransactionType::where('name', 'booking_debit')->firstOrFail();

            // Create transaction
            $transaction = $wallet->transactions()->create([
                'transaction_type_id' => $type->id,
                'amount' => -$amount,
                'balance_after' => $newBalance,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'description' => $description ?? 'Booking debit',
                'created_by' => auth()->id(),
                'created_at' => now(),
            ]);

            // Update wallet balance
            $wallet->update(['balance' => $newBalance]);

            // Create snapshot
            $this->takeSnapshot($wallet);

            return $transaction;
        });
    }

    /**
     * Credit wallet (for refunds, adjustments, etc.)
     */
    public function credit(
        Wallet $wallet,
        float $amount,
        string $referenceType = null,
        int $referenceId = null,
        string $description = null
    ): WalletTransaction {
        return DB::transaction(function () use ($wallet, $amount, $referenceType, $referenceId, $description) {
            // Get transaction type
            $type = WalletTransactionType::where('name', 'refund_credit')->firstOrFail();

            // Calculate new balance
            $newBalance = $wallet->balance + $amount;

            // Create transaction
            $transaction = $wallet->transactions()->create([
                'transaction_type_id' => $type->id,
                'amount' => $amount,
                'balance_after' => $newBalance,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'description' => $description ?? 'Refund credit',
                'created_by' => auth()->id(),
                'created_at' => now(),
            ]);

            // Update wallet balance
            $wallet->update(['balance' => $newBalance]);

            // Create snapshot
            $this->takeSnapshot($wallet);

            return $transaction;
        });
    }

    /**
     * Create balance snapshot for audit
     */
    public function takeSnapshot(Wallet $wallet): WalletBalanceSnapshot
    {
        return $wallet->snapshots()->create([
            'balance' => $wallet->balance,
            'paid_balance' => $wallet->paid_balance,
            'unpaid_balance' => $wallet->unpaid_balance,
            'created_at' => now(),
        ]);
    }

    /**
     * Get statement for period
     */
    public function getStatement(Wallet $wallet, ?\DateTime $from = null, ?\DateTime $to = null)
    {
        $query = $wallet->transactions();

        if ($from) {
            $query->where('created_at', '>=', $from);
        }

        if ($to) {
            $query->where('created_at', '<=', $to);
        }

        return $query->with('transactionType')->get();
    }

    /**
     * Recalculate balance from transactions (audit)
     */
    public function recalculateBalance(Wallet $wallet): float
    {
        $balance = 0;
        
        foreach ($wallet->transactions()->oldest('created_at')->get() as $tx) {
            $balance += $tx->amount;
        }

        return $balance;
    }
}
```

### 2.2 InvoiceService

**File:** `app/Services/InvoiceService.php`

```php
<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Order;
use App\Models\Quote;
use Illuminate\Support\Facades\DB;

class InvoiceService
{
    public function __construct(private WalletService $walletService) {}

    /**
     * Create invoice for approved order
     */
    public function createFromOrder(Order $order, Quote $quote): Invoice
    {
        return DB::transaction(function () use ($order, $quote) {
            // Generate invoice number: INV-DDMMYY-XXXX
            $invoiceNumber = $this->generateInvoiceNumber();

            // Create invoice
            $invoice = Invoice::create([
                'invoice_number' => $invoiceNumber,
                'order_id' => $order->id,
                'agency_id' => $order->agency_id,
                'subtotal' => $quote->base_price,
                'tax' => $quote->taxes,
                'total' => $quote->total_price,
                'currency_code' => $quote->currency_code,
                'status' => 'issued',
                'issue_date' => now()->toDateString(),
                'due_date' => now()->addDays(30)->toDateString(),
            ]);

            // Add line item for ticket
            $invoice->items()->create([
                'description' => "Ticket for flight {$order->order_number}",
                'quantity' => 1,
                'unit_price' => $quote->base_price,
                'amount' => $quote->base_price,
            ]);

            if ($quote->taxes > 0) {
                $invoice->items()->create([
                    'description' => 'Taxes',
                    'quantity' => 1,
                    'unit_price' => $quote->taxes,
                    'amount' => $quote->taxes,
                ]);
            }

            if ($quote->fees > 0) {
                $invoice->items()->create([
                    'description' => 'Platform fees',
                    'quantity' => 1,
                    'unit_price' => $quote->fees,
                    'amount' => $quote->fees,
                ]);
            }

            // Debit wallet
            $wallet = $order->agency->wallet;
            $transaction = $this->walletService->debit(
                $wallet,
                $quote->total_price,
                'Invoice',
                $invoice->id,
                "Invoice {$invoiceNumber} for order {$order->order_number}"
            );

            // Link transaction to invoice
            $invoice->update(['wallet_transaction_id' => $transaction->id]);

            return $invoice;
        });
    }

    private function generateInvoiceNumber(): string
    {
        $date = now()->format('dmyH');
        $random = str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        return "INV-{$date}-{$random}";
    }
}
```

---

## Section 3: Create Controllers

### 3.1 WalletController

**File:** `app/Http/Controllers/WalletController.php`

```php
<?php

namespace App\Http\Controllers;

use App\Models\Wallet;
use App\Http\Requests\TopUpWalletRequest;
use App\Http\Requests\SetCreditLimitRequest;
use App\Services\WalletService;
use Illuminate\Http\JsonResponse;

class WalletController extends Controller
{
    public function __construct(private WalletService $walletService) {}

    public function show(Wallet $wallet): JsonResponse
    {
        $this->authorize('view', $wallet);

        return response()->json($wallet->load(['agency', 'transactions']));
    }

    public function topUp(TopUpWalletRequest $request, Wallet $wallet): JsonResponse
    {
        $this->authorize('topup', $wallet);

        try {
            $transaction = $this->walletService->topUp(
                $wallet,
                $request->amount,
                $request->description
            );

            return response()->json([
                'message' => 'Wallet topped up successfully',
                'transaction' => $transaction,
                'wallet' => $wallet->fresh(),
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function setCreditLimit(SetCreditLimitRequest $request, Wallet $wallet): JsonResponse
    {
        $this->authorize('set-credit-limit', $wallet);

        $wallet->update(['credit_limit' => $request->credit_limit]);

        return response()->json([
            'message' => 'Credit limit updated',
            'wallet' => $wallet,
        ]);
    }

    public function statement(Wallet $wallet): JsonResponse
    {
        $this->authorize('view', $wallet);

        $from = request()->query('from') ? new \DateTime(request()->query('from')) : null;
        $to = request()->query('to') ? new \DateTime(request()->query('to')) : null;

        $transactions = $this->walletService->getStatement($wallet, $from, $to);

        return response()->json([
            'wallet' => $wallet,
            'transactions' => $transactions,
        ]);
    }
}
```

---

## Section 4: Create Requests

### 4.1 TopUpWalletRequest

**File:** `app/Http/Requests/TopUpWalletRequest.php`

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TopUpWalletRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('wallet:topup');
    }

    public function rules(): array
    {
        return [
            'amount' => 'required|numeric|min:1|max:1000000',
            'description' => 'nullable|string|max:500',
        ];
    }
}
```

### 4.2 SetCreditLimitRequest

**File:** `app/Http/Requests/SetCreditLimitRequest.php`

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SetCreditLimitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('wallet:set-credit-limit');
    }

    public function rules(): array
    {
        return [
            'credit_limit' => 'required|numeric|min:0|max:10000000',
        ];
    }
}
```

---

## Section 5: Frontend Components

### 5.1 WalletDashboard.vue (Agency Side)

**File:** `frontend/src/components/agency/WalletDashboard.vue`

```vue
<template>
  <div class="max-w-4xl mx-auto">
    <h1 class="text-3xl font-bold mb-6">Wallet & Balance</h1>

    <div v-if="loading" class="text-center py-8">Loading...</div>

    <div v-if="!loading && wallet" class="space-y-6">
      <!-- Balance Cards -->
      <div class="grid grid-cols-3 gap-4">
        <div class="bg-white rounded-lg shadow p-6" :class="statusColor">
          <p class="text-gray-600 text-sm">Current Balance</p>
          <p class="text-4xl font-bold mt-2">{{ formatCurrency(wallet.balance) }}</p>
          <p class="text-xs text-gray-500 mt-1" :class="statusTextColor">{{ wallet.status }}</p>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
          <p class="text-gray-600 text-sm">Credit Limit</p>
          <p class="text-4xl font-bold mt-2">{{ formatCurrency(wallet.credit_limit) }}</p>
          <p class="text-xs text-gray-500 mt-1">Available</p>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
          <p class="text-gray-600 text-sm">Available Funds</p>
          <p class="text-4xl font-bold mt-2">{{ formatCurrency(wallet.balance + wallet.credit_limit) }}</p>
          <p class="text-xs text-gray-500 mt-1">Balance + Limit</p>
        </div>
      </div>

      <!-- Request Top-Up -->
      <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
        <h2 class="text-lg font-semibold mb-4">Request Wallet Top-Up</h2>
        <form @submit.prevent="requestTopUp" class="space-y-4">
          <div>
            <label class="block text-sm font-medium mb-1">Amount</label>
            <input
              v-model.number="topUpForm.amount"
              type="number"
              step="0.01"
              min="0"
              class="w-full border rounded px-4 py-2"
              placeholder="Enter amount"
            />
          </div>
          <div>
            <label class="block text-sm font-medium mb-1">Description</label>
            <textarea
              v-model="topUpForm.description"
              class="w-full border rounded px-4 py-2"
              rows="3"
              placeholder="Reason for top-up (optional)"
            ></textarea>
          </div>
          <button
            type="submit"
            :disabled="topUpLoading"
            class="w-full bg-blue-600 text-white py-2 rounded font-semibold hover:bg-blue-700 disabled:opacity-50"
          >
            {{ topUpLoading ? 'Requesting...' : 'Request Top-Up' }}
          </button>
        </form>
      </div>

      <!-- Transaction History -->
      <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold mb-4">Transaction History</h2>
        
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="border-b">
                <th class="text-left py-2">Date</th>
                <th class="text-left py-2">Type</th>
                <th class="text-left py-2">Description</th>
                <th class="text-right py-2">Amount</th>
                <th class="text-right py-2">Balance After</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="tx in wallet.transactions" :key="tx.id" class="border-b hover:bg-gray-50">
                <td class="py-3">{{ formatDate(tx.created_at) }}</td>
                <td class="py-3">
                  <span class="px-2 py-1 rounded text-xs font-semibold" :class="txTypeClass(tx)">
                    {{ tx.transaction_type.label }}
                  </span>
                </td>
                <td class="py-3">{{ tx.description }}</td>
                <td class="py-3 text-right" :class="tx.amount > 0 ? 'text-green-600' : 'text-red-600'">
                  {{ tx.amount > 0 ? '+' : '' }}{{ formatCurrency(tx.amount) }}
                </td>
                <td class="py-3 text-right font-semibold">{{ formatCurrency(tx.balance_after) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';

const wallet = ref(null);
const loading = ref(true);
const topUpLoading = ref(false);
const topUpForm = ref({
  amount: '',
  description: '',
});

const API_URL = import.meta.env.VITE_API_URL || 'http://localhost:8000/api';

const statusColor = computed(() => {
  if (!wallet.value) return '';
  if (wallet.value.status === 'critical') return 'bg-red-50 border-l-4 border-red-500';
  if (wallet.value.status === 'warning') return 'bg-yellow-50 border-l-4 border-yellow-500';
  if (wallet.value.status === 'negative') return 'bg-orange-50 border-l-4 border-orange-500';
  return 'bg-green-50 border-l-4 border-green-500';
});

const statusTextColor = computed(() => {
  if (!wallet.value) return '';
  if (wallet.value.status === 'critical') return 'text-red-600';
  if (wallet.value.status === 'warning') return 'text-yellow-600';
  if (wallet.value.status === 'negative') return 'text-orange-600';
  return 'text-green-600';
});

onMounted(async () => {
  try {
    const response = await axios.get(`${API_URL}/wallets`, {
      headers: { Authorization: `Bearer ${localStorage.getItem('auth_token')}` },
    });
    wallet.value = response.data;
  } catch (err) {
    console.error('Failed to load wallet', err);
  } finally {
    loading.value = false;
  }
});

const requestTopUp = async () => {
  topUpLoading.value = true;
  try {
    const response = await axios.post(
      `${API_URL}/wallets/${wallet.value.id}/topup`,
      topUpForm.value,
      {
        headers: { Authorization: `Bearer ${localStorage.getItem('auth_token')}` },
      }
    );
    wallet.value = response.data.wallet;
    topUpForm.value = { amount: '', description: '' };
  } catch (err) {
    console.error('Top-up request failed', err);
  } finally {
    topUpLoading.value = false;
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

const txTypeClass = (tx) => {
  const type = tx.transaction_type.name;
  if (type === 'topup') return 'bg-green-100 text-green-800';
  if (type === 'booking_debit') return 'bg-red-100 text-red-800';
  if (type === 'refund_credit') return 'bg-blue-100 text-blue-800';
  return 'bg-gray-100 text-gray-800';
};
</script>
```

### 5.2 WalletTopUp.vue (Admin Side)

**File:** `frontend/src/components/admin/WalletTopUp.vue`

```vue
<template>
  <div class="max-w-2xl mx-auto">
    <h1 class="text-3xl font-bold mb-6">Wallet Top-Up</h1>

    <div class="bg-white rounded-lg shadow p-6 space-y-6">
      <div>
        <label class="block text-sm font-medium mb-2">Select Agency</label>
        <select
          v-model="selectedAgency"
          @change="loadWallet"
          class="w-full border rounded px-4 py-2"
        >
          <option value="">-- Select Agency --</option>
          <option v-for="agency in agencies" :key="agency.id" :value="agency.id">
            {{ agency.name }} ({{ agency.uuid }})
          </option>
        </select>
      </div>

      <div v-if="wallet" class="bg-blue-50 p-4 rounded border border-blue-200">
        <p class="text-sm text-gray-600">Current Balance</p>
        <p class="text-3xl font-bold">{{ formatCurrency(wallet.balance) }}</p>
        <p class="text-xs text-gray-500 mt-1">Credit Limit: {{ formatCurrency(wallet.credit_limit) }}</p>
      </div>

      <div v-if="wallet">
        <label class="block text-sm font-medium mb-2">Top-Up Amount</label>
        <input
          v-model.number="topUpAmount"
          type="number"
          step="0.01"
          min="0"
          class="w-full border rounded px-4 py-2 text-lg"
          placeholder="0.00"
        />
      </div>

      <div v-if="wallet">
        <label class="block text-sm font-medium mb-2">Description</label>
        <textarea
          v-model="topUpDescription"
          class="w-full border rounded px-4 py-2"
          rows="3"
          placeholder="Bank transfer verified, etc."
        ></textarea>
      </div>

      <div v-if="error" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
        {{ error }}
      </div>

      <button
        v-if="wallet"
        @click="submitTopUp"
        :disabled="!topUpAmount || topUpLoading"
        class="w-full bg-green-600 text-white py-2 rounded font-semibold hover:bg-green-700 disabled:opacity-50"
      >
        {{ topUpLoading ? 'Processing...' : 'Process Top-Up' }}
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';

const agencies = ref([]);
const wallet = ref(null);
const selectedAgency = ref('');
const topUpAmount = ref('');
const topUpDescription = ref('');
const topUpLoading = ref(false);
const error = ref('');

const API_URL = import.meta.env.VITE_API_URL || 'http://localhost:8000/api';

onMounted(async () => {
  try {
    const response = await axios.get(`${API_URL}/agencies`, {
      headers: { Authorization: `Bearer ${localStorage.getItem('auth_token')}` },
    });
    agencies.value = response.data;
  } catch (err) {
    error.value = 'Failed to load agencies';
  }
});

const loadWallet = async () => {
  if (!selectedAgency.value) {
    wallet.value = null;
    return;
  }

  try {
    const response = await axios.get(`${API_URL}/wallets/agency/${selectedAgency.value}`, {
      headers: { Authorization: `Bearer ${localStorage.getItem('auth_token')}` },
    });
    wallet.value = response.data;
  } catch (err) {
    error.value = 'Failed to load wallet';
  }
};

const submitTopUp = async () => {
  if (!topUpAmount.value || !wallet.value) return;

  topUpLoading.value = true;
  error.value = '';

  try {
    const response = await axios.post(
      `${API_URL}/wallets/${wallet.value.id}/topup`,
      {
        amount: topUpAmount.value,
        description: topUpDescription.value,
      },
      {
        headers: { Authorization: `Bearer ${localStorage.getItem('auth_token')}` },
      }
    );

    wallet.value = response.data.wallet;
    topUpAmount.value = '';
    topUpDescription.value = '';
  } catch (err) {
    error.value = err.response?.data?.error || 'Top-up failed';
  } finally {
    topUpLoading.value = false;
  }
};

const formatCurrency = (value) => {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD',
  }).format(value);
};
</script>
```

---

## Section 6: Testing

**File:** `backend/tests/Feature/Wallet/TopUpTest.php`

```php
<?php

namespace Tests\Feature\Wallet;

use App\Models\Wallet;
use App\Models\TravelAgency;
use App\Models\User;
use App\Services\WalletService;
use Tests\TestCase;

class TopUpTest extends TestCase
{
    public function test_admin_can_topup_wallet()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('wallet:topup');

        $agency = TravelAgency::factory()->create();
        $wallet = Wallet::factory()->create(['agency_id' => $agency->id]);
        $initialBalance = $wallet->balance;

        $response = $this->actingAs($user)->postJson(
            "/api/wallets/{$wallet->id}/topup",
            [
                'amount' => 1000,
                'description' => 'Bank transfer verified',
            ]
        );

        $response->assertStatus(200);
        $this->assertDatabaseHas('wallets', [
            'id' => $wallet->id,
            'balance' => $initialBalance + 1000,
        ]);
    }

    public function test_debit_fails_if_exceeds_credit_limit()
    {
        $wallet = Wallet::factory()->create([
            'balance' => 100,
            'credit_limit' => 500,
        ]);

        $service = app(WalletService::class);

        $this->expectException(\Exception::class);
        $service->debit($wallet, 700);
    }

    public function test_balance_cannot_be_negative_beyond_limit()
    {
        $wallet = Wallet::factory()->create([
            'balance' => 100,
            'credit_limit' => 500,
        ]);

        $service = app(WalletService::class);

        // Should succeed: 100 - 400 = -300 (within -500 limit)
        $transaction = $service->debit($wallet, 400);
        $this->assertEquals(-300, $transaction->balance_after);

        // Should fail: -300 - 300 = -600 (exceeds -500 limit)
        $this->expectException(\Exception::class);
        $service->debit($wallet, 300);
    }
}
```

---

## Section 7: Verification Checklist

- [x] Wallet model with balance tracking
- [x] WalletTransaction immutable append-only log
- [x] WalletTransactionType reference
- [x] WalletBalanceSnapshot for audit
- [x] Invoice and InvoiceItem models
- [x] WalletService with topUp, debit, credit, snapshot
- [x] InvoiceService for order invoices
- [x] WalletController endpoints
- [x] Request validation classes
- [x] WalletDashboard component (agency)
- [x] WalletTopUp component (admin)
- [x] Feature tests for wallet operations
- [x] Credit limit enforcement

**Next:** Proceed to Phase 5 for Refunds & Settlement
