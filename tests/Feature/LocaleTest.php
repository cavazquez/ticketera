<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocaleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function settingsPayload(array $overrides = []): array
    {
        return array_merge([
            'app_name' => 'Mi Ticketera',
            'support_email' => 'help@test.com',
            'timezone' => 'UTC',
            'locale' => 'es',
            'notify_on_reply' => true,
            'notify_on_status_change' => false,
            'auto_assign_tickets' => true,
            'allow_public_registration' => false,
            'turnstile_enabled' => false,
            'turnstile_site_key' => null,
            'auth_driver' => 'local',
            'allow_local_login' => true,
            'sso_auto_provision' => true,
            'sso_default_role' => 'cliente',
            'ldap_use_tls' => false,
            'sla_baja_hours' => 48,
            'sla_normal_hours' => 24,
            'sla_alta_hours' => 8,
            'sla_urgente_hours' => 2,
            'sla_warning_hours' => 2,
            'notify_sla_warnings' => true,
            'notify_sla_breaches' => true,
            'inbound_email_enabled' => false,
            'inbound_imap_port' => 993,
            'inbound_imap_encryption' => 'ssl',
            'inbound_imap_folder' => 'INBOX',
            'inbound_auto_create_users' => true,
            'outbound_smtp_enabled' => false,
            'smtp_port' => 587,
            'smtp_encryption' => 'tls',
        ], $overrides);
    }

    public function test_user_can_switch_locale_via_session(): void
    {
        $this->post(route('locale.update'), ['locale' => 'en'])
            ->assertRedirect();

        $this->get(route('help.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('locale', 'en')
                ->where('translations', fn ($translations) => collect($translations)->get('nav.help') === 'Help / FAQ'));
    }

    public function test_admin_can_set_default_locale_in_settings(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $this->actingAs($admin)
            ->patch(route('panel.settings.update'), $this->settingsPayload([
                'locale' => 'en',
            ]))
            ->assertRedirect();

        $this->assertSame('en', Setting::current()->locale);
    }

    public function test_help_page_uses_spanish_by_default(): void
    {
        Setting::current()->update(['locale' => 'es']);

        $this->get(route('help.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('locale', 'es')
                ->where('translations', fn ($translations) => collect($translations)->get('help.title') === 'Centro de ayuda'));
    }
}
