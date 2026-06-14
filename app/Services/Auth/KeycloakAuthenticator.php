<?php

namespace App\Services\Auth;

use App\Enums\AuthProvider;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Facades\Socialite;

class KeycloakAuthenticator
{
    public function __construct(
        private readonly SsoUserProvisioner $provisioner,
    ) {}

    public function isConfigured(): bool
    {
        $settings = Setting::current();

        return filled($settings->keycloak_base_url)
            && filled($settings->keycloak_realm)
            && filled($settings->keycloak_client_id)
            && filled($settings->keycloak_client_secret);
    }

    public function redirectUrl(): string
    {
        $this->configureSocialite();

        return Socialite::driver('keycloak')->redirect()->getTargetUrl();
    }

    public function authenticateCallback(): User
    {
        if (! $this->isConfigured()) {
            throw ValidationException::withMessages([
                'email' => 'Keycloak no está configurado correctamente.',
            ]);
        }

        $this->configureSocialite();

        $keycloakUser = Socialite::driver('keycloak')->user();

        $email = $keycloakUser->getEmail();

        if (blank($email)) {
            throw ValidationException::withMessages([
                'email' => 'Keycloak no devolvió un correo electrónico válido.',
            ]);
        }

        return $this->provisioner->provision(
            email: $email,
            name: $keycloakUser->getName() ?: $email,
            provider: AuthProvider::Keycloak,
            externalId: $keycloakUser->getId(),
        );
    }

    private function configureSocialite(): void
    {
        $settings = Setting::current();

        config([
            'services.keycloak' => [
                'client_id' => $settings->keycloak_client_id,
                'client_secret' => $settings->keycloak_client_secret,
                'redirect' => route('auth.keycloak.callback'),
                'base_url' => rtrim((string) $settings->keycloak_base_url, '/'),
                'realms' => $settings->keycloak_realm,
            ],
        ]);
    }
}
