<?php

namespace App\Notifications;

use App\Models\Ticket;
use App\Models\TicketReply;
use App\Models\User;
use App\Support\TicketMailSubject;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class TicketReplyNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Ticket $ticket,
        public TicketReply $reply,
        public User $author,
    ) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        if (! $notifiable instanceof User) {
            throw new \InvalidArgumentException('Expected notifiable to be a user.');
        }

        $url = $notifiable->isStaff()
            ? route('panel.tickets.show', $this->ticket)
            : route('client.tickets.show', $this->ticket);

        return TicketMailSubject::applyReplyTo(
            (new MailMessage)
                ->subject(TicketMailSubject::format($this->ticket, 'Nueva respuesta'))
                ->greeting("Hola {$notifiable->name},")
                ->line("{$this->author->name} respondió el ticket **{$this->ticket->number}**: {$this->ticket->subject}")
                ->line(Str::limit($this->reply->body, 300))
                ->action('Ver ticket', $url)
                ->line('Gracias por usar Ticketera.')
        );
    }
}
