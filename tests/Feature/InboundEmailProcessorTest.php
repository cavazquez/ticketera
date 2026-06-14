<?php

namespace Tests\Feature;

use App\DataTransferObjects\IncomingEmailAttachment;
use App\DataTransferObjects\IncomingEmailMessage;
use App\Enums\UserRole;
use App\Models\Department;
use App\Models\ProcessedIncomingEmail;
use App\Models\Setting;
use App\Models\Ticket;
use App\Models\User;
use App\Services\InboundEmailProcessor;
use App\Services\TicketAttachmentService;
use App\Support\TicketReplyToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class InboundEmailProcessorTest extends TestCase
{
    use RefreshDatabase;

    private function message(array $overrides = []): IncomingEmailMessage
    {
        return new IncomingEmailMessage(
            messageId: $overrides['messageId'] ?? 'msg-'.uniqid(),
            fromEmail: $overrides['fromEmail'] ?? 'cliente@example.com',
            fromName: $overrides['fromName'] ?? 'Cliente Test',
            subject: $overrides['subject'] ?? 'Consulta por email',
            body: $overrides['body'] ?? 'Necesito ayuda con mi cuenta.',
            attachments: $overrides['attachments'] ?? [],
            isAutomated: $overrides['isAutomated'] ?? false,
        );
    }

    public function test_creates_ticket_from_new_email(): void
    {
        Notification::fake();

        Setting::current()->update(['inbound_email_enabled' => true]);

        $department = Department::create(['name' => 'Soporte']);

        $ticket = app(InboundEmailProcessor::class)->process(
            $this->message(['subject' => 'Problema de acceso'])
        );

        $this->assertNotNull($ticket);
        $this->assertSame('Problema de acceso', $ticket->subject);
        $this->assertSame($department->id, $ticket->department_id);
        $this->assertDatabaseHas('users', ['email' => 'cliente@example.com']);
    }

    public function test_appends_reply_when_subject_contains_ticket_token(): void
    {
        Notification::fake();

        Setting::current()->update(['inbound_email_enabled' => true]);

        $department = Department::create(['name' => 'Soporte']);
        $client = User::factory()->create(['email' => 'cliente@example.com']);

        $ticket = Ticket::create([
            'number' => 'TKT-000042',
            'subject' => 'Ticket original',
            'body' => 'Cuerpo inicial',
            'user_id' => $client->id,
            'department_id' => $department->id,
        ]);

        $token = TicketReplyToken::for('TKT-000042');

        $result = app(InboundEmailProcessor::class)->process(
            $this->message([
                'messageId' => 'reply-msg-1',
                'subject' => "Re: [TKT-000042-{$token}] Ticket original",
                'body' => 'Gracias, sigo con el problema.',
            ])
        );

        $this->assertNotNull($result);
        $this->assertSame($ticket->id, $result->id);
        $this->assertDatabaseHas('ticket_replies', [
            'ticket_id' => $ticket->id,
            'body' => 'Gracias, sigo con el problema.',
        ]);
    }

    public function test_skips_duplicate_message_id(): void
    {
        Setting::current()->update(['inbound_email_enabled' => true]);
        Department::create(['name' => 'Soporte']);

        ProcessedIncomingEmail::query()->create(['message_id' => 'duplicate-msg']);

        $ticket = app(InboundEmailProcessor::class)->process(
            $this->message(['messageId' => 'duplicate-msg'])
        );

        $this->assertNull($ticket);
        $this->assertSame(0, Ticket::query()->count());
    }

    public function test_skips_automated_emails(): void
    {
        Setting::current()->update(['inbound_email_enabled' => true]);
        Department::create(['name' => 'Soporte']);

        $ticket = app(InboundEmailProcessor::class)->process(
            $this->message(['isAutomated' => true])
        );

        $this->assertNull($ticket);
        $this->assertSame(0, Ticket::query()->count());
        $this->assertSame(0, ProcessedIncomingEmail::query()->count());
    }

    public function test_skips_unknown_sender_when_auto_create_is_disabled(): void
    {
        Setting::current()->update([
            'inbound_email_enabled' => true,
            'inbound_auto_create_users' => false,
        ]);

        Department::create(['name' => 'Soporte']);

        $ticket = app(InboundEmailProcessor::class)->process(
            $this->message(['fromEmail' => 'desconocido@example.com'])
        );

        $this->assertNull($ticket);
        $this->assertSame(0, Ticket::query()->count());
    }

    public function test_client_cannot_reply_to_another_clients_ticket_via_email(): void
    {
        Notification::fake();

        Setting::current()->update(['inbound_email_enabled' => true]);

        $department = Department::create(['name' => 'Soporte']);
        User::factory()->create(['email' => 'dueño@example.com']);
        User::factory()->create(['email' => 'intruso@example.com']);

        $ticket = Ticket::create([
            'number' => 'TKT-000099',
            'subject' => 'Ticket ajeno',
            'body' => 'Solo para el dueño.',
            'user_id' => User::query()->where('email', 'dueño@example.com')->value('id'),
            'department_id' => $department->id,
        ]);

        $result = app(InboundEmailProcessor::class)->process(
            $this->message([
                'messageId' => 'intrusion-msg',
                'fromEmail' => 'intruso@example.com',
                'subject' => 'Re: [TKT-000099] Ticket ajeno',
                'body' => 'Intento responder a otro ticket.',
            ])
        );

        $this->assertNull($result);
        $this->assertDatabaseMissing('ticket_replies', [
            'ticket_id' => $ticket->id,
            'body' => 'Intento responder a otro ticket.',
        ]);
    }

    public function test_agent_can_reply_to_ticket_in_their_department_via_email(): void
    {
        Notification::fake();

        Setting::current()->update(['inbound_email_enabled' => true]);

        $department = Department::create(['name' => 'Soporte']);
        $client = User::factory()->create(['email' => 'cliente@example.com']);
        User::factory()->create([
            'email' => 'agente@example.com',
            'role' => UserRole::Agent,
            'department_id' => $department->id,
        ]);

        $ticket = Ticket::create([
            'number' => 'TKT-000050',
            'subject' => 'Consulta',
            'body' => 'Necesito ayuda.',
            'user_id' => $client->id,
            'department_id' => $department->id,
        ]);

        $result = app(InboundEmailProcessor::class)->process(
            $this->message([
                'messageId' => 'agent-reply-msg',
                'fromEmail' => 'agente@example.com',
                'subject' => 'Re: [TKT-000050] Consulta',
                'body' => 'Respuesta del agente por email.',
            ])
        );

        $this->assertNotNull($result);
        $this->assertDatabaseHas('ticket_replies', [
            'ticket_id' => $ticket->id,
            'body' => 'Respuesta del agente por email.',
        ]);
    }

    public function test_agent_cannot_reply_to_ticket_in_other_department_via_email(): void
    {
        Notification::fake();

        Setting::current()->update(['inbound_email_enabled' => true]);

        $soporte = Department::create(['name' => 'Soporte']);
        $ventas = Department::create(['name' => 'Ventas']);
        $client = User::factory()->create(['email' => 'cliente@example.com']);
        User::factory()->create([
            'email' => 'agente@example.com',
            'role' => UserRole::Agent,
            'department_id' => $soporte->id,
        ]);

        $ticket = Ticket::create([
            'number' => 'TKT-000051',
            'subject' => 'Ticket de ventas',
            'body' => 'Consulta comercial.',
            'user_id' => $client->id,
            'department_id' => $ventas->id,
        ]);

        $result = app(InboundEmailProcessor::class)->process(
            $this->message([
                'messageId' => 'agent-cross-dept-msg',
                'fromEmail' => 'agente@example.com',
                'subject' => 'Re: [TKT-000051] Ticket de ventas',
                'body' => 'No debería poder responder.',
            ])
        );

        $this->assertNull($result);
        $this->assertDatabaseMissing('ticket_replies', [
            'ticket_id' => $ticket->id,
            'body' => 'No debería poder responder.',
        ]);
    }

    public function test_stores_only_allowed_inbound_attachments(): void
    {
        Notification::fake();

        Setting::current()->update(['inbound_email_enabled' => true]);
        Department::create(['name' => 'Soporte']);

        $ticket = app(InboundEmailProcessor::class)->process(
            $this->message([
                'subject' => 'Con adjuntos',
                'attachments' => [
                    new IncomingEmailAttachment('documento.pdf', 'contenido-valido', 'application/pdf'),
                    new IncomingEmailAttachment('malware.exe', 'binario-peligroso', 'application/octet-stream'),
                    new IncomingEmailAttachment('script.sh', '#!/bin/sh', 'text/x-shellscript'),
                ],
            ])
        );

        $this->assertNotNull($ticket);
        $this->assertSame(1, $ticket->attachments()->count());
        $this->assertDatabaseHas('ticket_attachments', [
            'ticket_id' => $ticket->id,
            'original_name' => 'documento.pdf',
        ]);
        $this->assertDatabaseMissing('ticket_attachments', ['original_name' => 'malware.exe']);
    }

    public function test_rejects_oversized_inbound_attachment(): void
    {
        Notification::fake();

        Setting::current()->update(['inbound_email_enabled' => true]);
        Department::create(['name' => 'Soporte']);

        $oversized = str_repeat('a', TicketAttachmentService::MAX_FILE_BYTES + 1);

        $ticket = app(InboundEmailProcessor::class)->process(
            $this->message([
                'subject' => 'Adjunto enorme',
                'attachments' => [
                    new IncomingEmailAttachment('gigante.pdf', $oversized, 'application/pdf'),
                ],
            ])
        );

        $this->assertNotNull($ticket);
        $this->assertSame(0, $ticket->attachments()->count());
    }

    public function test_caps_number_of_inbound_attachments(): void
    {
        Notification::fake();

        Setting::current()->update(['inbound_email_enabled' => true]);
        Department::create(['name' => 'Soporte']);

        $attachments = [];
        for ($i = 0; $i < TicketAttachmentService::MAX_FILES + 3; $i++) {
            $attachments[] = new IncomingEmailAttachment("doc-{$i}.pdf", "contenido-{$i}", 'application/pdf');
        }

        $ticket = app(InboundEmailProcessor::class)->process(
            $this->message([
                'subject' => 'Muchos adjuntos',
                'attachments' => $attachments,
            ])
        );

        $this->assertNotNull($ticket);
        $this->assertSame(TicketAttachmentService::MAX_FILES, $ticket->attachments()->count());
    }

    public function test_rejects_email_with_invalid_signed_token(): void
    {
        Notification::fake();

        Setting::current()->update(['inbound_email_enabled' => true]);

        $department = Department::create(['name' => 'Soporte']);
        $client = User::factory()->create(['email' => 'cliente@example.com']);

        Ticket::create([
            'number' => 'TKT-000060',
            'subject' => 'Ticket protegido',
            'body' => 'Contenido',
            'user_id' => $client->id,
            'department_id' => $department->id,
        ]);

        $result = app(InboundEmailProcessor::class)->process(
            $this->message([
                'messageId' => 'bad-token-msg',
                'fromEmail' => 'cliente@example.com',
                'subject' => 'Re: [TKT-000060-00000000] Ticket protegido',
                'body' => 'Token inválido.',
            ])
        );

        $this->assertNull($result);
        $this->assertSame(1, Ticket::query()->count());
        $this->assertDatabaseMissing('ticket_replies', ['body' => 'Token inválido.']);
    }
}
