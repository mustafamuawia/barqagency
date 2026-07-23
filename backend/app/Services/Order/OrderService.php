<?php

namespace App\Services\Order;

use App\DTOs\Order\CreateOrderData;
use App\DTOs\Order\SubmitQuoteData;
use App\Exceptions\InsufficientWalletBalanceException;
use App\Exceptions\InvalidOrderStatusException;
use App\Exceptions\MissingAgencyException;
use App\Exceptions\MissingQuoteException;
use App\Helpers\ReferenceNumberGenerator;
use App\Models\Order;
use App\Models\OrderStatus;
use App\Models\OrderStatusHistory;
use App\Models\QuoteStatus;
use App\Models\User;
use App\Models\WalletTransactionType;
use App\Traits\AuthorizesAgencyOwnership;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrderService
{
    use AuthorizesAgencyOwnership;

    public function listForUser(User $user): LengthAwarePaginator
    {
        $query = Order::with(['agency', 'status', 'segments', 'passengers', 'latestQuote'])
            ->orderByDesc('created_at');

        if ($user->isAgency()) {
            $query->where('agency_id', $this->agencyIdFor($user));
        }

        return $query->paginate(20);
    }

    public function findForUser(User $user, Order $order): Order
    {
        $this->assertOwnsResource($user, $order->agency_id);

        return $order->load([
            'agency',
            'status',
            'segments.airline',
            'segments.departureAirport',
            'segments.arrivalAirport',
            'segments.cabinClass',
            'passengers.passengerType',
            'quotes.status',
            'statusHistories.fromStatus',
            'statusHistories.toStatus',
        ]);
    }

    public function create(User $user, CreateOrderData $data): Order
    {
        $agencyId = $data->agencyId;
        if ($user->isAgency()) {
            $agencyId = $this->agencyIdFor($user);
        }

        if (!$agencyId) {
            throw new MissingAgencyException('No agency specified');
        }

        $status = OrderStatus::where('name', 'pending_pricing')->firstOrFail();
        $orderNumber = ReferenceNumberGenerator::make('ORD');

        return DB::transaction(function () use ($data, $agencyId, $status, $orderNumber) {
            $order = Order::create([
                'order_number' => $orderNumber,
                'agency_id' => $agencyId,
                'status_id' => $status->id,
                'total_passengers' => $data->totalPassengers,
                'departure_date' => $data->departureDate,
                'return_date' => $data->returnDate,
                'created_by' => auth()->id(),
            ]);

            foreach ($data->segments as $segment) {
                $order->segments()->create($segment);
            }

            foreach ($data->passengers as $passenger) {
                $order->passengers()->create($passenger);
            }

            OrderStatusHistory::create([
                'order_id' => $order->id,
                'from_status_id' => null,
                'to_status_id' => $status->id,
                'reason' => 'تم إنشاء الطلب',
                'created_by' => auth()->id(),
                'created_at' => now(),
            ]);

            return $order->load(['agency', 'status', 'segments', 'passengers']);
        });
    }

    /**
     * @return array{order: Order, quote: mixed}
     */
    public function submitQuote(Order $order, SubmitQuoteData $data): array
    {
        if ($order->status->name !== 'pending_pricing') {
            throw new InvalidOrderStatusException('الطلب ليس بانتظار التسعير');
        }

        return DB::transaction(function () use ($order, $data) {
            $quoteStatus = QuoteStatus::where('name', 'sent')->firstOrFail();
            $order->quotes()->create([
                'status_id' => $quoteStatus->id,
                'base_price' => $data->basePrice,
                'taxes' => $data->taxes,
                'fees' => $data->fees,
                'total_price' => $data->totalPrice,
                'remarks' => $data->remarks,
                'created_by' => auth()->id(),
            ]);

            $order->transitionTo('pricing_sent', 'تم إرسال عرض السعر من الإدارة');

            return [
                'order' => $order->fresh(['status']),
                'quote' => $order->latestQuote,
            ];
        });
    }

    public function approve(User $user, Order $order): Order
    {
        $this->assertOwnsResource($user, $order->agency_id);

        if ($order->status->name !== 'pricing_sent') {
            throw new InvalidOrderStatusException('الطلب ليس بانتظار الموافقة');
        }

        $quote = $order->latestQuote;
        if (!$quote) {
            throw new MissingQuoteException();
        }

        $wallet = $order->agency->wallet;
        $newBalance = $wallet->balance - $quote->total_price;
        if ($newBalance < -$wallet->credit_limit) {
            throw new InsufficientWalletBalanceException();
        }

        return DB::transaction(function () use ($order, $quote, $wallet, $newBalance) {
            $order->transitionTo('approved', 'تمت الموافقة على العرض من قبل الوكالة');

            $acceptedStatus = QuoteStatus::where('name', 'accepted')->firstOrFail();
            $quote->update(['status_id' => $acceptedStatus->id]);

            $debitType = WalletTransactionType::firstOrCreate(['name' => 'booking_debit'], ['label' => 'Booking Debit']);
            $wallet->transactions()->create([
                'uuid' => Str::uuid(),
                'transaction_type_id' => $debitType->id,
                'amount' => -$quote->total_price,
                'balance_after' => $newBalance,
                'reference_type' => Order::class,
                'reference_id' => $order->id,
                'description' => "Booking debit for order {$order->order_number}",
                'created_by' => auth()->id(),
                'created_at' => now(),
            ]);
            $wallet->update(['balance' => $newBalance]);

            return $order->fresh(['status']);
        });
    }
}
