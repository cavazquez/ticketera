<?php

declare(strict_types=1);

namespace App\DataTransferObjects;

readonly class HealthCheckResult
{
    public function __construct(
        public string $group,
        public string $key,
        public string $label,
        public string $status,
        public string $message,
        public ?string $detail = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'group' => $this->group,
            'key' => $this->key,
            'label' => $this->label,
            'status' => $this->status,
            'message' => $this->message,
            'detail' => $this->detail,
        ];
    }
}
