<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Setting;
use App\Models\Ticket;
use Illuminate\Notifications\Messages\MailMessage;

final class TicketMailSubject
{
    public static function format(Ticket $ticket, string $prefix): string
    {
        $token = TicketReplyToken::for($ticket->number);

        return "[{$ticket->number}-{$token}] {$prefix}: {$ticket->subject}";
    }

    public static function applyReplyTo(MailMessage $message): MailMessage
    {
        $supportEmail = Setting::current()->support_email;

        if (filled($supportEmail)) {
            $message->replyTo($supportEmail);
        }

        return $message;
    }
}
