<?php

declare(strict_types=1);

namespace App\Enums;

enum SlaAlertType: string
{
    case Warning = 'warning';
    case Breach = 'breach';

    public function label(): string
    {
        return match ($this) {
            self::Warning => 'SLA por vencer',
            self::Breach => 'SLA vencido',
        };
    }
}
