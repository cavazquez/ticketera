<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Department;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SecurityFeaturesTest extends TestCase
{
    use RefreshDatabase;

    public function test_honeypot_blocks_ticket_creation(): void
    {
        $department = Department::create(['name' => 'Soporte']);
        $client = User::factory()->create(['role' => UserRole::Client]);

        $this->actingAs($client)
            ->post(route('client.tickets.store'), [
                'subject' => 'Consulta legítima',
                'department_id' => $department->id,
                'priority' => 'normal',
                'body' => 'Mensaje de prueba con suficiente longitud.',
                'company_website' => 'https://spam.test',
            ])
            ->assertSessionHasErrors('subject');
    }

    public function test_turnstile_is_required_when_enabled(): void
    {
        Setting::current()->update([
            'turnstile_enabled' => true,
            'turnstile_site_key' => 'site-key-test',
            'turnstile_secret_key' => 'secret-key-test',
        ]);

        $department = Department::create(['name' => 'Soporte']);
        $client = User::factory()->create(['role' => UserRole::Client]);

        $this->actingAs($client)
            ->post(route('client.tickets.store'), [
                'subject' => 'Consulta legítima',
                'department_id' => $department->id,
                'priority' => 'normal',
                'body' => 'Mensaje de prueba con suficiente longitud.',
            ])
            ->assertSessionHasErrors('cf_turnstile_response');
    }

    public function test_turnstile_accepts_valid_token(): void
    {
        Http::fake([
            'challenges.cloudflare.com/*' => Http::response(['success' => true]),
        ]);

        Setting::current()->update([
            'turnstile_enabled' => true,
            'turnstile_site_key' => 'site-key-test',
            'turnstile_secret_key' => 'secret-key-test',
        ]);

        $department = Department::create(['name' => 'Soporte']);
        $client = User::factory()->create(['role' => UserRole::Client]);

        $this->actingAs($client)
            ->post(route('client.tickets.store'), [
                'subject' => 'Consulta legítima',
                'department_id' => $department->id,
                'priority' => 'normal',
                'body' => 'Mensaje de prueba con suficiente longitud.',
                'cf_turnstile_response' => 'valid-token',
            ])
            ->assertRedirect();
    }

    public function test_agent_search_returns_filtered_results(): void
    {
        $department = Department::create(['name' => 'Soporte']);
        $admin = User::factory()->create([
            'role' => UserRole::Admin,
            'department_id' => $department->id,
            'name' => 'Admin Principal',
        ]);
        User::factory()->create([
            'name' => 'María López',
            'email' => 'maria@ticketera.test',
            'role' => UserRole::Agent,
            'department_id' => $department->id,
        ]);
        User::factory()->create([
            'name' => 'Carlos Díaz',
            'email' => 'carlos@ticketera.test',
            'role' => UserRole::Agent,
            'department_id' => $department->id,
        ]);

        $this->actingAs($admin)
            ->getJson(route('panel.agents.search', ['q' => 'María']))
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonFragment(['email' => 'maria@ticketera.test']);
    }

    public function test_admin_can_create_client_user(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $this->actingAs($admin)
            ->post(route('panel.agents.store'), [
                'name' => 'Cliente Nuevo',
                'email' => 'nuevo@ticketera.test',
                'password' => 'password',
                'password_confirmation' => 'password',
                'role' => UserRole::Client->value,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('users', [
            'email' => 'nuevo@ticketera.test',
            'role' => UserRole::Client->value,
        ]);
    }
}
