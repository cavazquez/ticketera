<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\TicketStatus;
use App\Enums\UserRole;
use App\Models\Department;
use App\Models\Ticket;
use App\Models\TicketReply;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class TicketRepliesAndActivityTest extends TestCase
{
    use RefreshDatabase;

    public function test_replies_are_paginated_and_default_to_latest_page(): void
    {
        $department = Department::create(['name' => 'Soporte']);
        $client = User::factory()->create(['role' => UserRole::Client]);
        $ticket = Ticket::create([
            'subject' => 'Consulta',
            'body' => 'Mensaje inicial.',
            'user_id' => $client->id,
            'department_id' => $department->id,
        ]);

        for ($i = 1; $i <= 21; $i++) {
            TicketReply::create([
                'ticket_id' => $ticket->id,
                'user_id' => $client->id,
                'body' => "Respuesta {$i}",
                'is_internal' => false,
            ]);
        }

        $this->actingAs($client)
            ->get(route('client.tickets.show', $ticket))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Client/Tickets/Show')
                ->has('replies.data', 20)
                ->where('replies.current_page', 1)
                ->where('replies.total', 21));
    }

    public function test_client_does_not_see_internal_replies_in_pagination(): void
    {
        $department = Department::create(['name' => 'Soporte']);
        $client = User::factory()->create(['role' => UserRole::Client]);
        $agent = User::factory()->create([
            'role' => UserRole::Agent,
            'department_id' => $department->id,
        ]);
        $ticket = Ticket::create([
            'subject' => 'Consulta',
            'body' => 'Mensaje inicial.',
            'user_id' => $client->id,
            'department_id' => $department->id,
        ]);

        TicketReply::create([
            'ticket_id' => $ticket->id,
            'user_id' => $agent->id,
            'body' => 'Nota interna',
            'is_internal' => true,
        ]);
        TicketReply::create([
            'ticket_id' => $ticket->id,
            'user_id' => $agent->id,
            'body' => 'Respuesta pública',
            'is_internal' => false,
        ]);

        $this->actingAs($client)
            ->get(route('client.tickets.show', $ticket))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('replies.data', 1)
                ->where('replies.data.0.body', 'Respuesta pública'));
    }

    public function test_ticket_activities_are_logged_on_update(): void
    {
        $department = Department::create(['name' => 'Soporte']);
        $client = User::factory()->create(['role' => UserRole::Client]);
        $agent = User::factory()->create([
            'role' => UserRole::Agent,
            'department_id' => $department->id,
        ]);
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $ticket = Ticket::create([
            'subject' => 'Consulta',
            'body' => 'Detalle',
            'user_id' => $client->id,
            'department_id' => $department->id,
            'status' => TicketStatus::Open,
            'priority' => 'normal',
        ]);

        $this->actingAs($admin)
            ->patch(route('panel.tickets.update', $ticket), [
                'status' => 'en_progreso',
                'priority' => 'alta',
                'assigned_to' => $agent->id,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('ticket_activities', [
            'ticket_id' => $ticket->id,
            'user_id' => $admin->id,
            'field' => 'status',
            'old_value' => 'abierto',
            'new_value' => 'en_progreso',
        ]);
        $this->assertDatabaseHas('ticket_activities', [
            'ticket_id' => $ticket->id,
            'field' => 'priority',
            'old_value' => 'normal',
            'new_value' => 'alta',
        ]);
        $this->assertDatabaseHas('ticket_activities', [
            'ticket_id' => $ticket->id,
            'field' => 'assigned_to',
            'old_value' => null,
            'new_value' => (string) $agent->id,
        ]);
    }

    public function test_staff_ticket_show_includes_activity_log(): void
    {
        $department = Department::create(['name' => 'Soporte']);
        $client = User::factory()->create(['role' => UserRole::Client]);
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $ticket = Ticket::create([
            'subject' => 'Consulta',
            'body' => 'Detalle',
            'user_id' => $client->id,
            'department_id' => $department->id,
        ]);

        $this->actingAs($admin)
            ->patch(route('panel.tickets.update', $ticket), [
                'status' => 'en_progreso',
                'priority' => 'normal',
                'assigned_to' => '',
            ]);

        $this->actingAs($admin)
            ->get(route('panel.tickets.show', $ticket))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('activities', 1)
                ->where('activities.0.field_label', 'Estado'));
    }
}
