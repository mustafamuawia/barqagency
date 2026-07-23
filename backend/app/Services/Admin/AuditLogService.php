<?php

namespace App\Services\Admin;

use App\Models\AuditLog;
use App\Models\LoginHistory;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class AuditLogService
{
    public function logs(?string $event = null): LengthAwarePaginator
    {
        return AuditLog::with('user')
            ->when($event, fn ($q) => $q->where('event', $event))
            ->orderByDesc('created_at')
            ->paginate(30);
    }

    public function loginHistory(): LengthAwarePaginator
    {
        return LoginHistory::with('user')
            ->orderByDesc('created_at')
            ->paginate(30);
    }
}
