<?php

declare(strict_types=1);

namespace App\Services;

use App\DataTransferObjects\HealthCheckResult;
use App\Models\Setting;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Schema;

class SystemHealthService
{
    private const PHP_MIN_VERSION = '8.3.0';

    /** @var list<string> */
    private const REQUIRED_EXTENSIONS = ['pdo_mysql', 'mbstring', 'openssl', 'tokenizer', 'xml', 'ctype', 'json', 'fileinfo'];

    /** @var list<string> */
    private const RECOMMENDED_EXTENSIONS = ['redis', 'imap', 'ldap'];

    /** @var list<string> */
    private const REQUIRED_TABLES = [
        'users',
        'departments',
        'tickets',
        'ticket_replies',
        'settings',
        'canned_responses',
        'ticket_sequences',
        'ticket_activities',
        'ticket_attachments',
        'processed_incoming_emails',
        'cache',
        'jobs',
        'failed_jobs',
    ];

    /** @var array<string, list<string>> */
    private const RECOMMENDED_INDEXES = [
        'tickets' => [
            'tickets_queue_index',
            'tickets_assignee_status_index',
            'tickets_user_created_index',
            'tickets_due_at_index',
        ],
        'ticket_replies' => [
            'ticket_replies_ticket_created_index',
        ],
    ];

    public function __construct(
        private Migrator $migrator,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function run(): array
    {
        $checks = [
            ...$this->checkRuntime(),
            ...$this->checkDatabase(),
            ...$this->checkCacheAndQueue(),
            ...$this->checkEmail(),
            ...$this->checkStorage(),
            ...$this->checkOperations(),
        ];

        $counts = ['ok' => 0, 'warning' => 0, 'error' => 0];

        foreach ($checks as $check) {
            $counts[$check->status]++;
        }

        $grouped = collect($checks)
            ->map(fn (HealthCheckResult $check) => $check->toArray())
            ->groupBy('group')
            ->map(fn ($items, $group) => [
                'name' => $group,
                'label' => $this->groupLabel($group),
                'checks' => $items->values()->all(),
            ])
            ->values()
            ->all();

        return [
            'generated_at' => now()->timezone(config('app.timezone'))->format('d/m/Y H:i:s'),
            'environment' => [
                'php_version' => PHP_VERSION,
                'laravel_version' => app()->version(),
                'app_env' => config('app.env'),
            ],
            'summary' => [
                ...$counts,
                'total' => count($checks),
                'healthy' => $counts['error'] === 0,
            ],
            'groups' => $grouped,
        ];
    }

    /**
     * @return list<HealthCheckResult>
     */
    private function checkRuntime(): array
    {
        $checks = [];

        $checks[] = new HealthCheckResult(
            group: 'runtime',
            key: 'php_version',
            label: 'Versión de PHP',
            status: version_compare(PHP_VERSION, self::PHP_MIN_VERSION, '>=') ? 'ok' : 'error',
            message: version_compare(PHP_VERSION, self::PHP_MIN_VERSION, '>=')
                ? 'PHP '.PHP_VERSION
                : 'Se requiere PHP '.self::PHP_MIN_VERSION.' o superior (actual: '.PHP_VERSION.')',
        );

        $checks[] = new HealthCheckResult(
            group: 'runtime',
            key: 'app_key',
            label: 'APP_KEY',
            status: filled(config('app.key')) ? 'ok' : 'error',
            message: filled(config('app.key'))
                ? 'Clave de aplicación configurada.'
                : 'Falta APP_KEY. Ejecutá php artisan key:generate.',
        );

        foreach (self::REQUIRED_EXTENSIONS as $extension) {
            $loaded = extension_loaded($extension);
            $checks[] = new HealthCheckResult(
                group: 'runtime',
                key: "ext_{$extension}",
                label: "Extensión {$extension}",
                status: $loaded ? 'ok' : 'error',
                message: $loaded ? 'Instalada.' : 'No instalada (obligatoria).',
            );
        }

        foreach (self::RECOMMENDED_EXTENSIONS as $extension) {
            $loaded = extension_loaded($extension);
            $required = $this->extensionIsRequired($extension);
            $checks[] = new HealthCheckResult(
                group: 'runtime',
                key: "ext_{$extension}",
                label: "Extensión {$extension}",
                status: $loaded ? 'ok' : ($required ? 'error' : 'warning'),
                message: $loaded
                    ? 'Instalada.'
                    : ($required
                        ? 'Requerida por la configuración actual.'
                        : 'No instalada (recomendada).'),
            );
        }

        return $checks;
    }

    /**
     * @return list<HealthCheckResult>
     */
    private function checkDatabase(): array
    {
        $checks = [];

        try {
            DB::connection()->getPdo();
            $checks[] = new HealthCheckResult(
                group: 'database',
                key: 'connection',
                label: 'Conexión a MariaDB/MySQL',
                status: 'ok',
                message: 'Conexión exitosa.',
                detail: config('database.connections.'.config('database.default').'.host'),
            );
        } catch (\Throwable $exception) {
            return [
                new HealthCheckResult(
                    group: 'database',
                    key: 'connection',
                    label: 'Conexión a MariaDB/MySQL',
                    status: 'error',
                    message: 'No se pudo conectar a la base de datos.',
                    detail: $exception->getMessage(),
                ),
            ];
        }

        $checks[] = $this->checkDatabaseCharset();
        $checks = [...$checks, ...$this->checkRequiredTables()];
        $checks[] = $this->checkPendingMigrations();
        $checks = [...$checks, ...$this->checkRecommendedIndexes()];

        return $checks;
    }

    private function checkDatabaseCharset(): HealthCheckResult
    {
        $database = config('database.connections.'.config('database.default').'.database');

        $schema = DB::selectOne(
            'SELECT DEFAULT_CHARACTER_SET_NAME AS charset, DEFAULT_COLLATION_NAME AS collation
             FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = ?',
            [$database],
        );

        if ($schema === null) {
            return new HealthCheckResult(
                group: 'database',
                key: 'charset',
                label: 'Codificación de la base',
                status: 'warning',
                message: 'No se pudo leer la codificación del esquema.',
            );
        }

        $charset = (string) $schema->charset;
        $collation = (string) $schema->collation;
        $isUtf8Mb4 = str_starts_with(strtolower($charset), 'utf8mb4')
            || str_starts_with(strtolower($collation), 'utf8mb4');

        return new HealthCheckResult(
            group: 'database',
            key: 'charset',
            label: 'Codificación de la base',
            status: $isUtf8Mb4 ? 'ok' : 'warning',
            message: $isUtf8Mb4
                ? "utf8mb4 ({$collation})"
                : "Se recomienda utf8mb4 (actual: {$charset} / {$collation}).",
        );
    }

    /**
     * @return list<HealthCheckResult>
     */
    private function checkRequiredTables(): array
    {
        $existing = collect(DB::select('SHOW TABLES'))
            ->map(fn ($row) => strtolower((string) array_values((array) $row)[0]))
            ->all();

        $checks = [];

        foreach (self::REQUIRED_TABLES as $table) {
            $present = in_array(strtolower($table), $existing, true);
            $checks[] = new HealthCheckResult(
                group: 'database',
                key: "table_{$table}",
                label: "Tabla {$table}",
                status: $present ? 'ok' : 'error',
                message: $present ? 'Presente.' : 'Falta ejecutar migraciones.',
            );
        }

        return $checks;
    }

    private function checkPendingMigrations(): HealthCheckResult
    {
        $files = $this->migrator->getMigrationFiles($this->migrator->paths());
        $ran = $this->migrator->getRepository()->getRan();
        $pending = array_values(array_diff(array_keys($files), $ran));

        return new HealthCheckResult(
            group: 'database',
            key: 'migrations',
            label: 'Migraciones',
            status: $pending === [] ? 'ok' : 'error',
            message: $pending === []
                ? 'Todas las migraciones están aplicadas.'
                : count($pending).' migración(es) pendiente(s).',
            detail: $pending === [] ? null : implode(', ', $pending),
        );
    }

    /**
     * @return list<HealthCheckResult>
     */
    private function checkRecommendedIndexes(): array
    {
        $checks = [];

        foreach (self::RECOMMENDED_INDEXES as $table => $expectedIndexes) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            $existing = collect(DB::select("SHOW INDEX FROM `{$table}`"))
                ->pluck('Key_name')
                ->map(fn ($name) => (string) $name)
                ->unique()
                ->all();

            foreach ($expectedIndexes as $index) {
                $present = in_array($index, $existing, true);
                $checks[] = new HealthCheckResult(
                    group: 'database',
                    key: "index_{$table}_{$index}",
                    label: "Índice {$index}",
                    status: $present ? 'ok' : 'warning',
                    message: $present
                        ? "Presente en {$table}."
                        : "Falta en {$table}. Ejecutá las migraciones de performance.",
                );
            }
        }

        return $checks;
    }

    /**
     * @return list<HealthCheckResult>
     */
    private function checkCacheAndQueue(): array
    {
        $checks = [];

        $cacheDriver = (string) config('cache.default');
        $checks[] = new HealthCheckResult(
            group: 'cache_queue',
            key: 'cache_driver',
            label: 'Driver de cache',
            status: $cacheDriver === 'redis' ? 'ok' : 'warning',
            message: "Cache: {$cacheDriver}".($cacheDriver === 'redis' ? '' : ' (se recomienda redis).'),
        );

        $sessionDriver = (string) config('session.driver');
        $checks[] = new HealthCheckResult(
            group: 'cache_queue',
            key: 'session_driver',
            label: 'Driver de sesión',
            status: $sessionDriver === 'redis' ? 'ok' : 'warning',
            message: "Sesiones: {$sessionDriver}".($sessionDriver === 'redis' ? '' : ' (se recomienda redis).'),
        );

        $queueDriver = (string) config('queue.default');
        $checks[] = new HealthCheckResult(
            group: 'cache_queue',
            key: 'queue_driver',
            label: 'Driver de colas',
            status: $queueDriver === 'redis' ? 'ok' : 'warning',
            message: "Colas: {$queueDriver}".($queueDriver === 'redis' ? '' : ' (se recomienda redis).'),
        );

        try {
            Redis::connection()->ping();
            $checks[] = new HealthCheckResult(
                group: 'cache_queue',
                key: 'redis',
                label: 'Redis',
                status: 'ok',
                message: 'Conexión exitosa.',
            );
        } catch (\Throwable $exception) {
            $severity = in_array('redis', [config('cache.default'), config('queue.default'), config('session.driver')], true)
                ? 'error'
                : 'warning';

            $checks[] = new HealthCheckResult(
                group: 'cache_queue',
                key: 'redis',
                label: 'Redis',
                status: $severity,
                message: 'No se pudo conectar a Redis.',
                detail: $exception->getMessage(),
            );
        }

        if (Schema::hasTable('failed_jobs')) {
            $failedCount = (int) DB::table('failed_jobs')->count();
            $checks[] = new HealthCheckResult(
                group: 'cache_queue',
                key: 'failed_jobs',
                label: 'Jobs fallidos',
                status: $failedCount === 0 ? 'ok' : 'warning',
                message: $failedCount === 0
                    ? 'Sin jobs fallidos.'
                    : "{$failedCount} job(s) en failed_jobs.",
            );
        }

        return $checks;
    }

    /**
     * @return list<HealthCheckResult>
     */
    private function checkEmail(): array
    {
        $settings = Setting::current();
        $checks = [];

        if ($settings->outbound_smtp_enabled) {
            $configured = $settings->smtpIsConfigured();
            $checks[] = new HealthCheckResult(
                group: 'email',
                key: 'smtp_panel',
                label: 'SMTP (panel)',
                status: $configured ? 'ok' : 'error',
                message: $configured
                    ? "Configurado ({$settings->smtp_host}:{$settings->smtp_port})."
                    : 'Activado pero incompleto.',
            );
        } else {
            $mailer = (string) config('mail.default');
            $checks[] = new HealthCheckResult(
                group: 'email',
                key: 'smtp_env',
                label: 'Email saliente',
                status: $mailer === 'log' ? 'warning' : 'ok',
                message: $mailer === 'log'
                    ? 'Usando driver log (.env). Los correos no se envían realmente.'
                    : "Driver activo: {$mailer}.",
            );
        }

        if ($settings->inbound_email_enabled) {
            $configured = $settings->inboundEmailIsConfigured();
            $imapLoaded = extension_loaded('imap');
            $status = match (true) {
                ! $configured => 'error',
                ! $imapLoaded => 'error',
                default => 'ok',
            };

            $checks[] = new HealthCheckResult(
                group: 'email',
                key: 'imap_panel',
                label: 'IMAP (panel)',
                status: $status,
                message: match (true) {
                    ! $configured => 'Activado pero incompleto.',
                    ! $imapLoaded => 'Activado pero falta la extensión PHP IMAP.',
                    default => "Configurado ({$settings->inbound_imap_host}).",
                },
            );
        } else {
            $checks[] = new HealthCheckResult(
                group: 'email',
                key: 'imap_panel',
                label: 'Email entrante',
                status: 'ok',
                message: 'Desactivado.',
            );
        }

        return $checks;
    }

    /**
     * @return list<HealthCheckResult>
     */
    private function checkStorage(): array
    {
        $paths = [
            'storage/app' => storage_path('app'),
            'storage/framework/cache' => storage_path('framework/cache'),
            'storage/logs' => storage_path('logs'),
            'bootstrap/cache' => base_path('bootstrap/cache'),
        ];

        $checks = [];

        foreach ($paths as $key => $path) {
            $writable = File::isDirectory($path) && is_writable($path);
            $checks[] = new HealthCheckResult(
                group: 'storage',
                key: $key,
                label: $key,
                status: $writable ? 'ok' : 'error',
                message: $writable ? 'Escritura OK.' : 'Sin permisos de escritura.',
            );
        }

        return $checks;
    }

    /**
     * @return list<HealthCheckResult>
     */
    private function checkOperations(): array
    {
        return [
            $this->checkProcessHeartbeat(
                key: 'queue_worker',
                label: 'Worker de colas',
                cacheKey: 'system_health:queue_heartbeat',
                runningMessage: 'Activo (heartbeat reciente).',
                stoppedMessage: 'Sin actividad reciente. Verificá que el servicio queue esté corriendo (Docker: ticketera-queue).',
            ),
            $this->checkProcessHeartbeat(
                key: 'scheduler',
                label: 'Scheduler',
                cacheKey: 'system_health:scheduler_heartbeat',
                runningMessage: 'Activo (heartbeat reciente).',
                stoppedMessage: 'Sin actividad reciente. Verificá que el servicio scheduler esté corriendo (SLA + email entrante).',
                maxAgeSeconds: 90,
            ),
        ];
    }

    private function checkProcessHeartbeat(
        string $key,
        string $label,
        string $cacheKey,
        string $runningMessage,
        string $stoppedMessage,
        int $maxAgeSeconds = 120,
    ): HealthCheckResult {
        $lastBeat = Cache::get($cacheKey);
        $lastBeat = is_numeric($lastBeat) ? (int) $lastBeat : null;
        $active = $lastBeat !== null && (now()->timestamp - $lastBeat) <= $maxAgeSeconds;

        return new HealthCheckResult(
            group: 'operations',
            key: $key,
            label: $label,
            status: $active ? 'ok' : 'warning',
            message: $active ? $runningMessage : $stoppedMessage,
        );
    }

    private function extensionIsRequired(string $extension): bool
    {
        $settings = Setting::current();

        return match ($extension) {
            'redis' => in_array('redis', [
                config('cache.default'),
                config('queue.default'),
                config('session.driver'),
            ], true),
            'imap' => $settings->inbound_email_enabled,
            'ldap' => $settings->authDriver()->value === 'ldap',
            default => false,
        };
    }

    private function groupLabel(string $group): string
    {
        return match ($group) {
            'runtime' => 'Entorno PHP',
            'database' => 'Base de datos',
            'cache_queue' => 'Cache y colas',
            'email' => 'Correo',
            'storage' => 'Almacenamiento',
            'operations' => 'Servicios Docker',
            default => ucfirst($group),
        };
    }
}
