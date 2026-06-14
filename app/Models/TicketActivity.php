<?php

namespace App\Models;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketActivity extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'ticket_id',
        'user_id',
        'field',
        'old_value',
        'new_value',
    ];

    /** @return BelongsTo<Ticket, $this> */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function fieldLabel(): string
    {
        return match ($this->field) {
            'status' => 'Estado',
            'priority' => 'Prioridad',
            'assigned_to' => 'Asignación',
            default => $this->field,
        };
    }

    public function formattedChange(): string
    {
        return match ($this->field) {
            'status' => sprintf(
                '%s → %s',
                $this->old_value ? (TicketStatus::tryFrom($this->old_value)?->label() ?? $this->old_value) : '—',
                $this->new_value ? (TicketStatus::tryFrom($this->new_value)?->label() ?? $this->new_value) : '—',
            ),
            'priority' => sprintf(
                '%s → %s',
                $this->old_value ? (TicketPriority::tryFrom($this->old_value)?->label() ?? $this->old_value) : '—',
                $this->new_value ? (TicketPriority::tryFrom($this->new_value)?->label() ?? $this->new_value) : '—',
            ),
            'assigned_to' => $this->formatAssigneeChange(),
            default => ($this->old_value ?? '—').' → '.($this->new_value ?? '—'),
        };
    }

    private function formatAssigneeChange(): string
    {
        return $this->assigneeLabel($this->old_value).' → '.$this->assigneeLabel($this->new_value);
    }

    private function assigneeLabel(?string $userId): string
    {
        if (blank($userId)) {
            return 'Sin asignar';
        }

        return User::query()->find($userId)?->name ?? "Usuario #{$userId}";
    }
}
