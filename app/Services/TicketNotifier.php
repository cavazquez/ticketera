<?php

namespace App\Services;

use App\Enums\TicketStatus;
use App\Models\Setting;
use App\Models\Ticket;
use App\Models\TicketReply;
use App\Models\User;
use App\Notifications\TicketReplyNotification;
use App\Notifications\TicketStatusChangedNotification;

class TicketNotifier
{
    public function notifyReply(Ticket $ticket, TicketReply $reply, User $author): void
    {
        if ($reply->is_internal || ! Setting::current()->notify_on_reply) {
            return;
        }

        if ($author->isStaff() && $ticket->user_id !== $author->id) {
            $ticket->user->notify(new TicketReplyNotification($ticket, $reply, $author));
        }

        if ($author->isClient() && $ticket->assignee && $ticket->assignee->id !== $author->id) {
            $ticket->assignee->notify(new TicketReplyNotification($ticket, $reply, $author));
        }
    }

    public function notifyStatusChange(Ticket $ticket, TicketStatus $previousStatus, User $author): void
    {
        if (
            ! Setting::current()->notify_on_status_change
            || $ticket->status === $previousStatus
            || $ticket->user_id === $author->id
        ) {
            return;
        }

        $ticket->user->notify(new TicketStatusChangedNotification($ticket, $previousStatus, $author));
    }
}
