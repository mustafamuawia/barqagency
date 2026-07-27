<?php

namespace App\Services\Agency;

use App\DTOs\Agency\UpdateAgencyData;
use App\Exceptions\EmailNotVerifiedException;
use App\Exceptions\InvalidAgencyStatusException;
use App\Models\TravelAgency;
use App\Notifications\AgencyApprovedNotification;
use App\Notifications\AgencyRejectedNotification;
use App\Services\Audit\AuditLogger;

class AgencyService
{
    public function __construct(private readonly AuditLogger $audit)
    {
    }

    public function list(?string $status = null): \Illuminate\Database\Eloquent\Collection
    {
        return TravelAgency::with(['wallet', 'agencyType', 'users.user'])
            ->when($status, fn ($q) => $q->where('status', $status))
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

    public function approve(TravelAgency $agency): TravelAgency
    {
        if ($agency->status !== 'pending') {
            throw new InvalidAgencyStatusException();
        }

        $owner = $agency->ownerUser();
        if (!$owner || !$owner->email_verified_at) {
            throw new EmailNotVerifiedException();
        }

        $agency->update([
            'status' => 'active',
            'rejection_reason' => null,
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        $owner->notify(new AgencyApprovedNotification());

        $this->audit->log('agency.approved', $agency, ['status' => 'pending'], ['status' => 'active']);

        return $agency->fresh(['wallet', 'agencyType']);
    }

    public function reject(TravelAgency $agency, ?string $reason): TravelAgency
    {
        if ($agency->status !== 'pending') {
            throw new InvalidAgencyStatusException();
        }

        $agency->update([
            'status' => 'rejected',
            'rejection_reason' => $reason,
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        $agency->ownerUser()?->notify(new AgencyRejectedNotification($reason));

        $this->audit->log('agency.rejected', $agency, ['status' => 'pending'], ['status' => 'rejected', 'reason' => $reason]);

        return $agency->fresh(['wallet', 'agencyType']);
    }
}
