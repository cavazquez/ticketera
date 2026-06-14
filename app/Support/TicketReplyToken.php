<?php

declare(strict_types=1);

namespace App\Support;

final class TicketReplyToken
{
    public static function for(string $ticketNumber): string
    {
        return substr(
            hash_hmac('sha256', strtoupper($ticketNumber), (string) config('app.key')),
            0,
            8,
        );
    }

    public static function verify(string $ticketNumber, string $token): bool
    {
        return hash_equals(self::for($ticketNumber), strtolower($token));
    }
}
