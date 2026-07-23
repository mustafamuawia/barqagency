<?php

namespace App\Services\Auth;

use App\Models\User;
use App\Services\Audit\AuditLogger;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function attempt(string $email, string $password): ?array
    {
        $user = User::where('email', $email)->first();

        if (!$user || !Hash::check($password, $user->password)) {
            if ($user) {
                $this->audit->loginAttempt($user->id, false);
            }
            return null;
        }

        $this->audit->loginAttempt($user->id, true);

        return [
            'token' => $user->createToken('auth_token')->plainTextToken,
            'user'  => $user,
        ];
    }

    public function logout(User $user): void
    {
        $this->audit->log('auth.logout', $user);
        $user->currentAccessToken()->delete();
    }
}
