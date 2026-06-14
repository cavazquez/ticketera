<?php

declare(strict_types=1);

namespace App\Enums;

enum AuthDriver: string
{
    case Local = 'local';
    case Ldap = 'ldap';
    case Keycloak = 'keycloak';

    public function label(): string
    {
        return match ($this) {
            self::Local => 'Local (base de datos)',
            self::Ldap => 'LDAP / Active Directory',
            self::Keycloak => 'Keycloak (OpenID Connect)',
        };
    }
}
