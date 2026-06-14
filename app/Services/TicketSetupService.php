<?php

namespace App\Services;

use App\Enums\TicketStatus;
use App\Enums\UserRole;
use App\Models\Setting;
use App\Models\Ticket;
use App\Models\User;

class TicketSetupService
{
    public function __construct(
        private readonly TicketActivityLogger $activityLogger,
    ) {}

    public function configureNewTicket(Ticket $ticket): void
    {
        $this->autoAssign($ticket);
        $this->applySla($ticket);
    }

    public function autoAssign(Ticket $ticket): void
    {
        if (! Setting::current()->auto_assign_tickets || $ticket->assigned_to) {
            return;
        }

        $agent = User::query()
            ->where('role', UserRole::Agent)
            ->where('department_id', $ticket->department_id)
            ->withCount([
                'assignedTickets as open_tickets_count' => fn ($query) => $query->whereNotIn('status', [
                    TicketStatus::Resolved->value,
                    TicketStatus::Closed->value,
                ]),
            ])
            ->orderBy('open_tickets_count')
            ->orderBy('name')
            ->first();

        if ($agent) {
            $ticket->update(['assigned_to' => $agent->id]);
            $this->activityLogger->logChange($ticket, null, 'assigned_to', null, $agent->id);
        }
    }

    public function applySla(Ticket $ticket): void
    {
        $hours = Setting::current()->slaHoursFor($ticket->priority);

        $ticket->update([
            'due_at' => $hours ? now()->addHours($hours) : null,
            'sla_warning_sent_at' => null,
            'sla_breach_sent_at' => null,
        ]);
    }
}
