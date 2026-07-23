<?php

namespace App\Services\Auth;

use App\Exceptions\InvalidResetTokenException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;

class PasswordResetService
{
    public function sendResetLink(string $email): void
    {
        Password::sendResetLink(['email' => $email]);
    }

    public function reset(string $token, string $email, string $password): void
    {
        $status = Password::reset(
            ['token' => $token, 'email' => $email, 'password' => $password],
            function ($user, $password) {
                $user->forceFill(['password' => Hash::make($password)])->save();
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw new InvalidResetTokenException();
        }
    }
}
