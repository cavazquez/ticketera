<?php

namespace App\Services\Auth;

use App\Enums\AuthProvider;
use App\Enums\UserRole;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SsoUserProvisioner
{
    public function provision(
        string $email,
        string $name,
        AuthProvider $provider,
        ?string $externalId = null,
    ): User {
        $existing = User::query()
            ->where(function ($query) use ($email, $externalId) {
                $query->where('email', $email);

                if ($externalId) {
                    $query->orWhere('external_id', $externalId);
                }
            })
            ->first();

        if ($existing) {
            $existing->update([
                'name' => $name,
                'auth_provider' => $provider->value,
                'external_id' => $externalId ?? $existing->external_id,
                'email_verified_at' => $existing->email_verified_at ?? now(),
            ]);

            return $existing;
        }

        if (! Setting::current()->sso_auto_provision) {
            throw ValidationException::withMessages([
                'email' => 'Tu cuenta no está autorizada para acceder al sistema.',
            ]);
        }

        $defaultRole = UserRole::tryFrom(Setting::current()->sso_default_role) ?? UserRole::Client;

        return User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make(Str::random(64)),
            'role' => $defaultRole,
            'auth_provider' => $provider->value,
            'external_id' => $externalId,
            'email_verified_at' => now(),
        ]);
    }
}
