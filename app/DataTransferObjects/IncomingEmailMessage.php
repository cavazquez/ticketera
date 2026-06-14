<?php

declare(strict_types=1);

namespace App\DataTransferObjects;

readonly class IncomingEmailMessage
{
    /**
     * @param  array<int, IncomingEmailAttachment>  $attachments
     */
    public function __construct(
        public string $messageId,
        public string $fromEmail,
        public ?string $fromName,
        public string $subject,
        public string $body,
        public array $attachments = [],
        public bool $isAutomated = false,
    ) {}
}
