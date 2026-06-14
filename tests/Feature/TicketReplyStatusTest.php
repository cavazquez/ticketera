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

class TicketReplyStatusTest extends TestCase
{
    use RefreshDatabase;

    private function openTicket(Department $department, User $client): Ticket
    {
        return Ticket::create([
            'subject' => 'Consulta',
            'body' => 'Detalle',
            'user_id' => $client->id,
            'department_id' => $department->id,
            'status' => TicketStatus::Open,
            'priority' => 'normal',
        ]);
    }

    public function test_staff_public_reply_promotes_open_ticket_to_in_progress(): void
    {
        $department = Department::create(['name' => 'Soporte']);
        $client = User::factory()->create(['role' => UserRole::Client]);
        $agent = User::factory()->create([
            'role' => UserRole::Agent,
            'department_id' => $department->id,
        ]);
        $ticket = $this->openTicket($department, $client);

        $this->actingAs($agent)
            ->post(route('panel.tickets.reply', $ticket), [
                'body' => 'Estamos viendo tu caso.',
                'is_internal' => false,
            ])
            ->assertRedirect();

        $this->assertSame(TicketStatus::InProgress, $ticket->fresh()->status);
    }

    public function test_internal_note_does_not_change_status(): void
    {
        $department = Department::create(['name' => 'Soporte']);
        $client = User::factory()->create(['role' => UserRole::Client]);
        $agent = User::factory()->create([
            'role' => UserRole::Agent,
            'department_id' => $department->id,
        ]);
        $ticket = $this->openTicket($department, $client);

        $this->actingAs($agent)
            ->post(route('panel.tickets.reply', $ticket), [
                'body' => 'Nota interna para el equipo.',
                'is_internal' => true,
            ])
            ->assertRedirect();

        $this->assertSame(TicketStatus::Open, $ticket->fresh()->status);
    }

    public function test_client_reply_does_not_change_status(): void
    {
        $department = Department::create(['name' => 'Soporte']);
        $client = User::factory()->create(['role' => UserRole::Client]);
        $ticket = $this->openTicket($department, $client);

        $this->actingAs($client)
            ->post(route('client.tickets.reply', $ticket), [
                'body' => 'Sigo esperando respuesta.',
            ])
            ->assertRedirect();

        $this->assertSame(TicketStatus::Open, $ticket->fresh()->status);
    }
}
