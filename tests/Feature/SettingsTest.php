<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Department;
use App\Models\Setting;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use RefreshDatabase;

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

    public function test_admin_can_view_and_update_settings(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $this->actingAs($admin)
            ->get(route('panel.settings.index'))
            ->assertOk();

        $this->actingAs($admin)
            ->patch(route('panel.settings.update'), $this->settingsPayload())
            ->assertRedirect();

        $this->assertSame('Mi Ticketera', Setting::current()->app_name);
        $this->assertTrue(Setting::current()->auto_assign_tickets);
        $this->assertFalse(Setting::current()->notify_on_status_change);
    }

    public function test_admin_can_configure_timezone(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $this->actingAs($admin)
            ->patch(route('panel.settings.update'), $this->settingsPayload([
                'timezone' => 'America/Argentina/Buenos_Aires',
            ]))
            ->assertRedirect();

        $this->assertSame('America/Argentina/Buenos_Aires', Setting::current()->timezone);
        $this->assertSame('America/Argentina/Buenos_Aires', config('app.timezone'));
    }

    public function test_sso_default_role_cannot_be_admin(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $this->actingAs($admin)
            ->patch(route('panel.settings.update'), $this->settingsPayload([
                'sso_default_role' => 'admin',
            ]))
            ->assertSessionHasErrors('sso_default_role');
    }

    public function test_admin_can_configure_keycloak_settings(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $this->actingAs($admin)
            ->patch(route('panel.settings.update'), $this->settingsPayload([
                'auth_driver' => 'keycloak',
                'keycloak_base_url' => 'https://auth.test',
                'keycloak_realm' => 'ticketera',
                'keycloak_client_id' => 'ticketera-app',
                'keycloak_client_secret' => 'super-secret',
            ]))
            ->assertRedirect();

        $settings = Setting::current()->fresh();

        $this->assertSame('keycloak', $settings->auth_driver);
        $this->assertSame('https://auth.test', $settings->keycloak_base_url);
        $this->assertSame('super-secret', $settings->keycloak_client_secret);
    }

    public function test_agent_cannot_access_settings(): void
    {
        $department = Department::create(['name' => 'Soporte']);
        $agent = User::factory()->create([
            'role' => UserRole::Agent,
            'department_id' => $department->id,
        ]);

        $this->actingAs($agent)
            ->get(route('panel.settings.index'))
            ->assertForbidden();
    }

    public function test_auto_assign_assigns_least_busy_agent(): void
    {
        Setting::current()->update(['auto_assign_tickets' => true]);

        $department = Department::create(['name' => 'Soporte']);
        $client = User::factory()->create(['role' => UserRole::Client]);
        $busyAgent = User::factory()->create([
            'role' => UserRole::Agent,
            'department_id' => $department->id,
        ]);
        $freeAgent = User::factory()->create([
            'role' => UserRole::Agent,
            'department_id' => $department->id,
        ]);

        Ticket::create([
            'subject' => 'Ocupado',
            'body' => 'Ticket existente',
            'user_id' => $client->id,
            'department_id' => $department->id,
            'assigned_to' => $busyAgent->id,
        ]);

        $this->actingAs($client)->post(route('client.tickets.store'), [
            'subject' => 'Nuevo ticket',
            'department_id' => $department->id,
            'priority' => 'normal',
            'body' => 'Necesito ayuda.',
        ]);

        $newTicket = Ticket::query()->where('subject', 'Nuevo ticket')->first();

        $this->assertSame($freeAgent->id, $newTicket->assigned_to);
    }

    public function test_sla_is_applied_on_ticket_creation(): void
    {
        $department = Department::create(['name' => 'Soporte']);
        $client = User::factory()->create(['role' => UserRole::Client]);

        $this->actingAs($client)->post(route('client.tickets.store'), [
            'subject' => 'Con SLA',
            'department_id' => $department->id,
            'priority' => 'urgente',
            'body' => 'Necesito ayuda urgente con mi cuenta.',
        ]);

        $ticket = Ticket::query()->where('subject', 'Con SLA')->first();

        $this->assertNotNull($ticket->due_at);
    }

    public function test_notifications_respect_settings_toggle(): void
    {
        Notification::fake();

        Setting::current()->update(['notify_on_reply' => false]);

        $department = Department::create(['name' => 'Soporte']);
        $client = User::factory()->create(['role' => UserRole::Client]);
        $agent = User::factory()->create([
            'role' => UserRole::Agent,
            'department_id' => $department->id,
        ]);

        $ticket = Ticket::create([
            'subject' => 'Consulta',
            'body' => 'Necesito ayuda.',
            'user_id' => $client->id,
            'department_id' => $department->id,
            'assigned_to' => $agent->id,
        ]);

        $this->actingAs($agent)
            ->post(route('panel.tickets.reply', $ticket), [
                'body' => 'Respuesta pública.',
                'is_internal' => false,
            ])
            ->assertRedirect();

        Notification::assertNothingSent();
    }
}
