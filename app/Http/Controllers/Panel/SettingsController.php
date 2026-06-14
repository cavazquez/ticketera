<?php

namespace App\Http\Controllers\Panel;

use App\Enums\AuthDriver;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateSettingsRequest;
use App\Models\Department;
use App\Models\Setting;
use App\Services\ImapConnectionTester;
use App\Services\SmtpConnectionTester;
use App\Support\LocaleManager;
use App\Support\TimezoneOptions;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', Setting::class);

        $settings = Setting::current();

        return Inertia::render('Panel/Settings/Index', [
            'settings' => [
                ...$settings->toArray(),
                'turnstile_secret_key_set' => filled($settings->turnstile_secret_key),
                'ldap_bind_password_set' => filled($settings->ldap_bind_password),
                'keycloak_client_secret_set' => filled($settings->keycloak_client_secret),
                'inbound_imap_password_set' => filled($settings->inbound_imap_password),
                'smtp_password_set' => filled($settings->smtp_password),
            ],
            'departments' => Department::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name']),
            'authDrivers' => collect(AuthDriver::cases())
                ->map(fn ($driver) => ['value' => $driver->value, 'label' => $driver->label()])
                ->values(),
            'userRoles' => collect(UserRole::cases())
                ->map(fn ($role) => ['value' => $role->value, 'label' => $role->label()])
                ->values(),
            'ssoRoles' => collect(UserRole::cases())
                ->reject(fn ($role) => $role === UserRole::Admin)
                ->map(fn ($role) => ['value' => $role->value, 'label' => $role->label()])
                ->values(),
            'keycloakCallbackUrl' => route('auth.keycloak.callback', absolute: true),
            'timezones' => TimezoneOptions::grouped(),
            'locales' => LocaleManager::labels(),
        ]);
    }

    public function update(UpdateSettingsRequest $request): RedirectResponse
    {
        $settings = Setting::current();
        $this->authorize('update', $settings);

        $data = $request->validated();

        if (blank($data['turnstile_secret_key'] ?? null)) {
            unset($data['turnstile_secret_key']);
        }

        if (blank($data['ldap_bind_password'] ?? null)) {
            unset($data['ldap_bind_password']);
        }

        if (blank($data['keycloak_client_secret'] ?? null)) {
            unset($data['keycloak_client_secret']);
        }

        if (blank($data['inbound_imap_password'] ?? null)) {
            unset($data['inbound_imap_password']);
        }

        if (blank($data['smtp_password'] ?? null)) {
            unset($data['smtp_password']);
        }

        $settings->update($data);
        $settings->refresh();

        if (filled($settings->timezone)) {
            config(['app.timezone' => $settings->timezone]);
            date_default_timezone_set($settings->timezone);
        }

        if (filled($settings->locale)) {
            LocaleManager::apply($settings->locale);
        }

        return back()->with('success', __('messages.settings_saved'));
    }

    public function testImap(ImapConnectionTester $tester): RedirectResponse
    {
        $settings = Setting::current();
        $this->authorize('update', $settings);

        try {
            $tester->test($settings);

            return back()->with('success', 'Conexión IMAP correcta.');
        } catch (\Throwable $exception) {
            return back()->with('error', $exception->getMessage());
        }
    }

    public function testSmtp(SmtpConnectionTester $tester): RedirectResponse
    {
        $settings = Setting::current();
        $this->authorize('update', $settings);

        $recipient = $this->requireUser()->email;

        try {
            $tester->test($settings, $recipient);

            return back()->with('success', "Correo de prueba enviado a {$recipient}.");
        } catch (\Throwable $exception) {
            return back()->with('error', $exception->getMessage());
        }
    }
}
