<?php

namespace App\Console\Commands;

use App\Services\SlaAlertService;
use Illuminate\Console\Command;

class CheckTicketSlaAlerts extends Command
{
    protected $signature = 'tickets:check-sla-alerts';

    protected $description = 'Envía alertas de SLA por vencer y vencidos';

    public function handle(SlaAlertService $service): int
    {
        $sent = $service->process();

        $this->info("Alertas SLA enviadas: {$sent}");

        return self::SUCCESS;
    }
}
