<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\TicketAttachment;
use App\Models\User;

class TicketAttachmentPolicy
{
    public function view(User $user, TicketAttachment $attachment): bool
    {
        $attachment->loadMissing('reply', 'ticket');

        $ticket = $attachment->ticket;

        if (! $user->can('view', $ticket)) {
            return false;
        }

        if ($attachment->ticket_reply_id === null) {
            return true;
        }

        $reply = $attachment->reply;

        return ! ($reply?->is_internal && $user->isClient());
    }

    public function download(User $user, TicketAttachment $attachment): bool
    {
        return $this->view($user, $attachment);
    }
}
