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
        $byExternalId = $this->findByExternalId($provider, $externalId);

        if ($byExternalId instanceof User) {
            $byExternalId->update([
                'name' => $name,
                'email' => $email,
                'email_verified_at' => $byExternalId->email_verified_at ?? now(),
            ]);

            return $byExternalId;
        }

        $byEmail = User::query()->where('email', $email)->first();

        if ($byEmail instanceof User) {
            return $this->linkExistingUser($byEmail, $name, $provider, $externalId);
        }

        return $this->createUser($email, $name, $provider, $externalId);
    }

    private function findByExternalId(AuthProvider $provider, ?string $externalId): ?User
    {
        if ($externalId === null || $externalId === '') {
            return null;
        }

        return User::query()
            ->where('auth_provider', $provider->value)
            ->where('external_id', $externalId)
            ->first();
    }

    private function linkExistingUser(
        User $user,
        string $name,
        AuthProvider $provider,
        ?string $externalId,
    ): User {
        // Never silently take over a local password account just because the IdP
        // asserts a matching email. Linking must be done deliberately by an admin.
        if ($user->auth_provider === AuthProvider::Local) {
            throw ValidationException::withMessages([
                'email' => 'Ya existe una cuenta local con este correo. Pedile a un administrador que habilite el acceso por SSO.',
            ]);
        }

        $user->update([
            'name' => $name,
            'auth_provider' => $provider->value,
            'external_id' => $externalId ?? $user->external_id,
            'email_verified_at' => $user->email_verified_at ?? now(),
        ]);

        return $user;
    }

    private function createUser(
        string $email,
        string $name,
        AuthProvider $provider,
        ?string $externalId,
    ): User {
        if (! Setting::current()->sso_auto_provision) {
            throw ValidationException::withMessages([
                'email' => 'Tu cuenta no está autorizada para acceder al sistema.',
            ]);
        }

        return User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make(Str::random(64)),
            'role' => $this->defaultRole(),
            'auth_provider' => $provider->value,
            'external_id' => $externalId,
            'email_verified_at' => now(),
        ]);
    }

    private function defaultRole(): UserRole
    {
        $role = UserRole::tryFrom(Setting::current()->sso_default_role) ?? UserRole::Client;

        // Administrators must never be created automatically through SSO.
        return $role === UserRole::Admin ? UserRole::Client : $role;
    }
}
