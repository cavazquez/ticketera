<?php

namespace App\Services\Auth;

use App\Enums\AuthProvider;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use LDAP\Connection;
use LDAP\Result;

class LdapAuthenticator
{
    public function __construct(
        private readonly SsoUserProvisioner $provisioner,
    ) {}

    public function isConfigured(): bool
    {
        $settings = Setting::current();

        return filled($settings->ldap_host) && filled($settings->ldap_base_dn);
    }

    public function authenticate(string $email, string $password): User
    {
        if (! extension_loaded('ldap')) {
            throw ValidationException::withMessages([
                'email' => 'LDAP no está disponible en el servidor.',
            ]);
        }

        if (! $this->isConfigured()) {
            throw ValidationException::withMessages([
                'email' => 'LDAP no está configurado correctamente.',
            ]);
        }

        $settings = Setting::current();
        $connection = $this->connect($settings);

        try {
            $this->serviceBind($connection, $settings);

            $userDn = $this->findUserDn($connection, $settings, $email);

            if (! $userDn) {
                throw ValidationException::withMessages([
                    'email' => trans('auth.failed'),
                ]);
            }

            if (! @ldap_bind($connection, $userDn, $password)) {
                throw ValidationException::withMessages([
                    'email' => trans('auth.failed'),
                ]);
            }

            $name = $this->resolveDisplayName($connection, $userDn, $email);

            return $this->provisioner->provision(
                email: $email,
                name: $name,
                provider: AuthProvider::Ldap,
                externalId: $userDn,
            );
        } finally {
            ldap_unbind($connection);
        }
    }

    private function connect(Setting $settings): Connection
    {
        $uri = ($settings->ldap_use_tls ? 'ldaps://' : 'ldap://')
            .$settings->ldap_host
            .':'
            .$settings->ldap_port;

        $connection = ldap_connect($uri);

        if (! $connection instanceof Connection) {
            throw ValidationException::withMessages([
                'email' => 'No se pudo conectar al servidor LDAP.',
            ]);
        }

        ldap_set_option($connection, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($connection, LDAP_OPT_REFERRALS, 0);

        return $connection;
    }

    private function serviceBind(Connection $connection, Setting $settings): void
    {
        if (blank($settings->ldap_bind_dn)) {
            return;
        }

        if (! @ldap_bind($connection, $settings->ldap_bind_dn, $settings->ldap_bind_password ?? '')) {
            Log::warning('LDAP service bind failed.');

            throw ValidationException::withMessages([
                'email' => 'Error de autenticación con el directorio LDAP.',
            ]);
        }
    }

    private function findUserDn(Connection $connection, Setting $settings, string $email): ?string
    {
        $baseDn = $settings->ldap_base_dn;

        if (! is_string($baseDn) || $baseDn === '') {
            return null;
        }

        $attribute = $settings->ldap_username_attribute ?: 'mail';
        $filter = sprintf('(%s=%s)', $attribute, ldap_escape($email, '', LDAP_ESCAPE_FILTER));
        $search = @ldap_search($connection, $baseDn, $filter, ['dn', 'cn', 'displayName', 'mail']);

        if (! $search instanceof Result) {
            return null;
        }

        $entries = ldap_get_entries($connection, $search);

        if (($entries['count'] ?? 0) < 1) {
            return null;
        }

        return $entries[0]['dn'] ?? null;
    }

    private function resolveDisplayName(Connection $connection, string $userDn, string $fallback): string
    {
        $search = @ldap_read($connection, $userDn, '(objectClass=*)', ['cn', 'displayName', 'mail']);

        if (! $search instanceof Result) {
            return $fallback;
        }

        $entry = ldap_first_entry($connection, $search);

        if ($entry === false) {
            return $fallback;
        }

        $attributes = ldap_get_attributes($connection, $entry);

        return $attributes['displayname'][0]
            ?? $attributes['cn'][0]
            ?? $fallback;
    }
}
