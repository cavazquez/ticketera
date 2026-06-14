<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\TicketActivity;
use App\Models\User;

class TicketActivityLogger
{
    public function logChange(
        Ticket $ticket,
        ?User $user,
        string $field,
        mixed $oldValue,
        mixed $newValue,
    ): void {
        $old = $this->normalizeValue($oldValue);
        $new = $this->normalizeValue($newValue);

        if ($old === $new) {
            return;
        }

        TicketActivity::query()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $user?->id,
            'field' => $field,
            'old_value' => $old,
            'new_value' => $new,
        ]);
    }

    private function normalizeValue(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof \BackedEnum) {
            return $value->value;
        }

        return (string) $value;
    }
}
