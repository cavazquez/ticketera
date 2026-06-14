<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CannedResponse extends Model
{
    protected $fillable = [
        'title',
        'body',
        'department_id',
        'is_active',
        'created_by',
    ];

    #[\Override]
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /** @return BelongsTo<Department, $this> */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /** @return BelongsTo<User, $this> */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function renderFor(Ticket $ticket): string
    {
        return str_replace(
            ['{cliente}', '{numero}', '{asunto}'],
            [$ticket->user->name, $ticket->number, $ticket->subject],
            $this->body,
        );
    }
}
