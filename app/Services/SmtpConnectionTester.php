<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Mail;

class SmtpConnectionTester
{
    public function test(Setting $settings, string $recipient): void
    {
        if (! $settings->outbound_smtp_enabled) {
            throw new \RuntimeException('El SMTP del panel no está activado.');
        }

        if (! $settings->smtpIsConfigured()) {
            throw new \RuntimeException('Completá host, usuario y contraseña SMTP antes de probar.');
        }

        MailConfigurator::apply($settings);

        Mail::raw(
            'Correo de prueba enviado desde Ticketera. Si lo recibiste, la configuración SMTP es correcta.',
            static function ($message) use ($recipient, $settings): void {
                $message->to($recipient)
                    ->subject('Ticketera — prueba SMTP');
            },
        );
    }
}
