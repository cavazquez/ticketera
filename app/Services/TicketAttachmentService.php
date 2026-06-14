<?php

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

    /**
     * @param  array<int, UploadedFile>  $files
     */
    public function storeMany(Ticket $ticket, User $user, array $files, ?TicketReply $reply = null): void
    {
        foreach ($files as $file) {
            if (! $file instanceof UploadedFile || ! $file->isValid()) {
                continue;
            }

            $path = $file->store("attachments/tickets/{$ticket->id}", 'local');

            $ticket->attachments()->create([
                'ticket_reply_id' => $reply?->id,
                'user_id' => $user->id,
                'disk' => 'local',
                'path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getClientMimeType() ?? 'application/octet-stream',
                'size' => $file->getSize(),
            ]);
        }
    }

    public function storeFromContents(
        Ticket $ticket,
        User $user,
        string $filename,
        string $contents,
        string $mimeType,
        ?TicketReply $reply = null,
    ): void {
        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        $storedName = Str::uuid()->toString().($extension ? ".{$extension}" : '');
        $path = "attachments/tickets/{$ticket->id}/{$storedName}";

        Storage::disk('local')->put($path, $contents);

        $ticket->attachments()->create([
            'ticket_reply_id' => $reply?->id,
            'user_id' => $user->id,
            'disk' => 'local',
            'path' => $path,
            'original_name' => $filename,
            'mime_type' => $mimeType,
            'size' => strlen($contents),
        ]);
    }
}
