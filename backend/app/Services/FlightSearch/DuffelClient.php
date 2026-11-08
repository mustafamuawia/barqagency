<?php

namespace App\Services\FlightSearch;

use App\Exceptions\DuffelBookingUncertainException;
use App\Exceptions\DuffelOfferExpiredException;
use App\Exceptions\FlightSearchUnavailableException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DuffelClient
{
    public function searchOffers(array $slices, array $passengers, string $cabinClass): array
    {
        try {
            $response = Http::withHeaders($this->headers())
                ->baseUrl($this->baseUrl())
                ->timeout(30)
                ->post('/air/offer_requests?return_offers=true', [
                    'data' => [
                        'slices' => $slices,
                        'passengers' => $passengers,
                        'cabin_class' => $cabinClass,
                    ],
                ]);
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::warning('Duffel connection failed', ['error' => $e->getMessage()]);
            throw new FlightSearchUnavailableException();
        }

        if ($response->failed()) {
            Log::warning('Duffel request failed', ['status' => $response->status(), 'body' => $response->body()]);
            throw new FlightSearchUnavailableException();
        }

        return $response->json('data.offers') ?? [];
    }

    public function bookOrder(string $offerId, array $passengers, string $currency, string $amount, ?string $idempotencyKey = null): array
    {
        $headers = $this->headers();
        if ($idempotencyKey !== null) {
            $headers['Idempotency-Key'] = $idempotencyKey;
        }

        try {
            $response = Http::withHeaders($headers)
                ->baseUrl($this->baseUrl())
                ->timeout(45)
                ->post('/air/orders', [
                    'data' => [
                        'type' => 'instant',
                        'selected_offers' => [$offerId],
                        'passengers' => $passengers,
                        'payments' => [
                            ['type' => 'balance', 'currency' => $currency, 'amount' => $amount],
                        ],
                    ],
                ]);
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::warning('Duffel book order connection failed', [
                'offer_id'         => $offerId,
                'idempotency_key'  => $idempotencyKey,
                'error'            => $e->getMessage(),
            ]);
            throw new DuffelBookingUncertainException();
        }

        if ($response->failed()) {
            $errorCode = $response->json('errors.0.code') ?? '';
            Log::warning('Duffel book order failed', ['status' => $response->status(), 'code' => $errorCode, 'body' => $response->body()]);

            $errorMessage = $response->json('errors.0.message') ?? '';
            if (in_array($errorCode, ['offer_no_longer_available', 'invalid_state_error', 'already_booked'], true)
                || str_contains($errorMessage, 'new offer request')
                || str_contains($errorMessage, 'already been booked')
                || str_contains($errorMessage, 'new search')) {
                throw new DuffelOfferExpiredException();
            }

            throw new \RuntimeException($response->json('errors.0.message') ?? 'Duffel booking failed');
        }

        return $response->json('data') ?? [];
    }

    public function getOrder(string $duffelOrderId): array
    {
        try {
            $response = Http::withHeaders($this->headers())
                ->baseUrl($this->baseUrl())
                ->timeout(15)
                ->get("/air/orders/{$duffelOrderId}");
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::warning('Duffel get order failed', ['order_id' => $duffelOrderId, 'error' => $e->getMessage()]);
            throw new FlightSearchUnavailableException();
        }

        if ($response->failed()) {
            Log::warning('Duffel get order failed', ['status' => $response->status(), 'body' => $response->body()]);
            throw new \RuntimeException($response->json('errors.0.message') ?? 'Failed to fetch Duffel order');
        }

        return $response->json('data') ?? [];
    }

    public function cancelOrder(string $duffelOrderId): array
    {
        try {
            $createResponse = Http::withHeaders($this->headers())
                ->baseUrl($this->baseUrl())
                ->timeout(30)
                ->post('/air/order_cancellations', [
                    'data' => ['order_id' => $duffelOrderId],
                ]);
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::warning('Duffel cancel order failed', ['order_id' => $duffelOrderId, 'error' => $e->getMessage()]);
            throw new FlightSearchUnavailableException();
        }

        if ($createResponse->failed()) {
            Log::warning('Duffel cancel order failed', ['status' => $createResponse->status(), 'body' => $createResponse->body()]);
            throw new \RuntimeException($createResponse->json('errors.0.message') ?? 'Duffel cancellation failed');
        }

        $cancellationId = $createResponse->json('data.id');

        try {
            $confirmResponse = Http::withHeaders($this->headers())
                ->baseUrl($this->baseUrl())
                ->timeout(30)
                ->post("/air/order_cancellations/{$cancellationId}/actions/confirm");
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::warning('Duffel cancel confirm failed', ['cancellation_id' => $cancellationId, 'error' => $e->getMessage()]);
            throw new FlightSearchUnavailableException();
        }

        if ($confirmResponse->failed()) {
            Log::warning('Duffel cancel confirm failed', ['status' => $confirmResponse->status(), 'body' => $confirmResponse->body()]);
            throw new \RuntimeException($confirmResponse->json('errors.0.message') ?? 'Duffel cancellation confirm failed');
        }

        return $confirmResponse->json('data') ?? [];
    }

    public function getAirport(string $iataCode): ?array
    {
        try {
            $response = Http::withHeaders($this->headers())
                ->baseUrl($this->baseUrl())
                ->timeout(10)
                ->get('/places/suggestions', ['query' => $iataCode]);

            if ($response->failed()) {
                return null;
            }

            $places = $response->json('data') ?? [];

            return collect($places)
                ->first(fn ($p) => strtoupper($p['iata_code'] ?? '') === strtoupper($iataCode)
                    && ($p['type'] ?? '') === 'airport');
        } catch (\Exception $e) {
            return null;
        }
    }

    private function headers(): array
    {
        return [
            'Authorization' => 'Bearer ' . config('services.duffel.key'),
            'Duffel-Version' => 'v2',
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];
    }

    private function baseUrl(): string
    {
        return config('services.duffel.base_url', 'https://api.duffel.com');
    }
}
