# Phase 3: Core Booking System (Orders & Requests)

**Duration:** Week 2-3  
**Priority:** HIGH  
**Objective:** Implement the ticket request workflow from agency creation to admin provisioning

---

## Overview

Phase 3 implements the complete order workflow: agencies create ticket requests, admins receive them immediately, price them, and send binding offers. This phase is the backbone of the platform.

---

## Section 1: Create Models

### 1.1 Order Model

**File:** `app/Models/Order.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'order_number',
        'agency_id',
        'status_id',
        'estimated_price',
        'total_passengers',
        'departure_date',
        'return_date',
        'metadata',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'metadata' => 'json',
        'departure_date' => 'date',
        'return_date' => 'date',
        'estimated_price' => 'decimal:3',
    ];

    // Relationships
    public function agency(): BelongsTo
    {
        return $this->belongsTo(TravelAgency::class);
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(OrderStatus::class);
    }

    public function segments(): HasMany
    {
        return $this->hasMany(OrderSegment::class)->orderBy('sequence');
    }

    public function passengers(): HasMany
    {
        return $this->hasMany(OrderPassenger::class)->orderBy('sequence');
    }

    public function quotes(): HasMany
    {
        return $this->hasMany(Quote::class);
    }

    public function latestQuote(): HasOne
    {
        return $this->hasOne(Quote::class)->latestOfMany();
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class)->orderByDesc('created_at');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Scopes
    public function scopePendingPricing($query)
    {
        return $query->whereHas('status', fn($q) => $q->where('name', 'pending_pricing'));
    }

    public function scopePricingSent($query)
    {
        return $query->whereHas('status', fn($q) => $q->where('name', 'pricing_sent'));
    }

    public function scopeApproved($query)
    {
        return $query->whereHas('status', fn($q) => $q->where('name', 'approved'));
    }

    public function scopeConfirmed($query)
    {
        return $query->whereHas('status', fn($q) => $q->where('name', 'confirmed'));
    }

    // Helper methods
    public function canBePriced(): bool
    {
        return $this->status->name === 'pending_pricing';
    }

    public function canBeApproved(): bool
    {
        return $this->status->name === 'pricing_sent';
    }

    public function transitionTo(string $statusName, ?string $reason = null, ?User $user = null): bool
    {
        $newStatus = OrderStatus::where('name', $statusName)->firstOrFail();
        $oldStatusId = $this->status_id;

        // Update status
        $this->update(['status_id' => $newStatus->id]);

        // Log history
        OrderStatusHistory::create([
            'order_id' => $this->id,
            'from_status_id' => $oldStatusId,
            'to_status_id' => $newStatus->id,
            'reason' => $reason,
            'created_by' => $user?->id ?? auth()->id(),
        ]);

        return true;
    }
}
```

### 1.2 OrderStatus Model

**File:** `app/Models/OrderStatus.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderStatus extends Model
{
    public $timestamps = false;

    protected $fillable = ['name', 'label'];

    public function orders()
    {
        return $this->hasMany(Order::class, 'status_id');
    }
}
```

### 1.3 OrderSegment Model

**File:** `app/Models/OrderSegment.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderSegment extends Model
{
    protected $fillable = [
        'order_id',
        'airline_id',
        'departure_airport_id',
        'arrival_airport_id',
        'cabin_class_id',
        'departure_datetime',
        'arrival_datetime',
        'flight_number',
        'aircraft_type',
        'duration_minutes',
        'sequence',
        'metadata',
    ];

    protected $casts = [
        'departure_datetime' => 'datetime',
        'arrival_datetime' => 'datetime',
        'metadata' => 'json',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function airline(): BelongsTo
    {
        return $this->belongsTo(Airline::class);
    }

    public function departureAirport(): BelongsTo
    {
        return $this->belongsTo(Airport::class, 'departure_airport_id');
    }

    public function arrivalAirport(): BelongsTo
    {
        return $this->belongsTo(Airport::class, 'arrival_airport_id');
    }

    public function cabinClass(): BelongsTo
    {
        return $this->belongsTo(CabinClass::class);
    }
}
```

### 1.4 OrderPassenger Model

**File:** `app/Models/OrderPassenger.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderPassenger extends Model
{
    protected $fillable = [
        'order_id',
        'passenger_type_id',
        'title',
        'first_name',
        'last_name',
        'date_of_birth',
        'passport_number',
        'passport_expiry',
        'nationality',
        'sequence',
        'metadata',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'passport_expiry' => 'date',
        'metadata' => 'json',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function passengerType(): BelongsTo
    {
        return $this->belongsTo(PassengerType::class);
    }

    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }
}
```

### 1.5 Quote Model

**File:** `app/Models/Quote.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Quote extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'order_id',
        'status_id',
        'base_price',
        'taxes',
        'fees',
        'total_price',
        'currency_code',
        'remarks',
        'valid_until',
        'pricing_details',
        'created_by',
    ];

    protected $casts = [
        'base_price' => 'decimal:3',
        'taxes' => 'decimal:3',
        'fees' => 'decimal:3',
        'total_price' => 'decimal:3',
        'valid_until' => 'datetime',
        'pricing_details' => 'json',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(QuoteStatus::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isValid(): bool
    {
        return $this->valid_until === null || $this->valid_until->isFuture();
    }
}
```

### 1.6 OrderStatusHistory Model

**File:** `app/Models/OrderStatusHistory.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderStatusHistory extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'order_id',
        'from_status_id',
        'to_status_id',
        'reason',
        'metadata',
        'created_by',
        'created_at',
    ];

    protected $casts = [
        'metadata' => 'json',
        'created_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function fromStatus(): BelongsTo
    {
        return $this->belongsTo(OrderStatus::class, 'from_status_id');
    }

    public function toStatus(): BelongsTo
    {
        return $this->belongsTo(OrderStatus::class, 'to_status_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
```

---

## Section 2: Create Services

### 2.1 OrderService

**File:** `app/Services/OrderService.php`

```php
<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderStatus;
use App\Models\Quote;
use Illuminate\Support\Facades\DB;

class OrderService
{
    public function createOrder(array $data): Order
    {
        return DB::transaction(function () use ($data) {
            // Get pending_pricing status
            $pendingStatus = OrderStatus::where('name', 'pending_pricing')->firstOrFail();

            // Generate order number: ORD-DDMMYY-XXXX
            $orderNumber = $this->generateOrderNumber();

            // Create order
            $order = Order::create([
                'order_number' => $orderNumber,
                'agency_id' => $data['agency_id'],
                'status_id' => $pendingStatus->id,
                'total_passengers' => $data['total_passengers'],
                'departure_date' => $data['departure_date'],
                'return_date' => $data['return_date'] ?? null,
                'estimated_price' => $data['estimated_price'] ?? 0,
                'metadata' => $data['metadata'] ?? null,
                'created_by' => auth()->id(),
            ]);

            // Create segments
            foreach ($data['segments'] as $segment) {
                $order->segments()->create($segment);
            }

            // Create passengers
            foreach ($data['passengers'] as $passenger) {
                $order->passengers()->create($passenger);
            }

            // Log initial status
            $order->statusHistories()->create([
                'from_status_id' => null,
                'to_status_id' => $pendingStatus->id,
                'reason' => 'Order created',
                'created_by' => auth()->id(),
                'created_at' => now(),
            ]);

            return $order;
        });
    }

    public function submitQuote(Order $order, array $data): Quote
    {
        return DB::transaction(function () use ($order, $data) {
            // Validate order can be priced
            if (!$order->canBePriced()) {
                throw new \Exception('Order cannot be priced in current status');
            }

            // Create quote
            $quote = $order->quotes()->create([
                'status_id' => \App\Models\QuoteStatus::where('name', 'sent')->firstOrFail()->id,
                'base_price' => $data['base_price'],
                'taxes' => $data['taxes'] ?? 0,
                'fees' => $data['fees'] ?? 0,
                'total_price' => $data['total_price'],
                'currency_code' => $data['currency_code'] ?? 'USD',
                'remarks' => $data['remarks'] ?? null,
                'valid_until' => $data['valid_until'] ?? null,
                'pricing_details' => $data['pricing_details'] ?? null,
                'created_by' => auth()->id(),
            ]);

            // Transition order to pricing_sent
            $order->transitionTo('pricing_sent', 'Quote submitted by admin', auth()->user());

            return $quote;
        });
    }

    public function approveQuote(Order $order, Quote $quote, array $data): void
    {
        DB::transaction(function () use ($order, $quote, $data) {
            // Validate
            if (!$order->canBeApproved()) {
                throw new \Exception('Order cannot be approved in current status');
            }

            if ($quote->order_id !== $order->id) {
                throw new \Exception('Quote does not belong to this order');
            }

            // Check wallet credit limit
            $wallet = $order->agency->wallet;
            $newBalance = $wallet->balance - $quote->total_price;

            if ($newBalance < -$wallet->credit_limit) {
                throw new \Exception('Insufficient wallet credit. Credit limit would be exceeded.');
            }

            // Transition to approved
            $order->transitionTo('approved', 'Quote approved by agency', auth()->user());

            // Debit wallet (will happen in Phase 4 via invoice)
            // For now, just mark as approved
        });
    }

    private function generateOrderNumber(): string
    {
        $date = now()->format('dmyH');
        $random = str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        return "ORD-{$date}-{$random}";
    }
}
```

---

## Section 3: Create Controllers

### 3.1 OrderController

**File:** `app/Http/Controllers/OrderController.php`

```php
<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOrderRequest;
use App\Http\Requests\SubmitQuoteRequest;
use App\Http\Requests\ApproveOrderRequest;
use App\Models\Order;
use App\Models\Quote;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;

class OrderController extends Controller
{
    public function __construct(private OrderService $orderService) {}

    public function index(): JsonResponse
    {
        $orders = Order::with(['agency', 'status', 'segments', 'passengers', 'latestQuote'])
            ->paginate(20);

        return response()->json($orders);
    }

    public function store(StoreOrderRequest $request): JsonResponse
    {
        $this->authorize('create', Order::class);

        try {
            $order = $this->orderService->createOrder($request->validated());

            return response()->json([
                'message' => 'Order created successfully',
                'order' => $order->load(['segments', 'passengers', 'agency']),
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function show(Order $order): JsonResponse
    {
        $this->authorize('view', $order);

        return response()->json(
            $order->load(['agency', 'status', 'segments', 'passengers', 'quotes', 'statusHistories'])
        );
    }

    public function submitQuote(SubmitQuoteRequest $request, Order $order): JsonResponse
    {
        $this->authorize('price', $order);

        try {
            $quote = $this->orderService->submitQuote($order, $request->validated());

            return response()->json([
                'message' => 'Quote submitted successfully',
                'quote' => $quote,
                'order' => $order->fresh(['status']),
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function approveOrder(ApproveOrderRequest $request, Order $order): JsonResponse
    {
        $this->authorize('approve', $order);

        try {
            $quote = $order->latestQuote;
            if (!$quote) {
                throw new \Exception('No quote available for approval');
            }

            $this->orderService->approveQuote($order, $quote, $request->validated());

            return response()->json([
                'message' => 'Order approved successfully',
                'order' => $order->fresh(['status']),
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }
}
```

---

## Section 4: Create Requests

### 4.1 StoreOrderRequest

**File:** `app/Http/Requests/StoreOrderRequest.php`

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('order:create');
    }

    public function rules(): array
    {
        return [
            'agency_id' => 'required|integer|exists:travel_agencies,id',
            'departure_date' => 'required|date|after_or_equal:today',
            'return_date' => 'nullable|date|after:departure_date',
            'total_passengers' => 'required|integer|min:1|max:9',
            'estimated_price' => 'nullable|numeric|min:0',
            'segments' => 'required|array|min:1',
            'segments.*.airline_id' => 'required|integer|exists:airlines,id',
            'segments.*.departure_airport_id' => 'required|integer|exists:airports,id',
            'segments.*.arrival_airport_id' => 'required|integer|exists:airports,id',
            'segments.*.cabin_class_id' => 'required|integer|exists:cabin_classes,id',
            'segments.*.departure_datetime' => 'required|date_format:Y-m-d H:i',
            'segments.*.arrival_datetime' => 'required|date_format:Y-m-d H:i|after:segments.*.departure_datetime',
            'segments.*.sequence' => 'required|integer|min:1',
            'passengers' => 'required|array|min:1',
            'passengers.*.passenger_type_id' => 'required|integer|exists:passenger_types,id',
            'passengers.*.first_name' => 'required|string|max:100',
            'passengers.*.last_name' => 'required|string|max:100',
            'passengers.*.sequence' => 'required|integer|min:1',
            'metadata' => 'nullable|array',
        ];
    }

    public function messages(): array
    {
        return [
            'segments.required' => 'At least one flight segment is required',
            'passengers.required' => 'At least one passenger is required',
        ];
    }
}
```

### 4.2 SubmitQuoteRequest

**File:** `app/Http/Requests/SubmitQuoteRequest.php`

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubmitQuoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('order:price');
    }

    public function rules(): array
    {
        return [
            'base_price' => 'required|numeric|min:0',
            'taxes' => 'nullable|numeric|min:0',
            'fees' => 'nullable|numeric|min:0',
            'total_price' => 'required|numeric|min:0',
            'currency_code' => 'nullable|string|size:3',
            'remarks' => 'nullable|string|max:1000',
            'valid_until' => 'nullable|date_format:Y-m-d H:i|after:now',
            'pricing_details' => 'nullable|array',
        ];
    }
}
```

---

## Section 5: API Routes

**File:** `routes/api.php` (add to existing)

```php
Route::middleware('auth:sanctum')->group(function () {
    // Order routes
    Route::get('/orders', [OrderController::class, 'index'])
        ->middleware('permission:order:view');
    
    Route::post('/orders', [OrderController::class, 'store'])
        ->middleware('permission:order:create');
    
    Route::get('/orders/{order}', [OrderController::class, 'show'])
        ->middleware('permission:order:view');
    
    Route::put('/orders/{order}/quote', [OrderController::class, 'submitQuote'])
        ->middleware('permission:order:price');
    
    Route::put('/orders/{order}/approve', [OrderController::class, 'approveOrder'])
        ->middleware('permission:order:approve');
});
```

---

## Section 6: Frontend Components

### 6.1 OrderForm.vue (Agency Side)

**File:** `frontend/src/components/agency/OrderForm.vue`

```vue
<template>
  <div class="max-w-4xl mx-auto">
    <h1 class="text-3xl font-bold mb-6">Create New Order</h1>

    <form @submit.prevent="handleSubmit" class="space-y-8 bg-white p-6 rounded-lg shadow">
      <!-- Flight Details -->
      <div class="border-b pb-6">
        <h2 class="text-xl font-semibold mb-4">Flight Details</h2>
        
        <div class="grid grid-cols-2 gap-4 mb-4">
          <div>
            <label class="block text-sm font-medium mb-1">Departure Date</label>
            <input v-model="form.departure_date" type="date" class="w-full border rounded px-3 py-2" required />
          </div>
          <div>
            <label class="block text-sm font-medium mb-1">Return Date (Optional)</label>
            <input v-model="form.return_date" type="date" class="w-full border rounded px-3 py-2" />
          </div>
        </div>

        <div>
          <label class="block text-sm font-medium mb-1">Number of Passengers</label>
          <input v-model.number="form.total_passengers" type="number" min="1" max="9" class="w-full border rounded px-3 py-2" required />
        </div>
      </div>

      <!-- Flight Segments -->
      <div class="border-b pb-6">
        <div class="flex justify-between items-center mb-4">
          <h2 class="text-xl font-semibold">Flight Segments</h2>
          <button type="button" @click="addSegment" class="bg-blue-600 text-white px-4 py-2 rounded">
            + Add Segment
          </button>
        </div>

        <div v-for="(segment, idx) in form.segments" :key="idx" class="mb-6 p-4 border rounded">
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium mb-1">Airline</label>
              <select v-model="segment.airline_id" class="w-full border rounded px-3 py-2" required>
                <option value="">Select Airline</option>
                <option v-for="airline in airlines" :key="airline.id" :value="airline.id">
                  {{ airline.name }}
                </option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium mb-1">Cabin Class</label>
              <select v-model="segment.cabin_class_id" class="w-full border rounded px-3 py-2" required>
                <option value="">Select Class</option>
                <option v-for="cabin in cabins" :key="cabin.id" :value="cabin.id">
                  {{ cabin.name }}
                </option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium mb-1">From</label>
              <select v-model="segment.departure_airport_id" class="w-full border rounded px-3 py-2" required>
                <option value="">Select Airport</option>
                <option v-for="airport in airports" :key="airport.id" :value="airport.id">
                  {{ airport.code }} - {{ airport.name }}
                </option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium mb-1">To</label>
              <select v-model="segment.arrival_airport_id" class="w-full border rounded px-3 py-2" required>
                <option value="">Select Airport</option>
                <option v-for="airport in airports" :key="airport.id" :value="airport.id">
                  {{ airport.code }} - {{ airport.name }}
                </option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium mb-1">Departure Time</label>
              <input v-model="segment.departure_datetime" type="datetime-local" class="w-full border rounded px-3 py-2" required />
            </div>
            <div>
              <label class="block text-sm font-medium mb-1">Arrival Time</label>
              <input v-model="segment.arrival_datetime" type="datetime-local" class="w-full border rounded px-3 py-2" required />
            </div>
          </div>
          <button v-if="form.segments.length > 1" type="button" @click="removeSegment(idx)" class="mt-2 text-red-600 text-sm">
            Remove Segment
          </button>
        </div>
      </div>

      <!-- Passengers -->
      <div class="border-b pb-6">
        <h2 class="text-xl font-semibold mb-4">Passengers</h2>
        <div v-for="(passenger, idx) in form.passengers" :key="idx" class="mb-6 p-4 border rounded">
          <div class="grid grid-cols-3 gap-4">
            <div>
              <label class="block text-sm font-medium mb-1">Type</label>
              <select v-model="passenger.passenger_type_id" class="w-full border rounded px-3 py-2" required>
                <option v-for="type in passengerTypes" :key="type.id" :value="type.id">
                  {{ type.name }}
                </option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium mb-1">First Name</label>
              <input v-model="passenger.first_name" type="text" class="w-full border rounded px-3 py-2" required />
            </div>
            <div>
              <label class="block text-sm font-medium mb-1">Last Name</label>
              <input v-model="passenger.last_name" type="text" class="w-full border rounded px-3 py-2" required />
            </div>
          </div>
        </div>
      </div>

      <div v-if="error" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
        {{ error }}
      </div>

      <button type="submit" :disabled="loading" class="w-full bg-blue-600 text-white py-2 rounded font-semibold hover:bg-blue-700">
        {{ loading ? 'Creating...' : 'Create Order' }}
      </button>
    </form>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';
import { useAuthStore } from '@/stores/auth';

const router = useRouter();
const auth = useAuthStore();
const loading = ref(false);
const error = ref('');

const airlines = ref([]);
const cabins = ref([]);
const airports = ref([]);
const passengerTypes = ref([]);

const form = ref({
  agency_id: null,
  departure_date: '',
  return_date: '',
  total_passengers: 1,
  segments: [
    {
      airline_id: '',
      departure_airport_id: '',
      arrival_airport_id: '',
      cabin_class_id: '',
      departure_datetime: '',
      arrival_datetime: '',
      sequence: 1,
    },
  ],
  passengers: [
    {
      passenger_type_id: '',
      first_name: '',
      last_name: '',
      sequence: 1,
    },
  ],
});

const API_URL = import.meta.env.VITE_API_URL || 'http://localhost:8000/api';

onMounted(async () => {
  // Load reference data
  try {
    const [airlinesRes, cabinsRes, airportsRes, typesRes] = await Promise.all([
      axios.get(`${API_URL}/airlines`),
      axios.get(`${API_URL}/cabin-classes`),
      axios.get(`${API_URL}/airports`),
      axios.get(`${API_URL}/passenger-types`),
    ]);

    airlines.value = airlinesRes.data;
    cabins.value = cabinsRes.data;
    airports.value = airportsRes.data;
    passengerTypes.value = typesRes.data;
  } catch (err) {
    error.value = 'Failed to load reference data';
  }
});

const addSegment = () => {
  form.value.segments.push({
    airline_id: '',
    departure_airport_id: '',
    arrival_airport_id: '',
    cabin_class_id: '',
    departure_datetime: '',
    arrival_datetime: '',
    sequence: form.value.segments.length + 1,
  });
};

const removeSegment = (idx) => {
  form.value.segments.splice(idx, 1);
};

const handleSubmit = async () => {
  loading.value = true;
  error.value = '';

  // Get agency ID from auth or user selection
  form.value.agency_id = auth.user.agency_id;

  try {
    const response = await axios.post(`${API_URL}/orders`, form.value);
    
    router.push(`/agency/orders/${response.data.order.id}`);
  } catch (err) {
    error.value = err.response?.data?.error || 'Failed to create order';
  } finally {
    loading.value = false;
  }
};
</script>
```

### 6.2 OrderQueue.vue (Admin Side)

**File:** `frontend/src/components/admin/OrderQueue.vue`

```vue
<template>
  <div>
    <div class="flex justify-between items-center mb-6">
      <h1 class="text-3xl font-bold">Pending Orders</h1>
      <input
        v-model="search"
        type="text"
        placeholder="Search by order number or agency..."
        class="border rounded px-4 py-2 w-64"
      />
    </div>

    <div v-if="loading" class="text-center py-8">Loading...</div>

    <div v-if="!loading && orders.length === 0" class="text-center py-8 text-gray-500">
      No pending orders
    </div>

    <div v-if="!loading && orders.length > 0" class="space-y-4">
      <div
        v-for="order in filteredOrders"
        :key="order.id"
        class="bg-white border rounded-lg p-4 hover:shadow-md transition cursor-pointer"
        @click="selectOrder(order)"
      >
        <div class="flex justify-between items-start">
          <div>
            <h3 class="font-semibold text-lg">{{ order.order_number }}</h3>
            <p class="text-gray-600">{{ order.agency.name }}</p>
            <p class="text-sm text-gray-500">{{ order.total_passengers }} passengers • {{ order.segments.length }} legs</p>
          </div>
          <div class="text-right">
            <span class="inline-block bg-yellow-100 text-yellow-800 px-3 py-1 rounded text-sm font-semibold">
              {{ order.status.label }}
            </span>
            <p class="text-sm text-gray-500 mt-2">{{ formatDate(order.created_at) }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Detail Modal -->
    <div v-if="selectedOrder" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4">
      <div class="bg-white rounded-lg max-w-2xl w-full max-h-96 overflow-y-auto p-6">
        <div class="flex justify-between items-center mb-4">
          <h2 class="text-2xl font-bold">{{ selectedOrder.order_number }}</h2>
          <button @click="selectedOrder = null" class="text-gray-500 hover:text-gray-700">✕</button>
        </div>

        <div class="space-y-4">
          <div>
            <h3 class="font-semibold mb-2">Segments</h3>
            <div v-for="seg in selectedOrder.segments" :key="seg.id" class="text-sm text-gray-600 mb-2">
              {{ seg.departure_airport.code }} → {{ seg.arrival_airport.code }} •
              {{ seg.airline.name }} • {{ seg.cabin_class.name }}
            </div>
          </div>

          <div>
            <h3 class="font-semibold mb-2">Passengers</h3>
            <ul class="text-sm text-gray-600">
              <li v-for="p in selectedOrder.passengers" :key="p.id">
                {{ p.first_name }} {{ p.last_name }} ({{ p.passenger_type.code }})
              </li>
            </ul>
          </div>

          <button
            @click="goToPricing(selectedOrder)"
            class="w-full bg-blue-600 text-white py-2 rounded font-semibold hover:bg-blue-700"
          >
            Price & Send Offer
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';

const router = useRouter();
const orders = ref([]);
const loading = ref(true);
const search = ref('');
const selectedOrder = ref(null);

const API_URL = import.meta.env.VITE_API_URL || 'http://localhost:8000/api';

const filteredOrders = computed(() => {
  return orders.value.filter(order =>
    order.order_number.includes(search.value) ||
    order.agency.name.toLowerCase().includes(search.value.toLowerCase())
  );
});

onMounted(async () => {
  try {
    const response = await axios.get(`${API_URL}/orders?status=pending_pricing`, {
      headers: { Authorization: `Bearer ${localStorage.getItem('auth_token')}` },
    });
    orders.value = response.data.data || response.data;
  } catch (err) {
    console.error('Failed to load orders', err);
  } finally {
    loading.value = false;
  }
});

const selectOrder = (order) => {
  selectedOrder.value = order;
};

const goToPricing = (order) => {
  router.push(`/admin/orders/${order.id}/price`);
};

const formatDate = (date) => {
  return new Date(date).toLocaleDateString();
};
</script>
```

---

## Section 7: Testing

**File:** `backend/tests/Feature/Orders/CreateOrderTest.php`

```php
<?php

namespace Tests\Feature\Orders;

use App\Models\Order;
use App\Models\User;
use App\Models\TravelAgency;
use App\Models\Airline;
use App\Models\Airport;
use App\Models\CabinClass;
use App\Models\PassengerType;
use Tests\TestCase;

class CreateOrderTest extends TestCase
{
    public function test_agency_can_create_order()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('order:create');
        $agency = TravelAgency::factory()->create();
        $airline = Airline::factory()->create();
        $depAirport = Airport::factory()->create();
        $arrAirport = Airport::factory()->create();
        $cabin = CabinClass::factory()->create();
        $passengerType = PassengerType::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/orders', [
            'agency_id' => $agency->id,
            'departure_date' => now()->addDays(30)->toDateString(),
            'total_passengers' => 2,
            'segments' => [
                [
                    'airline_id' => $airline->id,
                    'departure_airport_id' => $depAirport->id,
                    'arrival_airport_id' => $arrAirport->id,
                    'cabin_class_id' => $cabin->id,
                    'departure_datetime' => now()->addDays(30)->toDateTimeString(),
                    'arrival_datetime' => now()->addDays(30)->addHours(3)->toDateTimeString(),
                    'sequence' => 1,
                ],
            ],
            'passengers' => [
                [
                    'passenger_type_id' => $passengerType->id,
                    'first_name' => 'John',
                    'last_name' => 'Doe',
                    'sequence' => 1,
                ],
            ],
        ]);

        $response->assertStatus(201);
        $response->assertHasPath('order.order_number');
        $this->assertDatabaseHas('orders', [
            'agency_id' => $agency->id,
            'total_passengers' => 2,
        ]);
    }
}
```

---

## Section 8: Verification Checklist

- [x] Order, OrderSegment, OrderPassenger models created
- [x] Quote and OrderStatus models created
- [x] OrderStatusHistory for audit trail
- [x] OrderService with core business logic
- [x] OrderController with CRUD endpoints
- [x] Request validation classes
- [x] API routes protected with permissions
- [x] OrderForm component (agency side)
- [x] OrderQueue component (admin side)
- [x] Feature tests for order creation
- [x] Authorization policies in place

**Next:** Proceed to Phase 4 for Wallet & Financial Management
