<?php

declare(strict_types=1);

namespace App\Enums;

enum UserRole: string
{
    case Client = 'cliente';
    case Agent = 'agente';
    case Admin = 'admin';

    public function label(): string
    {
        return match ($this) {
            self::Client => 'Cliente',
            self::Agent => 'Agente',
            self::Admin => 'Administrador',
        };
    }

    public function isStaff(): bool
    {
        return in_array($this, [self::Agent, self::Admin], true);
    }
}
