<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Ticket;
use App\Models\TicketReply;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TicketAttachmentService
{
    public const MAX_FILES = 5;

    public const MAX_FILE_KILOBYTES = 20480;

    public const MAX_FILE_BYTES = self::MAX_FILE_KILOBYTES * 1024;

    /** @var list<string> */
    public const ALLOWED_EXTENSIONS = [
        'pdf', 'jpg', 'jpeg', 'png', 'gif', 'webp', 'txt', 'csv', 'doc', 'docx', 'xls', 'xlsx', 'zip',
    ];

    /**
     * @param  array<int, UploadedFile>  $files
     */
    public function storeMany(Ticket $ticket, User $user, array $files, ?TicketReply $reply = null): void
    {
        foreach ($files as $file) {
            if (! $file->isValid()) {
                continue;
            }

            $path = $file->store("attachments/tickets/{$ticket->id}", 'local');

            $ticket->attachments()->create([
                'ticket_reply_id' => $reply?->id,
                'user_id' => $user->id,
                'disk' => 'local',
                'path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getClientMimeType() ?: 'application/octet-stream',
                'size' => $file->getSize(),
            ]);
        }
    }

    /**
     * Store an attachment from raw bytes (e.g. an inbound email part).
     *
     * Applies the same size/type limits as web uploads and returns whether the
     * attachment was actually stored.
     */
    public function storeFromContents(
        Ticket $ticket,
        User $user,
        string $filename,
        string $contents,
        string $mimeType,
        ?TicketReply $reply = null,
    ): bool {
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $size = strlen($contents);

        if (! $this->isAllowedAttachment($extension, $size)) {
            return false;
        }

        $storedName = Str::uuid()->toString().($extension !== '' ? ".{$extension}" : '');
        $path = "attachments/tickets/{$ticket->id}/{$storedName}";

        Storage::disk('local')->put($path, $contents);

        $ticket->attachments()->create([
            'ticket_reply_id' => $reply?->id,
            'user_id' => $user->id,
            'disk' => 'local',
            'path' => $path,
            'original_name' => $filename,
            'mime_type' => $mimeType,
            'size' => $size,
        ]);

        return true;
    }

    public function isAllowedAttachment(string $extension, int $size): bool
    {
        if ($size <= 0 || $size > self::MAX_FILE_BYTES) {
            return false;
        }

        return in_array($extension, self::ALLOWED_EXTENSIONS, true);
    }
}
