<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Config;

final class MailConfigurator
{
    public static function apply(?Setting $settings = null): void
    {
        $settings ??= Setting::current();

        if (! $settings->outbound_smtp_enabled || ! $settings->smtpIsConfigured()) {
            return;
        }

        $encryption = match ($settings->smtp_encryption) {
            'ssl' => 'ssl',
            'tls' => 'tls',
            default => null,
        };

        Config::set('mail.default', 'smtp');
        Config::set('mail.mailers.smtp', array_merge(
            config('mail.mailers.smtp', []),
            [
                'transport' => 'smtp',
                'host' => $settings->smtp_host,
                'port' => $settings->smtp_port,
                'encryption' => $encryption,
                'username' => $settings->smtp_username,
                'password' => $settings->smtp_password,
            ],
        ));
        Config::set('mail.from', [
            'address' => $settings->mailFromAddress(),
            'name' => $settings->mailFromName(),
        ]);
    }
}
