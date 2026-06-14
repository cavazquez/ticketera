<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Department;
use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Models\TicketReply;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TicketAttachmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
    }

    public function test_client_can_attach_files_when_creating_ticket(): void
    {
        $department = Department::create(['name' => 'Soporte']);
        $client = User::factory()->create(['role' => UserRole::Client]);

        $this->actingAs($client)
            ->post(route('client.tickets.store'), [
                'subject' => 'Problema con factura',
                'department_id' => $department->id,
                'priority' => 'normal',
                'body' => 'Adjunto la factura para revisión.',
                'attachments' => [
                    UploadedFile::fake()->create('factura.pdf', 100, 'application/pdf'),
                ],
            ])
            ->assertRedirect();

        $ticket = Ticket::first();
        $this->assertNotNull($ticket);
        $this->assertDatabaseHas('ticket_attachments', [
            'ticket_id' => $ticket->id,
            'original_name' => 'factura.pdf',
            'ticket_reply_id' => null,
        ]);

        $attachment = TicketAttachment::first();
        Storage::disk('local')->assertExists($attachment->path);
    }

    public function test_client_can_download_own_ticket_attachment(): void
    {
        $department = Department::create(['name' => 'Soporte']);
        $client = User::factory()->create(['role' => UserRole::Client]);
        $ticket = Ticket::create([
            'subject' => 'Consulta',
            'body' => 'Detalle',
            'user_id' => $client->id,
            'department_id' => $department->id,
        ]);

        $path = UploadedFile::fake()->create('nota.txt', 50, 'text/plain')->store("attachments/tickets/{$ticket->id}", 'local');

        $attachment = TicketAttachment::create([
            'ticket_id' => $ticket->id,
            'user_id' => $client->id,
            'disk' => 'local',
            'path' => $path,
            'original_name' => 'nota.txt',
            'mime_type' => 'text/plain',
            'size' => 51200,
        ]);

        $this->actingAs($client)
            ->get(route('attachments.download', $attachment))
            ->assertOk()
            ->assertDownload('nota.txt');
    }

    public function test_client_cannot_download_internal_reply_attachment(): void
    {
        $department = Department::create(['name' => 'Soporte']);
        $client = User::factory()->create(['role' => UserRole::Client]);
        $agent = User::factory()->create([
            'role' => UserRole::Agent,
            'department_id' => $department->id,
        ]);
        $ticket = Ticket::create([
            'subject' => 'Consulta',
            'body' => 'Detalle',
            'user_id' => $client->id,
            'department_id' => $department->id,
        ]);
        $reply = TicketReply::create([
            'ticket_id' => $ticket->id,
            'user_id' => $agent->id,
            'body' => 'Nota interna',
            'is_internal' => true,
        ]);

        $path = UploadedFile::fake()->create('interno.pdf', 50, 'application/pdf')
            ->store("attachments/tickets/{$ticket->id}", 'local');

        $attachment = TicketAttachment::create([
            'ticket_id' => $ticket->id,
            'ticket_reply_id' => $reply->id,
            'user_id' => $agent->id,
            'disk' => 'local',
            'path' => $path,
            'original_name' => 'interno.pdf',
            'mime_type' => 'application/pdf',
            'size' => 51200,
        ]);

        $this->actingAs($client)
            ->get(route('attachments.download', $attachment))
            ->assertForbidden();

        $this->actingAs($agent)
            ->get(route('attachments.download', $attachment))
            ->assertOk();
    }

    public function test_deleting_attachment_removes_file_from_storage(): void
    {
        $department = Department::create(['name' => 'Soporte']);
        $client = User::factory()->create(['role' => UserRole::Client]);
        $ticket = Ticket::create([
            'subject' => 'Consulta',
            'body' => 'Detalle',
            'user_id' => $client->id,
            'department_id' => $department->id,
        ]);

        $path = UploadedFile::fake()->create('temp.txt', 10, 'text/plain')
            ->store("attachments/tickets/{$ticket->id}", 'local');

        $attachment = TicketAttachment::create([
            'ticket_id' => $ticket->id,
            'user_id' => $client->id,
            'disk' => 'local',
            'path' => $path,
            'original_name' => 'temp.txt',
            'mime_type' => 'text/plain',
            'size' => 10240,
        ]);

        Storage::disk('local')->assertExists($path);

        $attachment->delete();

        Storage::disk('local')->assertMissing($path);
    }
}
