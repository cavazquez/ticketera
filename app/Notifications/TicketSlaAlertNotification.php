<?php

namespace App\Notifications;

use App\Enums\SlaAlertType;
use App\Models\Ticket;
use App\Support\TicketMailSubject;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TicketSlaAlertNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Ticket $ticket,
        public SlaAlertType $type,
    ) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        if (! $notifiable instanceof \App\Models\User) {
            throw new \InvalidArgumentException('Expected notifiable to be a user.');
        }

        $message = TicketMailSubject::applyReplyTo(
            (new MailMessage)
                ->subject(TicketMailSubject::format($this->ticket, $this->type->label()))
                ->greeting("Hola {$notifiable->name},")
        );

        if ($this->type === SlaAlertType::Warning) {
            $message->line("El ticket **{$this->ticket->number}** vence pronto según el SLA configurado.");
        } else {
            $message->line("El ticket **{$this->ticket->number}** superó el plazo del SLA.");
        }

        return $message
            ->line("Asunto: {$this->ticket->subject}")
            ->line('Departamento: '.($this->ticket->department?->name ?? '—'))
            ->line('Vencimiento: '.$this->ticket->due_at?->timezone(config('app.timezone'))->format('d/m/Y H:i'))
            ->action('Ver ticket en el panel', route('panel.tickets.show', $this->ticket))
            ->line('Ticketera');
    }
}
