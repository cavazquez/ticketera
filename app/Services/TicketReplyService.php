<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\TicketReply;
use App\Models\User;
use Illuminate\Http\UploadedFile;

class TicketReplyService
{
    public function __construct(
        private readonly TicketAttachmentService $attachments,
        private readonly TicketNotifier $notifier,
        private readonly TicketActivityLogger $activityLogger,
    ) {}

    /**
     * Add a reply from a web request: persist it, store uploaded files, promote
     * the ticket status when staff answers an open ticket, and notify.
     *
     * @param  array<int, UploadedFile>  $files
     */
    public function reply(
        Ticket $ticket,
        User $author,
        string $body,
        bool $isInternal = false,
        array $files = [],
    ): TicketReply {
        $reply = $this->createReply($ticket, $author, $body, $isInternal);

        $this->attachments->storeMany($ticket, $author, $files, $reply);
        $this->promoteStatusIfNeeded($ticket, $author, $isInternal);
        $this->notifier->notifyReply($ticket, $reply, $author);

        return $reply;
    }

    public function createReply(
        Ticket $ticket,
        User $author,
        string $body,
        bool $isInternal,
    ): TicketReply {
        return $ticket->replies()->create([
            'user_id' => $author->id,
            'body' => $body,
            'is_internal' => $isInternal,
        ]);
    }

    /**
     * When a staff member publicly answers an open ticket, move it to "in progress".
     */
    public function promoteStatusIfNeeded(Ticket $ticket, User $author, bool $isInternal): void
    {
        if ($ticket->status !== TicketStatus::Open || ! $author->isStaff() || $isInternal) {
            return;
        }

        $previousStatus = $ticket->status;
        $ticket->update(['status' => TicketStatus::InProgress]);
        $this->activityLogger->logChange($ticket, $author, 'status', $previousStatus, $ticket->status);
    }

    public function notifyReply(Ticket $ticket, TicketReply $reply, User $author): void
    {
        $this->notifier->notifyReply($ticket, $reply, $author);
    }
}
