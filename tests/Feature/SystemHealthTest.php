<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use App\Services\SystemHealthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SystemHealthTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_system_health_page(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $this->actingAs($admin)
            ->get(route('panel.system-health.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Panel/SystemHealth/Index')
                ->has('report.summary')
                ->has('report.groups')
                ->where('report.summary.error', 0));
    }

    public function test_agent_cannot_view_system_health_page(): void
    {
        $agent = User::factory()->create(['role' => UserRole::Agent]);

        $this->actingAs($agent)
            ->get(route('panel.system-health.index'))
            ->assertForbidden();
    }

    public function test_health_report_includes_core_database_checks(): void
    {
        $report = app(SystemHealthService::class)->run();

        $checks = collect($report['groups'])
            ->flatMap(fn (array $group) => $group['checks'])
            ->keyBy('key');

        $this->assertSame('ok', $checks['connection']['status']);
        $this->assertSame('ok', $checks['migrations']['status']);
        $this->assertSame('ok', $checks['table_tickets']['status']);
        $this->assertSame('ok', $checks['index_tickets_tickets_queue_index']['status']);
    }

    public function test_health_report_flags_missing_imap_when_inbound_enabled(): void
    {
        if (extension_loaded('imap')) {
            $this->markTestSkipped('IMAP está instalado en este entorno.');
        }

        \App\Models\Setting::current()->update([
            'inbound_email_enabled' => true,
            'inbound_imap_host' => 'imap.test.local',
            'inbound_imap_username' => 'user',
            'inbound_imap_password' => 'secret',
        ]);

        $report = app(SystemHealthService::class)->run();

        $imapCheck = collect($report['groups'])
            ->flatMap(fn (array $group) => $group['checks'])
            ->firstWhere('key', 'imap_panel');

        $this->assertSame('error', $imapCheck['status']);
    }

    public function test_health_report_detects_active_queue_and_scheduler_heartbeats(): void
    {
        \Illuminate\Support\Facades\Cache::put('system_health:queue_heartbeat', now()->timestamp, 300);
        \Illuminate\Support\Facades\Cache::put('system_health:scheduler_heartbeat', (string) now()->timestamp, 300);

        $report = app(SystemHealthService::class)->run();

        $checks = collect($report['groups'])
            ->flatMap(fn (array $group) => $group['checks'])
            ->keyBy('key');

        $this->assertSame('ok', $checks['queue_worker']['status']);
        $this->assertSame('ok', $checks['scheduler']['status']);
    }
}
