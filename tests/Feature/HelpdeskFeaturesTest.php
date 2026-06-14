<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\TicketStatus;
use App\Enums\UserRole;
use App\Models\CannedResponse;
use App\Models\Department;
use App\Models\Setting;
use App\Models\Ticket;
use App\Models\User;
use App\Notifications\TicketSlaAlertNotification;
use App\Services\TicketSetupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class HelpdeskFeaturesTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_manage_canned_responses(): void
    {
        $department = Department::create(['name' => 'Soporte']);
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $this->actingAs($admin)
            ->post(route('panel.canned-responses.store'), [
                'title' => 'Saludo inicial',
                'body' => 'Hola {cliente}, revisamos el ticket {numero}.',
                'department_id' => $department->id,
                'is_active' => true,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('canned_responses', [
            'title' => 'Saludo inicial',
            'department_id' => $department->id,
        ]);
    }

    public function test_agent_sees_department_and_global_canned_responses_on_ticket(): void
    {
        $department = Department::create(['name' => 'Soporte']);
        $otherDepartment = Department::create(['name' => 'Ventas']);
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $agent = User::factory()->create([
            'role' => UserRole::Agent,
            'department_id' => $department->id,
        ]);
        $client = User::factory()->create(['role' => UserRole::Client]);

        CannedResponse::create([
            'title' => 'Global',
            'body' => 'Macro global',
            'created_by' => $admin->id,
        ]);
        CannedResponse::create([
            'title' => 'Soporte',
            'body' => 'Macro soporte',
            'department_id' => $department->id,
            'created_by' => $admin->id,
        ]);
        CannedResponse::create([
            'title' => 'Ventas',
            'body' => 'Macro ventas',
            'department_id' => $otherDepartment->id,
            'created_by' => $admin->id,
        ]);

        $ticket = Ticket::create([
            'subject' => 'Consulta',
            'body' => 'Necesito ayuda.',
            'user_id' => $client->id,
            'department_id' => $department->id,
        ]);

        $this->actingAs($agent)
            ->get(route('panel.tickets.show', $ticket))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Panel/Tickets/Show')
                ->has('cannedResponses', 2)
                ->where('cannedResponses.0.title', 'Global')
                ->where('cannedResponses.1.title', 'Soporte'));
    }

    public function test_canned_response_replaces_placeholders(): void
    {
        $client = User::factory()->create(['role' => UserRole::Client, 'name' => 'Ana Pérez']);
        $department = Department::create(['name' => 'Soporte']);
        $ticket = Ticket::create([
            'number' => 'TKT-000099',
            'subject' => 'Acceso bloqueado',
            'body' => 'No puedo entrar.',
            'user_id' => $client->id,
            'department_id' => $department->id,
        ]);

        $canned = CannedResponse::create([
            'title' => 'Saludo',
            'body' => 'Hola {cliente}, sobre {asunto} ({numero}).',
        ]);

        $this->assertSame(
            'Hola Ana Pérez, sobre Acceso bloqueado (TKT-000099).',
            $canned->renderFor($ticket->load('user')),
        );
    }

    public function test_staff_dashboard_shows_metrics(): void
    {
        $department = Department::create(['name' => 'Soporte']);
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $client = User::factory()->create(['role' => UserRole::Client]);

        Ticket::create([
            'subject' => 'Abierto',
            'body' => 'Detalle',
            'user_id' => $client->id,
            'department_id' => $department->id,
            'status' => TicketStatus::Open,
            'due_at' => now()->subHour(),
        ]);

        Ticket::create([
            'subject' => 'Resuelto',
            'body' => 'Detalle',
            'user_id' => $client->id,
            'department_id' => $department->id,
            'status' => TicketStatus::Resolved,
        ]);

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->where('metrics.open_count', 1)
                ->where('metrics.sla_overdue_count', 1)
                ->has('metrics.by_department', 1));
    }

    public function test_warning_and_breach_alerts_are_sent_once(): void
    {
        Notification::fake();

        Setting::current()->update([
            'notify_sla_warnings' => true,
            'notify_sla_breaches' => true,
            'sla_warning_hours' => 2,
        ]);

        $department = Department::create(['name' => 'Soporte']);
        $agent = User::factory()->create([
            'role' => UserRole::Agent,
            'department_id' => $department->id,
        ]);
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $client = User::factory()->create(['role' => UserRole::Client]);

        $warningTicket = Ticket::create([
            'subject' => 'Por vencer',
            'body' => 'Detalle',
            'user_id' => $client->id,
            'department_id' => $department->id,
            'assigned_to' => $agent->id,
            'status' => TicketStatus::Open,
            'due_at' => now()->addHour(),
        ]);

        $breachTicket = Ticket::create([
            'subject' => 'Vencido',
            'body' => 'Detalle',
            'user_id' => $client->id,
            'department_id' => $department->id,
            'assigned_to' => $agent->id,
            'status' => TicketStatus::InProgress,
            'due_at' => now()->subMinutes(30),
        ]);

        Artisan::call('tickets:check-sla-alerts');

        Notification::assertSentTo($agent, TicketSlaAlertNotification::class);
        Notification::assertSentTo($admin, TicketSlaAlertNotification::class);
        Notification::assertSentTimes(TicketSlaAlertNotification::class, 4);

        $this->assertNotNull($warningTicket->fresh()->sla_warning_sent_at);
        $this->assertNotNull($breachTicket->fresh()->sla_breach_sent_at);

        Artisan::call('tickets:check-sla-alerts');

        Notification::assertSentTimes(TicketSlaAlertNotification::class, 4);
    }

    public function test_changing_priority_resets_sla_alert_flags(): void
    {
        $department = Department::create(['name' => 'Soporte']);
        $client = User::factory()->create(['role' => UserRole::Client]);

        $ticket = Ticket::create([
            'subject' => 'Ticket',
            'body' => 'Detalle',
            'user_id' => $client->id,
            'department_id' => $department->id,
            'priority' => 'normal',
            'status' => TicketStatus::Open,
            'sla_warning_sent_at' => now(),
            'sla_breach_sent_at' => now(),
        ]);

        app(TicketSetupService::class)->applySla($ticket);

        $ticket->refresh();
        $this->assertNull($ticket->sla_warning_sent_at);
        $this->assertNull($ticket->sla_breach_sent_at);
        $this->assertNotNull($ticket->due_at);
    }
}
