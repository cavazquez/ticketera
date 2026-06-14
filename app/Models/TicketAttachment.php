<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * @property-read string $human_size
 * @property-read string $download_url
 */
class TicketAttachment extends Model
{
    protected $fillable = [
        'ticket_id',
        'ticket_reply_id',
        'user_id',
        'disk',
        'path',
        'original_name',
        'mime_type',
        'size',
    ];

    protected $hidden = [
        'path',
        'disk',
        'ticket_id',
        'ticket_reply_id',
        'user_id',
    ];

    protected $appends = [
        'human_size',
        'download_url',
    ];

    #[\Override]
    protected static function booted(): void
    {
        static::deleting(function (TicketAttachment $attachment) {
            Storage::disk($attachment->disk)->delete($attachment->path);
        });
    }

    /** @return BelongsTo<Ticket, $this> */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    /** @return BelongsTo<TicketReply, $this> */
    public function reply(): BelongsTo
    {
        return $this->belongsTo(TicketReply::class, 'ticket_reply_id');
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function humanSize(): string
    {
        $bytes = $this->size;

        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 1).' MB';
        }

        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 1).' KB';
        }

        return $bytes.' B';
    }

    public function getHumanSizeAttribute(): string
    {
        return $this->humanSize();
    }

    public function getDownloadUrlAttribute(): string
    {
        return route('attachments.download', $this);
    }
}
