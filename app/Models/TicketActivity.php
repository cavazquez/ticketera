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

    /**
     * @param  array<int|string, string>  $userNames  Optional map of user id => name to avoid per-row lookups.
     */
    public function formattedChange(array $userNames = []): string
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
            'assigned_to' => $this->formatAssigneeChange($userNames),
            default => ($this->old_value ?? '—').' → '.($this->new_value ?? '—'),
        };
    }

    /**
     * User ids referenced by this activity when it is an assignment change.
     *
     * @return list<int>
     */
    public function referencedUserIds(): array
    {
        if ($this->field !== 'assigned_to') {
            return [];
        }

        return array_values(
            collect([$this->old_value, $this->new_value])
                ->filter(fn ($value) => filled($value))
                ->map(fn ($value) => (int) $value)
                ->all()
        );
    }

    /**
     * @param  array<int|string, string>  $userNames
     */
    private function formatAssigneeChange(array $userNames): string
    {
        return $this->assigneeLabel($this->old_value, $userNames).' → '.$this->assigneeLabel($this->new_value, $userNames);
    }

    /**
     * @param  array<int|string, string>  $userNames
     */
    private function assigneeLabel(?string $userId, array $userNames): string
    {
        if (blank($userId)) {
            return 'Sin asignar';
        }

        if (isset($userNames[$userId])) {
            return $userNames[$userId];
        }

        $user = User::query()->find($userId);

        return $user instanceof User ? $user->name : "Usuario #{$userId}";
    }
}
