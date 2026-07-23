<?php

namespace App\Services\Agency;

use App\DTOs\Agency\UpdateAgencyData;
use App\Models\TravelAgency;
use App\Services\Audit\AuditLogger;

class AgencyService
{
    public function __construct(private readonly AuditLogger $audit)
    {
    }

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

        // Don't duplicate encrypted PII into the audit log in cleartext — just note that it changed.
        $auditableKeys = array_diff(array_keys($attributes), ['phone']);
        $oldValues = $agency->only($auditableKeys);
        $newValues = array_intersect_key($attributes, array_flip($auditableKeys));
        if (array_key_exists('phone', $attributes)) {
            $oldValues['phone'] = '[unchanged or redacted]';
            $newValues['phone'] = '[changed, redacted]';
        }

        $agency->update($attributes);

        if (array_key_exists('credit_limit', $attributes) && $agency->wallet) {
            $agency->wallet->update(['credit_limit' => $attributes['credit_limit']]);
        }

        $this->audit->log('agency.updated', $agency, $oldValues, $newValues);

        return $agency->fresh(['wallet']);
    }
}
