<?php

namespace App\Services\Agency;

use App\DTOs\Agency\UpdateAgencyData;
use App\Models\TravelAgency;

class AgencyService
{
    public function list(): \Illuminate\Database\Eloquent\Collection
    {
        return TravelAgency::with(['wallet', 'agencyType'])
            ->orderBy('name')
            ->get();
    }

    public function find(TravelAgency $agency): TravelAgency
    {
        return $agency->load(['wallet', 'agencyType', 'orders']);
    }

    public function update(TravelAgency $agency, UpdateAgencyData $data): TravelAgency
    {
        $attributes = $data->toModelAttributes();
        $agency->update($attributes);

        if (array_key_exists('credit_limit', $attributes) && $agency->wallet) {
            $agency->wallet->update(['credit_limit' => $attributes['credit_limit']]);
        }

        return $agency->fresh(['wallet']);
    }
}
