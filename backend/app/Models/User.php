<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use App\Notifications\ResetPasswordNotification;
use App\Notifications\VerifyAgencyEmailNotification;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'email', 'password', 'user_type_id', 'is_active', 'created_by', 'updated_by', 'deleted_by'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasApiTokens, SoftDeletes, HasUuid;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function userType()
    {
        return $this->belongsTo(UserType::class);
    }

    public function agencyUsers()
    {
        return $this->hasMany(AgencyUser::class);
    }

    public function agencies()
    {
        return $this->belongsToMany(TravelAgency::class, 'agency_users', 'user_id', 'agency_id');
    }

    public function isSuperAdmin(): bool
    {
        return $this->userType?->name === 'super_admin';
    }

    public function isAdmin(): bool
    {
        return in_array($this->userType?->name, ['super_admin', 'admin']);
    }

    public function isAgency(): bool
    {
        return $this->userType?->name === 'b2b';
    }

    public function scopeAdmins($query)
    {
        return $query->whereHas('userType', fn ($q) => $q->whereIn('name', ['super_admin', 'admin']));
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordNotification($token));
    }

    public function sendAgencyVerificationEmail(): void
    {
        $this->notify(new VerifyAgencyEmailNotification($this->email_verification_token));
    }
}
