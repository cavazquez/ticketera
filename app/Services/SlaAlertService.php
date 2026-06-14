<?php

namespace App\Services;

use App\Enums\SlaAlertType;
use App\Enums\TicketStatus;
use App\Enums\UserRole;
use App\Models\Setting;
use App\Models\Ticket;
use App\Models\User;
use App\Notifications\TicketSlaAlertNotification;
use Illuminate\Support\Collection;

class SlaAlertService
{
    public function process(): int
    {
        $settings = Setting::current();
        $sent = 0;

        if ($settings->notify_sla_warnings && filled($settings->sla_warning_hours)) {
            $sent += $this->sendWarnings((int) $settings->sla_warning_hours);
        }

        if ($settings->notify_sla_breaches) {
            $sent += $this->sendBreaches();
        }

        return $sent;
    }

    private function sendWarnings(int $warningHours): int
    {
        $sent = 0;

        $tickets = Ticket::query()
            ->whereIn('status', [TicketStatus::Open, TicketStatus::InProgress])
            ->whereNotNull('due_at')
            ->whereNull('sla_warning_sent_at')
            ->where('due_at', '>', now())
            ->where('due_at', '<=', now()->addHours($warningHours))
            ->with(['user:id,name', 'assignee:id,name,email', 'department:id,name'])
            ->get();

        foreach ($tickets as $ticket) {
            $this->notifyRecipients($ticket, SlaAlertType::Warning);
            $ticket->update(['sla_warning_sent_at' => now()]);
            $sent++;
        }

        return $sent;
    }

    private function sendBreaches(): int
    {
        $sent = 0;

        $tickets = Ticket::query()
            ->whereIn('status', [TicketStatus::Open, TicketStatus::InProgress])
            ->whereNotNull('due_at')
            ->whereNull('sla_breach_sent_at')
            ->where('due_at', '<', now())
            ->with(['user:id,name', 'assignee:id,name,email', 'department:id,name'])
            ->get();

        foreach ($tickets as $ticket) {
            $this->notifyRecipients($ticket, SlaAlertType::Breach);
            $ticket->update(['sla_breach_sent_at' => now()]);
            $sent++;
        }

        return $sent;
    }

    private function notifyRecipients(Ticket $ticket, SlaAlertType $type): void
    {
        foreach ($this->recipientsFor($ticket) as $user) {
            $user->notify(new TicketSlaAlertNotification($ticket, $type));
        }
    }

    /** @return Collection<int, User> */
    private function recipientsFor(Ticket $ticket): Collection
    {
        if ($ticket->assignee) {
            return User::query()
                ->where(function ($query) use ($ticket) {
                    $query->where('id', $ticket->assignee->id)
                        ->orWhere('role', UserRole::Admin);
                })
                ->get();
        }

        return User::query()
            ->where(function ($query) use ($ticket) {
                $query->where('role', UserRole::Admin)
                    ->orWhere(function ($nested) use ($ticket) {
                        $nested->where('role', UserRole::Agent)
                            ->where('department_id', $ticket->department_id);
                    });
            })
            ->get();
    }
}
