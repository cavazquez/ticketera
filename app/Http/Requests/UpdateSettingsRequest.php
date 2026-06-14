<?php

namespace App\Http\Requests;

use App\Enums\AuthDriver;
use App\Enums\UserRole;
use App\Models\Setting;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<int, ValidationRule|string>|string>
     */
    public function rules(): array
    {
        return [
            'app_name' => ['required', 'string', 'max:100'],
            'support_email' => ['nullable', 'email', 'max:255'],
            'timezone' => ['required', 'timezone:all'],
            'locale' => ['required', 'in:es,en'],
            'notify_on_reply' => ['required', 'boolean'],
            'notify_on_status_change' => ['required', 'boolean'],
            'auto_assign_tickets' => ['required', 'boolean'],
            'allow_public_registration' => ['required', 'boolean'],
            'turnstile_enabled' => ['required', 'boolean'],
            'turnstile_site_key' => ['nullable', 'string', 'max:255'],
            'turnstile_secret_key' => ['nullable', 'string', 'max:255'],
            'auth_driver' => ['required', Rule::enum(AuthDriver::class)],
            'allow_local_login' => ['required', 'boolean'],
            'sso_auto_provision' => ['required', 'boolean'],
            'sso_default_role' => ['required', Rule::in([UserRole::Client->value, UserRole::Agent->value])],
            'ldap_host' => ['nullable', 'string', 'max:255'],
            'ldap_port' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'ldap_base_dn' => ['nullable', 'string', 'max:255'],
            'ldap_use_tls' => ['required', 'boolean'],
            'ldap_username_attribute' => ['nullable', 'string', 'max:64'],
            'ldap_bind_dn' => ['nullable', 'string', 'max:255'],
            'ldap_bind_password' => ['nullable', 'string', 'max:255'],
            'keycloak_base_url' => ['nullable', 'url', 'max:255'],
            'keycloak_realm' => ['nullable', 'string', 'max:255'],
            'keycloak_client_id' => ['nullable', 'string', 'max:255'],
            'keycloak_client_secret' => ['nullable', 'string', 'max:255'],
            'sla_baja_hours' => ['nullable', 'integer', 'min:1', 'max:8760'],
            'sla_normal_hours' => ['nullable', 'integer', 'min:1', 'max:8760'],
            'sla_alta_hours' => ['nullable', 'integer', 'min:1', 'max:8760'],
            'sla_urgente_hours' => ['nullable', 'integer', 'min:1', 'max:8760'],
            'sla_warning_hours' => ['nullable', 'integer', 'min:1', 'max:168'],
            'notify_sla_warnings' => ['required', 'boolean'],
            'notify_sla_breaches' => ['required', 'boolean'],
            'inbound_email_enabled' => ['required', 'boolean'],
            'inbound_imap_host' => ['nullable', 'string', 'max:255'],
            'inbound_imap_port' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'inbound_imap_encryption' => ['nullable', 'in:ssl,tls,none'],
            'inbound_imap_username' => ['nullable', 'string', 'max:255'],
            'inbound_imap_password' => ['nullable', 'string', 'max:255'],
            'inbound_imap_folder' => ['nullable', 'string', 'max:255'],
            'inbound_default_department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'inbound_auto_create_users' => ['required', 'boolean'],
            'outbound_smtp_enabled' => ['required', 'boolean'],
            'smtp_host' => ['nullable', 'string', 'max:255'],
            'smtp_port' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'smtp_encryption' => ['nullable', 'in:ssl,tls,none'],
            'smtp_username' => ['nullable', 'string', 'max:255'],
            'smtp_password' => ['nullable', 'string', 'max:255'],
            'smtp_from_address' => ['nullable', 'email', 'max:255'],
            'smtp_from_name' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $this->validateTurnstile($validator);
            $this->validateSso($validator);
            $this->validateInboundEmail($validator);
            $this->validateOutboundSmtp($validator);
        });
    }

    private function validateOutboundSmtp(Validator $validator): void
    {
        if (! $this->boolean('outbound_smtp_enabled')) {
            return;
        }

        if (blank($this->input('smtp_host'))) {
            $validator->errors()->add('smtp_host', 'El host SMTP es obligatorio si el envío por panel está activo.');
        }

        if (blank($this->input('smtp_username'))) {
            $validator->errors()->add('smtp_username', 'El usuario SMTP es obligatorio.');
        }

        $passwordProvided = filled($this->input('smtp_password'));
        $passwordExists = filled(Setting::current()->smtp_password);

        if (! $passwordProvided && ! $passwordExists) {
            $validator->errors()->add('smtp_password', 'La contraseña SMTP es obligatoria.');
        }
    }

    private function validateInboundEmail(Validator $validator): void
    {
        if (! $this->boolean('inbound_email_enabled')) {
            return;
        }

        if (blank($this->input('inbound_imap_host'))) {
            $validator->errors()->add('inbound_imap_host', 'El host IMAP es obligatorio si el email entrante está activo.');
        }

        if (blank($this->input('inbound_imap_username'))) {
            $validator->errors()->add('inbound_imap_username', 'El usuario IMAP es obligatorio.');
        }

        $passwordProvided = filled($this->input('inbound_imap_password'));
        $passwordExists = filled(Setting::current()->inbound_imap_password);

        if (! $passwordProvided && ! $passwordExists) {
            $validator->errors()->add('inbound_imap_password', 'La contraseña IMAP es obligatoria.');
        }
    }

    private function validateTurnstile(Validator $validator): void
    {
        if (! $this->boolean('turnstile_enabled')) {
            return;
        }

        if (blank($this->input('turnstile_site_key'))) {
            $validator->errors()->add('turnstile_site_key', 'La site key es obligatoria si Turnstile está activo.');
        }

        $secretProvided = filled($this->input('turnstile_secret_key'));
        $secretExists = filled(Setting::current()->turnstile_secret_key);

        if (! $secretProvided && ! $secretExists) {
            $validator->errors()->add('turnstile_secret_key', 'La secret key es obligatoria si Turnstile está activo.');
        }
    }

    private function validateSso(Validator $validator): void
    {
        $driver = AuthDriver::tryFrom($this->input('auth_driver'));

        if ($driver === AuthDriver::Ldap) {
            if (blank($this->input('ldap_host'))) {
                $validator->errors()->add('ldap_host', 'El host LDAP es obligatorio.');
            }

            if (blank($this->input('ldap_base_dn'))) {
                $validator->errors()->add('ldap_base_dn', 'El Base DN es obligatorio.');
            }
        }

        if ($driver === AuthDriver::Keycloak) {
            if (blank($this->input('keycloak_base_url'))) {
                $validator->errors()->add('keycloak_base_url', 'La URL base de Keycloak es obligatoria.');
            }

            if (blank($this->input('keycloak_realm'))) {
                $validator->errors()->add('keycloak_realm', 'El realm de Keycloak es obligatorio.');
            }

            if (blank($this->input('keycloak_client_id'))) {
                $validator->errors()->add('keycloak_client_id', 'El client ID es obligatorio.');
            }

            $secretProvided = filled($this->input('keycloak_client_secret'));
            $secretExists = filled(Setting::current()->keycloak_client_secret);

            if (! $secretProvided && ! $secretExists) {
                $validator->errors()->add('keycloak_client_secret', 'El client secret es obligatorio.');
            }
        }
    }
}
