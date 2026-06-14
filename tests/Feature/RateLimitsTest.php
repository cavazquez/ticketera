<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Department;
use App\Models\Setting;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RateLimitsTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_is_rate_limited_by_ip(): void
    {
        Setting::current()->update(['allow_public_registration' => true]);

        for ($i = 0; $i < 3; $i++) {
            $this->post('/register', [
                'name' => "User {$i}",
                'email' => "user{$i}@example.com",
                'password' => 'password',
                'password_confirmation' => 'password',
            ])->assertRedirect();
        }

        $this->post('/register', [
            'name' => 'User blocked',
            'email' => 'blocked@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertStatus(429);
    }

    public function test_password_reset_is_rate_limited_by_ip(): void
    {
        for ($i = 0; $i < 3; $i++) {
            User::factory()->create(['email' => "user{$i}@example.com"]);
        }

        for ($i = 0; $i < 3; $i++) {
            $this->post('/forgot-password', ['email' => "user{$i}@example.com"])
                ->assertSessionHasNoErrors();
        }

        $this->post('/forgot-password', ['email' => 'blocked@example.com'])
            ->assertStatus(429);
    }

    public function test_ticket_replies_are_rate_limited_per_user(): void
    {
        $department = Department::create(['name' => 'Soporte']);
        $client = User::factory()->create(['role' => UserRole::Client]);
        $ticket = Ticket::create([
            'subject' => 'Consulta',
            'body' => 'Necesito ayuda con mi cuenta.',
            'user_id' => $client->id,
            'department_id' => $department->id,
        ]);

        for ($i = 0; $i < 30; $i++) {
            $this->actingAs($client)
                ->post(route('client.tickets.reply', $ticket), [
                    'body' => "Respuesta número {$i} con texto suficiente.",
                ])
                ->assertRedirect();
        }

        $this->actingAs($client)
            ->post(route('client.tickets.reply', $ticket), [
                'body' => 'Esta respuesta debería ser bloqueada por rate limit.',
            ])
            ->assertStatus(429);
    }

    public function test_ticket_creation_is_rate_limited_per_user(): void
    {
        $department = Department::create(['name' => 'Soporte']);
        $client = User::factory()->create(['role' => UserRole::Client]);

        for ($i = 0; $i < 10; $i++) {
            $this->actingAs($client)
                ->post(route('client.tickets.store'), [
                    'subject' => "Ticket {$i}",
                    'department_id' => $department->id,
                    'priority' => 'normal',
                    'body' => 'Descripción del problema con longitud suficiente.',
                ])
                ->assertRedirect();
        }

        $this->actingAs($client)
            ->post(route('client.tickets.store'), [
                'subject' => 'Ticket bloqueado',
                'department_id' => $department->id,
                'priority' => 'normal',
                'body' => 'Este ticket debería ser bloqueado por rate limit.',
            ])
            ->assertStatus(429);
    }
}
