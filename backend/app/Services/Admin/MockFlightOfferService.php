<?php

namespace App\Services\Admin;

use App\DTOs\MockFlight\MockFlightOfferData;
use App\Http\Resources\Api\V1\Admin\MockFlightOfferResource;
use App\Models\MockFlightOffer;
use App\Services\Audit\AuditLogger;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

class MockFlightOfferService
{
    public function __construct(private readonly AuditLogger $audit)
    {
    }

    public function list(int $perPage = 20): LengthAwarePaginator
    {
        return MockFlightOffer::with(['airline', 'originAirport', 'destinationAirport', 'cabinClass'])
            ->orderByDesc('created_at')
            ->paginate($perPage)
            ->through(fn (MockFlightOffer $offer) => new MockFlightOfferResource($offer));
    }

    public function create(MockFlightOfferData $data): MockFlightOffer
    {
        $offer = MockFlightOffer::create([
            'uuid' => Str::uuid(),
            ...$data->toModelAttributes(),
            'created_by' => auth()->id(),
        ]);

        $this->audit->log('mock_flight_offer.created', $offer, [], $data->toModelAttributes());

        return $offer->fresh(['airline', 'originAirport', 'destinationAirport', 'cabinClass']);
    }

    public function update(MockFlightOffer $offer, MockFlightOfferData $data): MockFlightOffer
    {
        $attributes = $data->toModelAttributes();
        $oldValues = $offer->only(array_keys($attributes));

        $attributes['updated_by'] = auth()->id();
        $offer->update($attributes);

        $this->audit->log('mock_flight_offer.updated', $offer, $oldValues, $data->toModelAttributes());

        return $offer->fresh(['airline', 'originAirport', 'destinationAirport', 'cabinClass']);
    }

    public function delete(MockFlightOffer $offer): void
    {
        $this->audit->log('mock_flight_offer.deleted', $offer);
        $offer->delete();
    }
}
