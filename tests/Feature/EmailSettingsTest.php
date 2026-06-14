<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Department;
use App\Models\Setting;
use App\Models\Ticket;
use App\Models\User;
use App\Services\ImapConnectionTester;
use App\Services\MailConfigurator;
use App\Services\SmtpConnectionTester;
use App\Support\TicketMailSubject;
use App\Support\TicketReplyToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmailSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_ticket_mail_subject_includes_signed_token(): void
    {
        $department = Department::create(['name' => 'Soporte']);
        $client = User::factory()->create(['role' => UserRole::Client]);

        $ticket = Ticket::create([
            'subject' => 'Consulta',
            'body' => 'Necesito ayuda.',
            'user_id' => $client->id,
            'department_id' => $department->id,
        ]);

        $subject = TicketMailSubject::format($ticket, 'Nueva respuesta');
        $token = TicketReplyToken::for($ticket->number);

        $this->assertStringContainsString("[{$ticket->number}-{$token}]", $subject);
    }

    public function test_mail_configurator_applies_panel_smtp_settings(): void
    {
        Setting::current()->update([
            'outbound_smtp_enabled' => true,
            'smtp_host' => 'smtp.test.local',
            'smtp_port' => 2525,
            'smtp_encryption' => 'tls',
            'smtp_username' => 'mailer',
            'smtp_password' => 'secret',
            'smtp_from_address' => 'help@test.local',
            'smtp_from_name' => 'Ticketera Test',
        ]);

        MailConfigurator::apply();

        $this->assertSame('smtp', config('mail.default'));
        $this->assertSame('smtp.test.local', config('mail.mailers.smtp.host'));
        $this->assertSame('mailer', config('mail.mailers.smtp.username'));
        $this->assertSame('help@test.local', config('mail.from.address'));
        $this->assertSame('Ticketera Test', config('mail.from.name'));
    }

    public function test_admin_can_test_imap_connection(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $this->mock(ImapConnectionTester::class, function ($mock): void {
            $mock->shouldReceive('test')->once();
        });

        $this->actingAs($admin)
            ->post(route('panel.settings.test-imap'))
            ->assertRedirect()
            ->assertSessionHas('success');
    }

    public function test_admin_can_test_smtp_connection(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::Admin,
            'email' => 'admin@test.local',
        ]);

        $this->mock(SmtpConnectionTester::class, function ($mock) use ($admin): void {
            $mock->shouldReceive('test')
                ->once()
                ->withArgs(fn ($settings, $recipient) => $recipient === $admin->email);
        });

        $this->actingAs($admin)
            ->post(route('panel.settings.test-smtp'))
            ->assertRedirect()
            ->assertSessionHas('success');
    }

    public function test_smtp_settings_require_credentials_when_enabled(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $this->actingAs($admin)
            ->patch(route('panel.settings.update'), array_merge($this->settingsPayload(), [
                'outbound_smtp_enabled' => true,
                'smtp_host' => null,
            ]))
            ->assertSessionHasErrors('smtp_host');
    }

    /**
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
}
