<?php

namespace App\Notifications;

use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\User;
use App\Support\TicketMailSubject;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TicketStatusChangedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Ticket $ticket,
        public TicketStatus $previousStatus,
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

        return TicketMailSubject::applyReplyTo(
            (new MailMessage)
                ->subject(TicketMailSubject::format($this->ticket, 'Estado actualizado'))
                ->greeting("Hola {$notifiable->name},")
                ->line("El ticket **{$this->ticket->number}** cambió de estado.")
                ->line("**{$this->previousStatus->label()}** → **{$this->ticket->status->label()}**")
                ->line("Asunto: {$this->ticket->subject}")
                ->action('Ver ticket', route('client.tickets.show', $this->ticket))
                ->line('Gracias por usar Ticketera.')
        );
    }
}
