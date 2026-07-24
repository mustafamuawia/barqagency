<?php

namespace App\Services\Admin;

use App\Exceptions\DomainException;
use App\Models\User;
use App\Models\UserType;
use App\Services\Audit\AuditLogger;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserManagementService
{
    /** Roles this screen is allowed to manage (internal/staff users, not B2B agency accounts). */
    private const MANAGEABLE_ROLES = ['super_admin', 'admin'];

    public function __construct(private readonly AuditLogger $audit)
    {
    }

    /** @param string|null $trashed 'with' to include soft-deleted, 'only' for just soft-deleted */
    public function list(?string $trashed = null): Collection
    {
        return User::with('userType')
            ->whereHas('userType', fn ($q) => $q->whereIn('name', self::MANAGEABLE_ROLES))
            ->when($trashed === 'with', fn ($q) => $q->withTrashed())
            ->when($trashed === 'only', fn ($q) => $q->onlyTrashed())
            ->orderByDesc('created_at')
            ->get();
    }

    public function assignableUserTypes(): Collection
    {
        return UserType::whereIn('name', self::MANAGEABLE_ROLES)->orderBy('id')->get();
    }

    public function create(array $data): User
    {
        $user = User::create([
            'uuid' => Str::uuid(),
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'user_type_id' => $data['user_type_id'],
            'is_active' => $data['is_active'] ?? true,
            'created_by' => auth()->id(),
        ]);

        $this->audit->log('user.created', $user, [], [
            'name' => $user->name,
            'email' => $user->email,
            'user_type_id' => $user->user_type_id,
        ]);

        return $user->load('userType');
    }

    public function update(User $user, array $data): User
    {
        $attributes = array_filter([
            'name' => $data['name'] ?? null,
            'email' => $data['email'] ?? null,
            'user_type_id' => $data['user_type_id'] ?? null,
        ], fn ($value) => $value !== null);

        if (array_key_exists('is_active', $data)) {
            $attributes['is_active'] = (bool) $data['is_active'];
        }

        if (!empty($data['password'])) {
            $attributes['password'] = Hash::make($data['password']);
        }

        $auditableKeys = array_diff(array_keys($attributes), ['password']);
        $oldValues = $user->only($auditableKeys);

        $attributes['updated_by'] = auth()->id();
        $user->update($attributes);

        $newValues = array_intersect_key($attributes, array_flip($auditableKeys));
        if (isset($attributes['password'])) {
            $oldValues['password'] = '[redacted]';
            $newValues['password'] = '[changed, redacted]';
        }

        $this->audit->log('user.updated', $user, $oldValues, $newValues);

        return $user->fresh('userType');
    }

    public function setActive(User $user, bool $active): User
    {
        $this->guardNotSelf($user, $active
            ? 'لا يمكنك تعديل حالة حسابك الخاص'
            : 'لا يمكنك تعطيل حسابك الخاص');

        $user->update([
            'is_active' => $active,
            'updated_by' => auth()->id(),
        ]);

        $this->audit->log($active ? 'user.activated' : 'user.deactivated', $user);

        return $user->fresh('userType');
    }

    public function softDelete(User $user): void
    {
        $this->guardNotSelf($user, 'لا يمكنك حذف حسابك الخاص');

        $user->update(['deleted_by' => auth()->id()]);
        $user->delete();

        $this->audit->log('user.deleted', $user);
    }

    public function restore(int $id): User
    {
        $user = User::withTrashed()->findOrFail($id);

        if (!$user->trashed()) {
            return $user->load('userType');
        }

        $user->restore();
        $user->update(['deleted_by' => null, 'updated_by' => auth()->id()]);

        $this->audit->log('user.restored', $user);

        return $user->fresh('userType');
    }

    private function guardNotSelf(User $user, string $message): void
    {
        if ($user->id === auth()->id()) {
            throw new class($message) extends DomainException {
                public function __construct(string $message)
                {
                    parent::__construct($message, 422);
                }
            };
        }
    }
}
