<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Department;
use App\Models\Ticket;
use App\Models\User;
use App\Notifications\TicketReplyNotification;
use App\Notifications\TicketStatusChangedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class TicketNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_is_notified_when_agent_replies(): void
    {
        Notification::fake();

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
                'body' => 'Estamos revisando tu caso.',
                'is_internal' => false,
            ])
            ->assertRedirect();

        Notification::assertSentTo($client, TicketReplyNotification::class);
        Notification::assertNotSentTo($agent, TicketReplyNotification::class);
    }

    public function test_agent_is_notified_when_client_replies(): void
    {
        Notification::fake();

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

        $this->actingAs($client)
            ->post(route('client.tickets.reply', $ticket), [
                'body' => 'Adjunto más detalles.',
            ])
            ->assertRedirect();

        Notification::assertSentTo($agent, TicketReplyNotification::class);
        Notification::assertNotSentTo($client, TicketReplyNotification::class);
    }

    public function test_client_is_notified_when_status_changes(): void
    {
        Notification::fake();

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
            ->patch(route('panel.tickets.update', $ticket), [
                'status' => 'resuelto',
                'priority' => 'normal',
                'assigned_to' => $agent->id,
            ])
            ->assertRedirect();

        Notification::assertSentTo($client, TicketStatusChangedNotification::class);
    }

    public function test_internal_notes_do_not_notify_client(): void
    {
        Notification::fake();

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
        ]);

        $this->actingAs($agent)
            ->post(route('panel.tickets.reply', $ticket), [
                'body' => 'Nota interna del equipo.',
                'is_internal' => true,
            ])
            ->assertRedirect();

        Notification::assertNothingSent();
    }
}
