<?php

declare(strict_types=1);

namespace App\Http\Requests\Concerns;

use App\Services\TicketAttachmentService;
use Illuminate\Http\UploadedFile;

trait ValidatesTicketAttachments
{
    /**
     * @return array<string, array<int, string>>
     */
    protected function attachmentRules(): array
    {
        return [
            'attachments' => ['nullable', 'array', 'max:'.TicketAttachmentService::MAX_FILES],
            'attachments.*' => [
                'file',
                'max:'.TicketAttachmentService::MAX_FILE_KILOBYTES,
                'mimes:'.implode(',', TicketAttachmentService::ALLOWED_EXTENSIONS),
            ],
        ];
    }

    /**
     * @return array<int, UploadedFile>
     */
    public function validatedAttachments(): array
    {
        /** @var array<int, UploadedFile>|null $attachments */
        $attachments = $this->file('attachments');

        return is_array($attachments) ? array_values($attachments) : [];
    }
}
