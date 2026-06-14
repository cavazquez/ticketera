<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\TicketStatus;
use App\Enums\UserRole;
use App\Models\Department;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketUpdateAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private function ticketIn(Department $department): Ticket
    {
        $client = User::factory()->create(['role' => UserRole::Client]);

        return Ticket::create([
            'subject' => 'Consulta',
            'body' => 'Detalle',
            'user_id' => $client->id,
            'department_id' => $department->id,
            'status' => TicketStatus::Open,
            'priority' => 'normal',
        ]);
    }

    public function test_ticket_cannot_be_assigned_to_a_client(): void
    {
        $department = Department::create(['name' => 'Soporte']);
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $client = User::factory()->create(['role' => UserRole::Client]);
        $ticket = $this->ticketIn($department);

        $this->actingAs($admin)
            ->patch(route('panel.tickets.update', $ticket), [
                'assigned_to' => $client->id,
            ])
            ->assertSessionHasErrors('assigned_to');

        $this->assertNull($ticket->fresh()->assigned_to);
    }

    public function test_agent_cannot_move_ticket_to_another_department(): void
    {
        $soporte = Department::create(['name' => 'Soporte']);
        $ventas = Department::create(['name' => 'Ventas']);
        $agent = User::factory()->create([
            'role' => UserRole::Agent,
            'department_id' => $soporte->id,
        ]);
        $ticket = $this->ticketIn($soporte);

        $this->actingAs($agent)
            ->patch(route('panel.tickets.update', $ticket), [
                'department_id' => $ventas->id,
            ])
            ->assertSessionHasErrors('department_id');

        $this->assertSame($soporte->id, $ticket->fresh()->department_id);
    }

    public function test_agent_cannot_assign_to_agent_of_another_department(): void
    {
        $soporte = Department::create(['name' => 'Soporte']);
        $ventas = Department::create(['name' => 'Ventas']);
        $agent = User::factory()->create([
            'role' => UserRole::Agent,
            'department_id' => $soporte->id,
        ]);
        $otherAgent = User::factory()->create([
            'role' => UserRole::Agent,
            'department_id' => $ventas->id,
        ]);
        $ticket = $this->ticketIn($soporte);

        $this->actingAs($agent)
            ->patch(route('panel.tickets.update', $ticket), [
                'assigned_to' => $otherAgent->id,
            ])
            ->assertSessionHasErrors('assigned_to');
    }

    public function test_agent_can_assign_to_agent_in_their_department(): void
    {
        $soporte = Department::create(['name' => 'Soporte']);
        $agent = User::factory()->create([
            'role' => UserRole::Agent,
            'department_id' => $soporte->id,
        ]);
        $peer = User::factory()->create([
            'role' => UserRole::Agent,
            'department_id' => $soporte->id,
        ]);
        $ticket = $this->ticketIn($soporte);

        $this->actingAs($agent)
            ->patch(route('panel.tickets.update', $ticket), [
                'assigned_to' => $peer->id,
            ])
            ->assertRedirect();

        $this->assertSame($peer->id, $ticket->fresh()->assigned_to);
    }

    public function test_admin_can_assign_across_departments(): void
    {
        $soporte = Department::create(['name' => 'Soporte']);
        $ventas = Department::create(['name' => 'Ventas']);
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $agent = User::factory()->create([
            'role' => UserRole::Agent,
            'department_id' => $ventas->id,
        ]);
        $ticket = $this->ticketIn($soporte);

        $this->actingAs($admin)
            ->patch(route('panel.tickets.update', $ticket), [
                'department_id' => $ventas->id,
                'assigned_to' => $agent->id,
            ])
            ->assertRedirect();

        $this->assertSame($ventas->id, $ticket->fresh()->department_id);
        $this->assertSame($agent->id, $ticket->fresh()->assigned_to);
    }
}
