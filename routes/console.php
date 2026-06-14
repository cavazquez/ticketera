<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('tickets:check-sla-alerts')->everyFiveMinutes();
Schedule::command('tickets:fetch-email')->everyFiveMinutes();

Schedule::call(function (): void {
    \Illuminate\Support\Facades\Cache::put('system_health:scheduler_heartbeat', now()->timestamp, 300);
})->everyMinute()->name('system-health-scheduler-heartbeat');
