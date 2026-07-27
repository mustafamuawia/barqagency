<?php

namespace App\Services\FlightSearch;

use App\Exceptions\FlightSearchUnavailableException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class IgnavClient
{
    public function oneWay(array $payload): array
    {
        return $this->post('/fares/one-way', $payload);
    }

    public function roundTrip(array $payload): array
    {
        return $this->post('/fares/round-trip', $payload);
    }

    public function airports(string $query, int $limit = 10): array
    {
        $response = Http::withHeaders(['X-Api-Key' => config('services.ignav.key')])
            ->baseUrl(config('services.ignav.base_url'))
            ->timeout(10)
            ->get('/airports', ['q' => $query, 'limit' => $limit]);

        if ($response->failed()) {
            return [];
        }

        return $response->json() ?? [];
    }

    private function post(string $path, array $payload): array
    {
        try {
            $response = Http::withHeaders(['X-Api-Key' => config('services.ignav.key')])
                ->baseUrl(config('services.ignav.base_url'))
                ->timeout(15)
                ->post($path, $payload);
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::warning('Ignav connection failed', ['path' => $path, 'error' => $e->getMessage()]);
            throw new FlightSearchUnavailableException();
        }

        if ($response->failed()) {
            Log::warning('Ignav request failed', ['path' => $path, 'status' => $response->status(), 'body' => $response->body()]);
            throw new FlightSearchUnavailableException();
        }

        return $response->json() ?? [];
    }
}
