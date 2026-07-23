<?php

namespace App\Traits;

use App\Models\User;

trait AuthorizesAgencyOwnership
{
    protected function agencyIdFor(User $user): ?int
    {
        return $user->agencies()->first()?->id;
    }

    protected function assertOwnsResource(User $user, ?int $resourceAgencyId): void
    {
        if ($user->isAgency() && $resourceAgencyId !== $this->agencyIdFor($user)) {
            abort(403);
        }
    }
}
