<?php

namespace App\Console\Commands;

use App\Services\ImapInboundMailFetcher;
use App\Services\InboundEmailProcessor;
use Illuminate\Console\Command;

class FetchIncomingEmail extends Command
{
    protected $signature = 'tickets:fetch-email';

    protected $description = 'Importa tickets y respuestas desde el buzón IMAP configurado';

    public function handle(ImapInboundMailFetcher $fetcher, InboundEmailProcessor $processor): int
    {
        $messages = $fetcher->fetch();
        $processed = 0;

        foreach ($messages as $message) {
            if ($processor->process($message) !== null) {
                $processed++;
            }
        }

        $this->info('Mensajes leídos: '.count($messages).", procesados: {$processed}");

        return self::SUCCESS;
    }
}
