# Phase 5: Refunds & Financial Settlement

**Duration:** Week 4  
**Priority:** HIGH  
**Objective:** Handle refunds with proper audit trail and credit notes

---

## Overview

Phase 5 implements the refund workflow. Agencies request refunds, admins calculate and approve them, and approved refunds are immediately credited to wallets. Credit notes serve as immutable audit records.

---

## Section 1: Create Models

### 1.1 Refund Model

**File:** `app/Models/Refund.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Refund extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'ticket_id',
        'agency_id',
        'refund_reason_id',
        'ticket_price',
        'supplier_penalty',
        'platform_fee',
        'refund_amount',
        'currency_code',
        'status',
        'remarks',
        'requested_at',
        'approved_at',
        'approved_by',
    ];

    protected $casts = [
        'ticket_price' => 'decimal:3',
        'supplier_penalty' => 'decimal:3',
        'platform_fee' => 'decimal:3',
        'refund_amount' => 'decimal:3',
        'requested_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function agency(): BelongsTo
    {
        return $this->belongsTo(TravelAgency::class);
    }

    public function reason(): BelongsTo
    {
        return $this->belongsTo(RefundReason::class, 'refund_reason_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function creditNote(): HasOne
    {
        return $this->hasOne(CreditNote::class);
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeCredited($query)
    {
        return $query->where('status', 'credited');
    }

    // Helpers
    public function canBeApproved(): bool
    {
        return $this->status === 'pending';
    }

    public function markAsApproved(?User $user = null): void
    {
        $this->update([
            'status' => 'approved',
            'approved_at' => now(),
            'approved_by' => $user?->id ?? auth()->id(),
        ]);
    }

    public function markAsCredited(): void
    {
        $this->update(['status' => 'credited']);
    }
}
```

### 1.2 RefundReason Model

**File:** `app/Models/RefundReason.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RefundReason extends Model
{
    public $timestamps = false;

    protected $fillable = ['name', 'description'];

    public function refunds()
    {
        return $this->hasMany(Refund::class);
    }
}
```

### 1.3 CreditNote Model

**File:** `app/Models/CreditNote.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CreditNote extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'credit_note_number',
        'refund_id',
        'invoice_id',
        'amount',
        'currency_code',
        'issue_date',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:3',
        'issue_date' => 'date',
    ];

    // Immutable - no updates
    protected static function boot()
    {
        parent::boot();

        static::updating(function () {
            throw new \Exception('Credit notes are immutable');
        });

        static::deleting(function () {
            throw new \Exception('Credit notes cannot be deleted');
        });
    }

    public function refund(): BelongsTo
    {
        return $this->belongsTo(Refund::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }
}
```

---

## Section 2: Create Services

### 2.1 RefundService

**File:** `app/Services/RefundService.php`

```php
<?php

namespace App\Services;

use App\Models\Refund;
use App\Models\Ticket;
use App\Models\CreditNote;
use Illuminate\Support\Facades\DB;

class RefundService
{
    public function __construct(private WalletService $walletService) {}

    /**
     * Request refund on ticket
     */
    public function requestRefund(Ticket $ticket, array $data): Refund
    {
        return DB::transaction(function () use ($ticket, $data) {
            $order = $ticket->order;
            
            // Calculate refund (default: full refund minus penalties)
            $ticketPrice = $ticket->price;
            $supplierPenalty = $data['supplier_penalty'] ?? 0;
            $platformFee = $data['platform_fee'] ?? 0;
            $refundAmount = $ticketPrice - $supplierPenalty - $platformFee;

            // Create refund request
            $refund = Refund::create([
                'ticket_id' => $ticket->id,
                'agency_id' => $order->agency_id,
                'refund_reason_id' => $data['refund_reason_id'] ?? null,
                'ticket_price' => $ticketPrice,
                'supplier_penalty' => $supplierPenalty,
                'platform_fee' => $platformFee,
                'refund_amount' => max(0, $refundAmount), // Can't refund negative
                'currency_code' => $ticket->currency_code,
                'status' => 'pending',
                'remarks' => $data['remarks'] ?? null,
                'requested_at' => now(),
            ]);

            return $refund;
        });
    }

    /**
     * Approve refund and credit wallet
     */
    public function approveRefund(Refund $refund, ?string $notes = null): CreditNote
    {
        return DB::transaction(function () use ($refund, $notes) {
            // Validate
            if (!$refund->canBeApproved()) {
                throw new \Exception('Refund cannot be approved in current status');
            }

            // Mark approved
            $refund->markAsApproved(auth()->user());

            // Credit wallet
            $wallet = $refund->agency->wallet;
            $this->walletService->credit(
                $wallet,
                $refund->refund_amount,
                'Refund',
                $refund->id,
                "Refund for ticket {$refund->ticket->ticket_number}"
            );

            // Get original invoice
            $invoice = $refund->ticket->order->invoices()->first();

            // Create credit note
            $creditNote = CreditNote::create([
                'credit_note_number' => $this->generateCreditNoteNumber(),
                'refund_id' => $refund->id,
                'invoice_id' => $invoice->id,
                'amount' => $refund->refund_amount,
                'currency_code' => $refund->currency_code,
                'issue_date' => now()->toDateString(),
                'notes' => $notes,
            ]);

            // Mark refund as credited
            $refund->markAsCredited();

            return $creditNote;
        });
    }

    /**
     * Reject refund
     */
    public function rejectRefund(Refund $refund, string $reason): void
    {
        if (!$refund->canBeApproved()) {
            throw new \Exception('Refund cannot be rejected in current status');
        }

        $refund->update([
            'status' => 'rejected',
            'remarks' => $reason,
        ]);
    }

    /**
     * Calculate dynamic refund based on rules
     */
    public function calculateRefund(Ticket $ticket): array
    {
        $basePrice = $ticket->price;
        $order = $ticket->order;
        $airline = $order->segments()->first()->airline;

        // For Sudanese carriers, require admin approval
        // Calculate default penalties (configurable via settings)
        $supplierPenalty = $basePrice * 0.10; // 10% supplier penalty
        $platformFee = $basePrice * 0.05; // 5% platform fee

        return [
            'ticket_price' => $basePrice,
            'supplier_penalty' => $supplierPenalty,
            'platform_fee' => $platformFee,
            'refund_amount' => $basePrice - $supplierPenalty - $platformFee,
            'requires_approval' => $airline->is_local, // Sudanese carriers need approval
        ];
    }

    private function generateCreditNoteNumber(): string
    {
        $date = now()->format('dmyH');
        $random = str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        return "CN-{$date}-{$random}";
    }
}
```

---

## Section 3: Create Controllers

### 3.1 RefundController

**File:** `app/Http/Controllers/RefundController.php`

```php
<?php

namespace App\Http\Controllers;

use App\Models\Refund;
use App\Models\Ticket;
use App\Http\Requests\RequestRefundRequest;
use App\Http\Requests\ApproveRefundRequest;
use App\Services\RefundService;
use Illuminate\Http\JsonResponse;

class RefundController extends Controller
{
    public function __construct(private RefundService $refundService) {}

    public function index(): JsonResponse
    {
        $refunds = Refund::with([
            'ticket',
            'agency',
            'reason',
            'approvedBy',
            'creditNote',
        ])->paginate(20);

        return response()->json($refunds);
    }

    public function show(Refund $refund): JsonResponse
    {
        $this->authorize('view', $refund);

        return response()->json($refund->load([
            'ticket',
            'agency',
            'reason',
            'approvedBy',
            'creditNote',
        ]));
    }

    public function store(RequestRefundRequest $request): JsonResponse
    {
        $this->authorize('request', Refund::class);

        try {
            $ticket = Ticket::findOrFail($request->ticket_id);

            $refund = $this->refundService->requestRefund($ticket, $request->validated());

            return response()->json([
                'message' => 'Refund request submitted',
                'refund' => $refund->load(['ticket', 'agency']),
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function approve(ApproveRefundRequest $request, Refund $refund): JsonResponse
    {
        $this->authorize('approve', $refund);

        try {
            $creditNote = $this->refundService->approveRefund(
                $refund,
                $request->notes ?? null
            );

            return response()->json([
                'message' => 'Refund approved and credited',
                'refund' => $refund->fresh(['creditNote']),
                'creditNote' => $creditNote,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function reject(Refund $refund): JsonResponse
    {
        $this->authorize('approve', $refund);

        try {
            $reason = request()->input('reason', 'Rejected by admin');
            $this->refundService->rejectRefund($refund, $reason);

            return response()->json([
                'message' => 'Refund rejected',
                'refund' => $refund->fresh(),
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }
}
```

---

## Section 4: Create Requests

### 4.1 RequestRefundRequest

**File:** `app/Http/Requests/RequestRefundRequest.php`

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RequestRefundRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('refund:request');
    }

    public function rules(): array
    {
        return [
            'ticket_id' => 'required|integer|exists:tickets,id',
            'refund_reason_id' => 'nullable|integer|exists:refund_reasons,id',
            'supplier_penalty' => 'nullable|numeric|min:0',
            'platform_fee' => 'nullable|numeric|min:0',
            'remarks' => 'nullable|string|max:1000',
        ];
    }
}
```

### 4.2 ApproveRefundRequest

**File:** `app/Http/Requests/ApproveRefundRequest.php`

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ApproveRefundRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('refund:approve');
    }

    public function rules(): array
    {
        return [
            'notes' => 'nullable|string|max:1000',
        ];
    }
}
```

---

## Section 5: API Routes

**File:** `routes/api.php` (add to existing)

```php
Route::middleware('auth:sanctum')->group(function () {
    // Refund routes
    Route::get('/refunds', [RefundController::class, 'index'])
        ->middleware('permission:refund:view');
    
    Route::post('/refunds', [RefundController::class, 'store'])
        ->middleware('permission:refund:request');
    
    Route::get('/refunds/{refund}', [RefundController::class, 'show'])
        ->middleware('permission:refund:view');
    
    Route::put('/refunds/{refund}/approve', [RefundController::class, 'approve'])
        ->middleware('permission:refund:approve');
    
    Route::put('/refunds/{refund}/reject', [RefundController::class, 'reject'])
        ->middleware('permission:refund:approve');
});
```

---

## Section 6: Frontend Components

### 6.1 RefundQueue.vue (Admin Side)

**File:** `frontend/src/components/admin/RefundQueue.vue`

```vue
<template>
  <div>
    <h1 class="text-3xl font-bold mb-6">Pending Refunds</h1>

    <div v-if="loading" class="text-center py-8">Loading...</div>

    <div v-if="!loading && refunds.length === 0" class="text-center py-8 text-gray-500">
      No pending refunds
    </div>

    <div v-if="!loading && refunds.length > 0" class="space-y-4">
      <div
        v-for="refund in refunds"
        :key="refund.id"
        class="bg-white border rounded-lg p-4 hover:shadow-md transition"
      >
        <div class="flex justify-between items-start mb-4">
          <div>
            <h3 class="font-semibold text-lg">{{ refund.ticket.ticket_number }}</h3>
            <p class="text-gray-600">{{ refund.agency.name }}</p>
            <p class="text-sm text-gray-500">{{ formatDate(refund.requested_at) }}</p>
          </div>
          <span class="inline-block bg-yellow-100 text-yellow-800 px-3 py-1 rounded text-sm font-semibold">
            Pending Approval
          </span>
        </div>

        <div class="grid grid-cols-3 gap-4 mb-4 p-4 bg-gray-50 rounded">
          <div>
            <p class="text-xs text-gray-600">Ticket Price</p>
            <p class="text-lg font-semibold">{{ formatCurrency(refund.ticket_price) }}</p>
          </div>
          <div>
            <p class="text-xs text-gray-600">Penalties</p>
            <p class="text-lg font-semibold text-red-600">
              -{{ formatCurrency(refund.supplier_penalty + refund.platform_fee) }}
            </p>
          </div>
          <div>
            <p class="text-xs text-gray-600">Refund Amount</p>
            <p class="text-lg font-semibold text-green-600">{{ formatCurrency(refund.refund_amount) }}</p>
          </div>
        </div>

        <div v-if="refund.remarks" class="mb-4 p-3 bg-blue-50 rounded text-sm">
          <p class="font-semibold text-blue-900">Remarks:</p>
          <p class="text-blue-800">{{ refund.remarks }}</p>
        </div>

        <div class="flex gap-2">
          <button
            @click="approveRefund(refund)"
            class="flex-1 bg-green-600 text-white py-2 rounded font-semibold hover:bg-green-700"
          >
            Approve
          </button>
          <button
            @click="showRejectForm(refund)"
            class="flex-1 bg-red-600 text-white py-2 rounded font-semibold hover:bg-red-700"
          >
            Reject
          </button>
        </div>
      </div>
    </div>

    <!-- Reject Modal -->
    <div v-if="rejectingRefund" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4">
      <div class="bg-white rounded-lg max-w-md w-full p-6">
        <h2 class="text-xl font-bold mb-4">Reject Refund</h2>
        <textarea
          v-model="rejectReason"
          class="w-full border rounded px-4 py-2 mb-4"
          rows="4"
          placeholder="Reason for rejection"
        ></textarea>
        <div class="flex gap-2">
          <button
            @click="submitReject"
            class="flex-1 bg-red-600 text-white py-2 rounded font-semibold"
          >
            Reject
          </button>
          <button
            @click="rejectingRefund = null"
            class="flex-1 bg-gray-300 text-gray-700 py-2 rounded font-semibold"
          >
            Cancel
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';

const refunds = ref([]);
const loading = ref(true);
const rejectingRefund = ref(null);
const rejectReason = ref('');

const API_URL = import.meta.env.VITE_API_URL || 'http://localhost:8000/api';

onMounted(async () => {
  try {
    const response = await axios.get(`${API_URL}/refunds?status=pending`, {
      headers: { Authorization: `Bearer ${localStorage.getItem('auth_token')}` },
    });
    refunds.value = response.data.data || response.data;
  } catch (err) {
    console.error('Failed to load refunds', err);
  } finally {
    loading.value = false;
  }
});

const approveRefund = async (refund) => {
  try {
    const response = await axios.put(
      `${API_URL}/refunds/${refund.id}/approve`,
      {},
      {
        headers: { Authorization: `Bearer ${localStorage.getItem('auth_token')}` },
      }
    );
    refunds.value = refunds.value.filter(r => r.id !== refund.id);
  } catch (err) {
    console.error('Failed to approve refund', err);
  }
};

const showRejectForm = (refund) => {
  rejectingRefund.value = refund;
  rejectReason.value = '';
};

const submitReject = async () => {
  if (!rejectingRefund.value) return;

  try {
    await axios.put(
      `${API_URL}/refunds/${rejectingRefund.value.id}/reject`,
      { reason: rejectReason.value },
      {
        headers: { Authorization: `Bearer ${localStorage.getItem('auth_token')}` },
      }
    );
    refunds.value = refunds.value.filter(r => r.id !== rejectingRefund.value.id);
    rejectingRefund.value = null;
  } catch (err) {
    console.error('Failed to reject refund', err);
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
</script>
```

### 6.2 RefundRequest.vue (Agency Side)

**File:** `frontend/src/components/agency/RefundRequest.vue`

```vue
<template>
  <div class="max-w-2xl mx-auto">
    <h1 class="text-3xl font-bold mb-6">Request Refund</h1>

    <form @submit.prevent="submitRequest" class="space-y-6 bg-white p-6 rounded-lg shadow">
      <div>
        <label class="block text-sm font-medium mb-2">Select Ticket</label>
        <select v-model="form.ticket_id" class="w-full border rounded px-4 py-2" required>
          <option value="">-- Select Ticket --</option>
          <option v-for="ticket in tickets" :key="ticket.id" :value="ticket.id">
            {{ ticket.ticket_number }} - {{ ticket.order.order_number }}
          </option>
        </select>
      </div>

      <div v-if="selectedTicket" class="bg-blue-50 p-4 rounded border border-blue-200">
        <p class="text-sm text-gray-600">Ticket Price</p>
        <p class="text-2xl font-bold">{{ formatCurrency(selectedTicket.price) }}</p>
      </div>

      <div>
        <label class="block text-sm font-medium mb-2">Reason for Refund</label>
        <select v-model="form.refund_reason_id" class="w-full border rounded px-4 py-2">
          <option value="">-- Select Reason --</option>
          <option v-for="reason in refundReasons" :key="reason.id" :value="reason.id">
            {{ reason.name }}
          </option>
        </select>
      </div>

      <div>
        <label class="block text-sm font-medium mb-2">Additional Remarks</label>
        <textarea
          v-model="form.remarks"
          class="w-full border rounded px-4 py-2"
          rows="4"
          placeholder="Any additional information for admin..."
        ></textarea>
      </div>

      <div v-if="error" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
        {{ error }}
      </div>

      <button
        type="submit"
        :disabled="!form.ticket_id || loading"
        class="w-full bg-blue-600 text-white py-2 rounded font-semibold hover:bg-blue-700 disabled:opacity-50"
      >
        {{ loading ? 'Submitting...' : 'Submit Refund Request' }}
      </button>
    </form>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';

const tickets = ref([]);
const refundReasons = ref([]);
const loading = ref(false);
const error = ref('');

const form = ref({
  ticket_id: '',
  refund_reason_id: '',
  remarks: '',
});

const API_URL = import.meta.env.VITE_API_URL || 'http://localhost:8000/api';

const selectedTicket = computed(() => {
  return tickets.value.find(t => t.id == form.value.ticket_id);
});

onMounted(async () => {
  try {
    const [ticketsRes, reasonsRes] = await Promise.all([
      axios.get(`${API_URL}/tickets`, {
        headers: { Authorization: `Bearer ${localStorage.getItem('auth_token')}` },
      }),
      axios.get(`${API_URL}/refund-reasons`, {
        headers: { Authorization: `Bearer ${localStorage.getItem('auth_token')}` },
      }),
    ]);

    tickets.value = ticketsRes.data;
    refundReasons.value = reasonsRes.data;
  } catch (err) {
    error.value = 'Failed to load data';
  }
});

const submitRequest = async () => {
  loading.value = true;
  error.value = '';

  try {
    const response = await axios.post(`${API_URL}/refunds`, form.value, {
      headers: { Authorization: `Bearer ${localStorage.getItem('auth_token')}` },
    });

    // Reset form
    form.value = { ticket_id: '', refund_reason_id: '', remarks: '' };
    alert('Refund request submitted successfully!');
  } catch (err) {
    error.value = err.response?.data?.error || 'Failed to submit refund request';
  } finally {
    loading.value = false;
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

## Section 7: Testing

**File:** `backend/tests/Feature/Refunds/ProcessRefundTest.php`

```php
<?php

namespace Tests\Feature\Refunds;

use App\Models\Refund;
use App\Models\Ticket;
use App\Models\Order;
use App\Models\User;
use App\Services\RefundService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProcessRefundTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_approve_refund()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('refund:approve');

        $ticket = Ticket::factory()->create(['price' => 1000]);
        $refund = Refund::factory()->create([
            'ticket_id' => $ticket->id,
            'status' => 'pending',
            'ticket_price' => 1000,
            'refund_amount' => 900,
        ]);

        $response = $this->actingAs($user)->putJson(
            "/api/refunds/{$refund->id}/approve",
            ['notes' => 'Approved']
        );

        $response->assertStatus(200);
        $this->assertDatabaseHas('refunds', [
            'id' => $refund->id,
            'status' => 'credited',
        ]);
        $this->assertDatabaseHas('credit_notes', [
            'refund_id' => $refund->id,
        ]);
    }

    public function test_refund_credits_wallet_immediately()
    {
        $ticket = Ticket::factory()->create(['price' => 1000]);
        $order = $ticket->order;
        $wallet = $order->agency->wallet;
        $initialBalance = $wallet->balance;

        $refund = Refund::factory()->create([
            'ticket_id' => $ticket->id,
            'refund_amount' => 900,
        ]);

        $service = app(RefundService::class);
        $service->approveRefund($refund);

        $wallet->refresh();
        $this->assertEquals($initialBalance + 900, $wallet->balance);
    }
}
```

---

## Section 8: Verification Checklist

- [x] Refund model with status tracking
- [x] RefundReason reference table
- [x] CreditNote immutable model
- [x] RefundService with calculation & approval
- [x] RefundController endpoints
- [x] Request validation classes
- [x] Wallet credit on approval
- [x] Credit note generation
- [x] RefundQueue component (admin)
- [x] RefundRequest component (agency)
- [x] Feature tests for refund flow
- [x] Immutable credit notes

**Next:** Proceed to Phase 6 for Admin Dashboard
