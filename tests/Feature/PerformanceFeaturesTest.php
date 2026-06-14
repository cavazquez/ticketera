<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Setting;
use App\Models\Ticket;
use App\Models\User;
use App\Notifications\TicketReplyNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PerformanceFeaturesTest extends TestCase
{
    use RefreshDatabase;

    public function test_ticket_numbers_are_unique_and_sequential(): void
    {
        $department = Department::create(['name' => 'Soporte']);
        $client = User::factory()->create();

        $first = Ticket::create([
            'subject' => 'Primero',
            'body' => 'Contenido del primer ticket.',
            'user_id' => $client->id,
            'department_id' => $department->id,
        ]);

        $second = Ticket::create([
            'subject' => 'Segundo',
            'body' => 'Contenido del segundo ticket.',
            'user_id' => $client->id,
            'department_id' => $department->id,
        ]);

        $this->assertNotSame($first->number, $second->number);
        $this->assertSame(
            (int) substr($first->number, 4) + 1,
            (int) substr($second->number, 4),
        );
    }

    public function test_setting_cache_is_refreshed_after_update(): void
    {
        Setting::current()->update(['app_name' => 'Antes']);
        Setting::clearCache();

        $this->assertSame('Antes', Setting::current()->app_name);

        Setting::current()->update(['app_name' => 'Después']);

        $this->assertSame('Después', Setting::current()->app_name);
    }

    public function test_ticket_reply_notifications_are_queued(): void
    {
        $this->assertTrue(
            in_array(
                ShouldQueue::class,
                class_implements(TicketReplyNotification::class) ?: [],
                true,
            ),
        );
    }
}
