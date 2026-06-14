<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Department;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_can_create_and_view_ticket(): void
    {
        $department = Department::create(['name' => 'Soporte']);
        $client = User::factory()->create(['role' => UserRole::Client]);

        $response = $this->actingAs($client)->post(route('client.tickets.store'), [
            'subject' => 'Problema de acceso',
            'department_id' => $department->id,
            'priority' => 'alta',
            'body' => 'No puedo iniciar sesión desde ayer.',
        ]);

        $ticket = Ticket::first();
        $this->assertNotNull($ticket);
        $response->assertRedirect(route('client.tickets.show', $ticket));

        $this->actingAs($client)
            ->get(route('client.tickets.show', $ticket))
            ->assertOk();
    }

    public function test_agent_can_reply_and_update_ticket(): void
    {
        $department = Department::create(['name' => 'Soporte']);
        $client = User::factory()->create(['role' => UserRole::Client]);
        $agent = User::factory()->create([
            'role' => UserRole::Agent,
            'department_id' => $department->id,
        ]);

        $ticket = Ticket::create([
            'subject' => 'Consulta',
            'body' => 'Necesito ayuda con mi cuenta.',
            'user_id' => $client->id,
            'department_id' => $department->id,
        ]);

        $this->actingAs($agent)
            ->post(route('panel.tickets.reply', $ticket), [
                'body' => 'Estamos revisando tu caso.',
                'is_internal' => false,
            ])
            ->assertRedirect();

        $this->actingAs($agent)
            ->patch(route('panel.tickets.update', $ticket), [
                'status' => 'resuelto',
                'priority' => 'normal',
                'assigned_to' => $agent->id,
            ])
            ->assertRedirect();

        $ticket->refresh();
        $this->assertSame('resuelto', $ticket->status->value);
        $this->assertSame($agent->id, $ticket->assigned_to);
    }

    public function test_client_cannot_access_panel(): void
    {
        $client = User::factory()->create(['role' => UserRole::Client]);

        $this->actingAs($client)
            ->get(route('panel.tickets.index'))
            ->assertForbidden();
    }
}
