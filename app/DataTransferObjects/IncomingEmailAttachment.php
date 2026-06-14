<?php

declare(strict_types=1);

namespace App\DataTransferObjects;

readonly class IncomingEmailAttachment
{
    public function __construct(
        public string $filename,
        public string $contents,
        public string $mimeType,
    ) {}
}
