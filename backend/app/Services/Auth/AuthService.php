<?php

namespace App\Services\Auth;

use App\Exceptions\EmailAlreadyVerifiedException;
use App\Exceptions\InactiveAccountException;
use App\Exceptions\InvalidCurrentPasswordException;
use App\Exceptions\InvalidVerificationTokenException;
use App\Models\User;
use App\Notifications\Admin\NewAgencyPendingNotification;
use App\Services\Audit\AuditLogger;
use App\Services\Notification\AdminNotifier;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthService
{
    public function __construct(private readonly AuditLogger $audit, private readonly AdminNotifier $adminNotifier) {}

    public function updateProfile(User $user, array $data): User
    {
        $oldValues = ['name' => $user->name, 'email' => $user->email];

        $user->update([
            'name' => $data['name'],
            'email' => $data['email'],
        ]);

        $this->audit->log('profile.updated', $user, $oldValues, ['name' => $user->name, 'email' => $user->email]);

        return $user->fresh(['userType', 'agencies']);
    }

    public function updatePassword(User $user, string $currentPassword, string $newPassword): void
    {
        if (!Hash::check($currentPassword, $user->password)) {
            throw new InvalidCurrentPasswordException();
        }

        $user->forceFill(['password' => Hash::make($newPassword)])->save();

        $this->audit->log('profile.password_changed', $user);
    }

    public function attempt(string $email, string $password): ?array
    {
        $user = User::where('email', $email)->first();

        if (!$user || !Hash::check($password, $user->password)) {
            if ($user) {
                $this->audit->loginAttempt($user->id, false);
            }
            return null;
        }

        if (!$user->is_active) {
            $this->audit->loginAttempt($user->id, false);
            throw new InactiveAccountException();
        }

        $this->audit->loginAttempt($user->id, true);

        return [
            'token' => $user->createToken('auth_token')->plainTextToken,
            'user'  => $user,
        ];
    }

    public function registerAgency(array $data): array
    {
        return \Illuminate\Support\Facades\DB::transaction(function () use ($data) {
            $userType = \App\Models\UserType::where('name', 'b2b')->first();

            $user = User::create([
                'uuid' => \Illuminate\Support\Str::uuid(),
                'name' => $data['agency_name'],
                'email' => $data['email'],
                'password' => \Illuminate\Support\Facades\Hash::make($data['password']),
                'user_type_id' => $userType?->id,
            ]);

            $user->forceFill(['email_verification_token' => Str::random(64)])->save();

            // Assign role if spatie HasRoles trait exists on model, otherwise user_type_id is set
            if (method_exists($user, 'assignRole')) {
                $user->assignRole('agency');
            }

            $agencyType = \App\Models\AgencyType::first();

            // Create Travel Agency
            $agency = \App\Models\TravelAgency::create([
                'uuid' => \Illuminate\Support\Str::uuid(),
                'name' => $data['agency_name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'address' => $data['address'] ?? null,
                'agency_type_id' => $agencyType?->id,
                'status' => 'pending',
                'created_by' => $user->id,
            ]);

            // Associate user with agency
            \App\Models\AgencyUser::create([
                'agency_id' => $agency->id,
                'user_id' => $user->id,
                'role' => 'owner',
            ]);

            // Create Wallet for agency
            \App\Models\Wallet::create([
                'uuid' => \Illuminate\Support\Str::uuid(),
                'agency_id' => $agency->id,
                'balance' => 0.000,
                'paid_balance' => 0.000,
                'unpaid_balance' => 0.000,
                'credit_limit' => 5000.000,
                'created_by' => $user->id,
            ]);

            $this->audit->log('auth.register_agency', $user, [], ['agency_id' => $agency->id]);

            $user->sendAgencyVerificationEmail();
            $this->adminNotifier->send(new NewAgencyPendingNotification($agency));

            $token = $user->createToken('auth_token')->plainTextToken;

            return [
                'token' => $token,
                'user' => $user->fresh(['userType', 'agencies']),
            ];
        });
    }

    public function verifyEmail(string $email, string $token): User
    {
        $user = User::where('email', $email)->where('email_verification_token', $token)->first();

        if (!$user) {
            throw new InvalidVerificationTokenException();
        }

        $user->forceFill(['email_verified_at' => now(), 'email_verification_token' => null])->save();

        $this->audit->log('auth.email_verified', $user);

        return $user->fresh(['userType', 'agencies']);
    }

    public function resendVerificationEmail(User $user): void
    {
        if ($user->email_verified_at) {
            throw new EmailAlreadyVerifiedException();
        }

        $user->forceFill(['email_verification_token' => Str::random(64)])->save();
        $user->sendAgencyVerificationEmail();

        $this->audit->log('auth.resend_verification', $user);
    }

    public function logout(User $user): void
    {
        $this->audit->log('auth.logout', $user);
        $user->currentAccessToken()->delete();
    }
}
