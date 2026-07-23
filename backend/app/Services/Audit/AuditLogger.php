<?php

namespace App\Services\Audit;

use App\Models\AuditLog;
use App\Models\LoginHistory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class AuditLogger
{
    public function __construct(private readonly Request $request) {}

    public function log(
        string $event,
        ?Model $auditable = null,
        array $oldValues = [],
        array $newValues = [],
        ?int $userId = null,
    ): void {
        AuditLog::create([
            'user_id'        => $userId ?? auth()->id(),
            'event'          => $event,
            'auditable_type' => $auditable ? get_class($auditable) : null,
            'auditable_id'   => $auditable?->getKey(),
            'old_values'     => $oldValues ?: null,
            'new_values'     => $newValues ?: null,
            'ip_address'     => $this->request->ip(),
            'user_agent'     => $this->request->userAgent(),
            'created_at'     => now(),
        ]);
    }

    public function loginAttempt(int $userId, bool $success): void
    {
        LoginHistory::create([
            'user_id'    => $userId,
            'ip_address' => $this->request->ip(),
            'user_agent' => $this->request->userAgent(),
            'success'    => $success,
            'created_at' => now(),
        ]);
    }
}
