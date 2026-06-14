<?php

namespace App\Models;

use App\Enums\AuthDriver;
use App\Enums\TicketPriority;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    private const string CACHE_KEY = 'settings.current';

    private const int CACHE_TTL_SECONDS = 300;

    protected static ?self $instance = null;

    protected $fillable = [
        'app_name',
        'support_email',
        'timezone',
        'locale',
        'notify_on_reply',
        'notify_on_status_change',
        'auto_assign_tickets',
        'allow_public_registration',
        'turnstile_enabled',
        'turnstile_site_key',
        'turnstile_secret_key',
        'auth_driver',
        'allow_local_login',
        'sso_auto_provision',
        'sso_default_role',
        'ldap_host',
        'ldap_port',
        'ldap_base_dn',
        'ldap_use_tls',
        'ldap_username_attribute',
        'ldap_bind_dn',
        'ldap_bind_password',
        'keycloak_base_url',
        'keycloak_realm',
        'keycloak_client_id',
        'keycloak_client_secret',
        'sla_baja_hours',
        'sla_normal_hours',
        'sla_alta_hours',
        'sla_urgente_hours',
        'sla_warning_hours',
        'notify_sla_warnings',
        'notify_sla_breaches',
        'inbound_email_enabled',
        'inbound_imap_host',
        'inbound_imap_port',
        'inbound_imap_encryption',
        'inbound_imap_username',
        'inbound_imap_password',
        'inbound_imap_folder',
        'inbound_default_department_id',
        'inbound_auto_create_users',
        'outbound_smtp_enabled',
        'smtp_host',
        'smtp_port',
        'smtp_encryption',
        'smtp_username',
        'smtp_password',
        'smtp_from_address',
        'smtp_from_name',
    ];

    protected $hidden = [
        'turnstile_secret_key',
        'ldap_bind_password',
        'keycloak_client_secret',
        'inbound_imap_password',
        'smtp_password',
    ];

    #[\Override]
    protected function casts(): array
    {
        return [
            'notify_on_reply' => 'boolean',
            'notify_on_status_change' => 'boolean',
            'auto_assign_tickets' => 'boolean',
            'allow_public_registration' => 'boolean',
            'turnstile_enabled' => 'boolean',
            'turnstile_secret_key' => 'encrypted',
            'allow_local_login' => 'boolean',
            'sso_auto_provision' => 'boolean',
            'ldap_port' => 'integer',
            'ldap_use_tls' => 'boolean',
            'ldap_bind_password' => 'encrypted',
            'keycloak_client_secret' => 'encrypted',
            'sla_baja_hours' => 'integer',
            'sla_normal_hours' => 'integer',
            'sla_alta_hours' => 'integer',
            'sla_urgente_hours' => 'integer',
            'sla_warning_hours' => 'integer',
            'notify_sla_warnings' => 'boolean',
            'notify_sla_breaches' => 'boolean',
            'inbound_email_enabled' => 'boolean',
            'inbound_imap_port' => 'integer',
            'inbound_imap_password' => 'encrypted',
            'inbound_auto_create_users' => 'boolean',
            'outbound_smtp_enabled' => 'boolean',
            'smtp_port' => 'integer',
            'smtp_password' => 'encrypted',
        ];
    }

    public static function current(): self
    {
        if (static::$instance instanceof Setting) {
            return static::$instance;
        }

        $id = Cache::remember(
            self::CACHE_KEY,
            self::CACHE_TTL_SECONDS,
            fn (): int => (int) static::query()->firstOrCreate([], static::defaultAttributes())->getKey(),
        );

        $setting = static::query()->find($id);

        if ($setting === null) {
            static::clearCache();

            return static::current();
        }

        return static::$instance = $setting;
    }

    /**
     * @return array<string, mixed>
     */
    public static function defaultAttributes(): array
    {
        return [
            'app_name' => 'Ticketera',
            'support_email' => null,
            'timezone' => 'UTC',
            'locale' => 'es',
            'notify_on_reply' => true,
            'notify_on_status_change' => true,
            'auto_assign_tickets' => false,
            'allow_public_registration' => false,
            'turnstile_enabled' => false,
            'turnstile_site_key' => null,
            'turnstile_secret_key' => null,
            'auth_driver' => AuthDriver::Local->value,
            'allow_local_login' => true,
            'sso_auto_provision' => false,
            'sso_default_role' => 'cliente',
            'ldap_host' => null,
            'ldap_port' => 389,
            'ldap_base_dn' => null,
            'ldap_use_tls' => false,
            'ldap_username_attribute' => 'mail',
            'ldap_bind_dn' => null,
            'ldap_bind_password' => null,
            'keycloak_base_url' => null,
            'keycloak_realm' => null,
            'keycloak_client_id' => null,
            'keycloak_client_secret' => null,
            'sla_baja_hours' => 72,
            'sla_normal_hours' => 48,
            'sla_alta_hours' => 24,
            'sla_urgente_hours' => 4,
            'sla_warning_hours' => 2,
            'notify_sla_warnings' => true,
            'notify_sla_breaches' => true,
            'inbound_email_enabled' => false,
            'inbound_imap_host' => null,
            'inbound_imap_port' => 993,
            'inbound_imap_encryption' => 'ssl',
            'inbound_imap_username' => null,
            'inbound_imap_password' => null,
            'inbound_imap_folder' => 'INBOX',
            'inbound_default_department_id' => null,
            'inbound_auto_create_users' => true,
            'outbound_smtp_enabled' => false,
            'smtp_host' => null,
            'smtp_port' => 587,
            'smtp_encryption' => 'tls',
            'smtp_username' => null,
            'smtp_password' => null,
            'smtp_from_address' => null,
            'smtp_from_name' => null,
        ];
    }

    public static function clearCache(): void
    {
        static::$instance = null;
        Cache::forget(self::CACHE_KEY);
    }

    #[\Override]
    protected static function booted(): void
    {
        static::saved(fn () => static::clearCache());
    }

    public function slaHoursFor(TicketPriority $priority): ?int
    {
        return match ($priority) {
            TicketPriority::Low => $this->sla_baja_hours,
            TicketPriority::Normal => $this->sla_normal_hours,
            TicketPriority::High => $this->sla_alta_hours,
            TicketPriority::Urgent => $this->sla_urgente_hours,
        };
    }

    public function turnstileIsConfigured(): bool
    {
        return $this->turnstile_enabled
            && filled($this->turnstile_site_key)
            && filled($this->turnstile_secret_key);
    }

    public function authDriver(): AuthDriver
    {
        return AuthDriver::tryFrom($this->auth_driver) ?? AuthDriver::Local;
    }

    public function ldapIsConfigured(): bool
    {
        return filled($this->ldap_host) && filled($this->ldap_base_dn);
    }

    public function keycloakIsConfigured(): bool
    {
        return filled($this->keycloak_base_url)
            && filled($this->keycloak_realm)
            && filled($this->keycloak_client_id)
            && filled($this->keycloak_client_secret);
    }

    public function showsLocalLoginForm(): bool
    {
        return $this->authDriver() !== AuthDriver::Keycloak || $this->allow_local_login;
    }

    public function inboundEmailIsConfigured(): bool
    {
        return filled($this->inbound_imap_host)
            && filled($this->inbound_imap_username)
            && filled($this->inbound_imap_password);
    }

    public function smtpIsConfigured(): bool
    {
        return filled($this->smtp_host)
            && filled($this->smtp_username)
            && filled($this->smtp_password);
    }

    public function mailFromAddress(): string
    {
        return $this->smtp_from_address
            ?? $this->support_email
            ?? (string) config('mail.from.address');
    }

    public function mailFromName(): string
    {
        return $this->smtp_from_name
            ?? $this->app_name
            ?? (string) config('mail.from.name');
    }
}
