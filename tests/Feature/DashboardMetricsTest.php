<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Enums\UserRole;
use App\Models\Department;
use App\Models\Ticket;
use App\Models\User;
use App\Services\DashboardMetricsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardMetricsTest extends TestCase
{
    use RefreshDatabase;

    private function ticket(Department $department, User $client, TicketStatus $status, ?string $dueAt = null): void
    {
        Ticket::create([
            'subject' => 'T',
            'body' => 'B',
            'user_id' => $client->id,
            'department_id' => $department->id,
            'status' => $status,
            'priority' => TicketPriority::Normal,
            'due_at' => $dueAt,
        ]);
    }

    public function test_department_breakdown_aggregates_open_and_overdue_counts(): void
    {
        $soporte = Department::create(['name' => 'Soporte']);
        $ventas = Department::create(['name' => 'Ventas']);
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $client = User::factory()->create(['role' => UserRole::Client]);

        // Soporte: 2 abiertos (1 vencido) + 1 cerrado (no cuenta).
        $this->ticket($soporte, $client, TicketStatus::Open, now()->subDay()->toDateTimeString());
        $this->ticket($soporte, $client, TicketStatus::InProgress);
        $this->ticket($soporte, $client, TicketStatus::Closed);

        // Ventas: 1 abierto no vencido.
        $this->ticket($ventas, $client, TicketStatus::Open, now()->addDay()->toDateTimeString());

        $metrics = app(DashboardMetricsService::class)->forUser($admin);
        $byDepartment = collect($metrics['by_department'])->keyBy('name');

        $this->assertSame(2, $byDepartment['Soporte']['open_count']);
        $this->assertSame(1, $byDepartment['Soporte']['overdue_count']);
        $this->assertSame(1, $byDepartment['Ventas']['open_count']);
        $this->assertSame(0, $byDepartment['Ventas']['overdue_count']);
    }

    public function test_agent_breakdown_is_scoped_to_their_department(): void
    {
        $soporte = Department::create(['name' => 'Soporte']);
        $ventas = Department::create(['name' => 'Ventas']);
        $agent = User::factory()->create([
            'role' => UserRole::Agent,
            'department_id' => $soporte->id,
        ]);
        $client = User::factory()->create(['role' => UserRole::Client]);

        $this->ticket($soporte, $client, TicketStatus::Open);
        $this->ticket($ventas, $client, TicketStatus::Open);

        $metrics = app(DashboardMetricsService::class)->forUser($agent);

        $this->assertCount(1, $metrics['by_department']);
        $this->assertSame('Soporte', $metrics['by_department'][0]['name']);
    }
}
