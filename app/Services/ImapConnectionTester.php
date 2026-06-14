<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Setting;

class ImapConnectionTester
{
    public function __construct(
        private readonly ImapInboundMailFetcher $fetcher,
    ) {}

    public function test(?Setting $settings = null): void
    {
        $settings ??= Setting::current();

        if (! $settings->inbound_email_enabled) {
            throw new \RuntimeException('El email entrante no está activado.');
        }

        if (! $settings->inboundEmailIsConfigured()) {
            throw new \RuntimeException('Completá host, usuario y contraseña IMAP antes de probar.');
        }

        $this->fetcher->testConnection($settings);
    }
}
