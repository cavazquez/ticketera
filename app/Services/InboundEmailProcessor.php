<?php

declare(strict_types=1);

namespace App\Services;

use App\DataTransferObjects\IncomingEmailAttachment;
use App\DataTransferObjects\IncomingEmailMessage;
use App\Enums\TicketPriority;
use App\Enums\UserRole;
use App\Models\Department;
use App\Models\ProcessedIncomingEmail;
use App\Models\Setting;
use App\Models\Ticket;
use App\Models\TicketReply;
use App\Models\User;
use App\Policies\TicketPolicy;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class InboundEmailProcessor
{
    public function __construct(
        private readonly InboundEmailParser $parser,
        private readonly TicketSetupService $ticketSetup,
        private readonly TicketAttachmentService $attachments,
        private readonly TicketReplyService $replyService,
        private readonly TicketPolicy $ticketPolicy,
    ) {}

    public function process(IncomingEmailMessage $message): ?Ticket
    {
        $messageId = $this->parser->normalizeMessageId($message->messageId);

        if ($messageId === '' || $message->isAutomated || $this->isIgnoredSender($message->fromEmail)) {
            return null;
        }

        if (ProcessedIncomingEmail::query()->where('message_id', $messageId)->exists()) {
            return null;
        }

        return DB::transaction(function () use ($message, $messageId) {
            ProcessedIncomingEmail::query()->create(['message_id' => $messageId]);

            if ($this->parser->hasInvalidSignedToken($message->subject)) {
                return null;
            }

            $ticketNumber = $this->parser->extractTicketNumber($message->subject);

            if ($ticketNumber !== null) {
                return $this->appendReply($ticketNumber, $message);
            }

            return $this->createTicket($message);
        });
    }

    private function appendReply(string $ticketNumber, IncomingEmailMessage $message): ?Ticket
    {
        $ticket = Ticket::query()->where('number', $ticketNumber)->first();

        if ($ticket === null) {
            return $this->createTicket($message);
        }

        $user = $this->resolveUser($message);
        if (! $user instanceof User || ! $this->ticketPolicy->reply($user, $ticket)) {
            return null;
        }

        $reply = $this->replyService->createReply(
            $ticket,
            $user,
            $this->normalizeBody($message->body),
            false,
        );

        $this->storeAttachments($ticket, $user, $message->attachments, $reply);
        $this->replyService->notifyReply($ticket, $reply, $user);

        return $ticket;
    }

    private function createTicket(IncomingEmailMessage $message): ?Ticket
    {
        $settings = Setting::current();

        if (! $settings->inbound_email_enabled) {
            return null;
        }

        $user = $this->resolveUser($message);
        if (! $user instanceof User) {
            return null;
        }

        $departmentId = $settings->inbound_default_department_id
            ?? Department::query()->where('is_active', true)->orderBy('id')->value('id');

        if ($departmentId === null) {
            return null;
        }

        $ticket = Ticket::create([
            'subject' => $this->parser->cleanSubject($message->subject),
            'body' => $this->normalizeBody($message->body),
            'priority' => TicketPriority::Normal,
            'user_id' => $user->id,
            'department_id' => $departmentId,
        ]);

        $this->storeAttachments($ticket, $user, $message->attachments);
        $this->ticketSetup->configureNewTicket($ticket);

        return $ticket;
    }

    private function resolveUser(IncomingEmailMessage $message): ?User
    {
        $email = Str::lower(trim($message->fromEmail));

        $existing = User::query()->where('email', $email)->first();
        if ($existing !== null) {
            return $existing;
        }

        if (! Setting::current()->inbound_auto_create_users) {
            return null;
        }

        return User::query()->create([
            'name' => $message->fromName ?: Str::before($email, '@'),
            'email' => $email,
            'password' => Str::password(32),
            'role' => UserRole::Client,
            'email_verified_at' => now(),
        ]);
    }

    /**
     * @param  array<int, IncomingEmailAttachment>  $attachments
     */
    private function storeAttachments(
        Ticket $ticket,
        User $user,
        array $attachments,
        ?TicketReply $reply = null,
    ): void {
        $stored = 0;

        foreach ($attachments as $attachment) {
            if ($stored >= TicketAttachmentService::MAX_FILES) {
                break;
            }

            $wasStored = $this->attachments->storeFromContents(
                $ticket,
                $user,
                $attachment->filename,
                $attachment->contents,
                $attachment->mimeType,
                $reply,
            );

            if ($wasStored) {
                $stored++;
            }
        }
    }

    private function normalizeBody(string $body): string
    {
        $body = trim(strip_tags($body));

        return $body !== '' ? $body : '(Sin contenido de texto)';
    }

    private function isIgnoredSender(string $email): bool
    {
        $email = Str::lower(trim($email));
        $settings = Setting::current();

        if (Str::contains($email, ['mailer-daemon', 'postmaster@'])) {
            return true;
        }

        if ($settings->inbound_imap_username && $email === Str::lower($settings->inbound_imap_username)) {
            return true;
        }

        return $settings->support_email && $email === Str::lower($settings->support_email);
    }
}
