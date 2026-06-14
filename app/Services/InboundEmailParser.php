<?php

declare(strict_types=1);

namespace App\Services;

use App\Support\TicketReplyToken;

class InboundEmailParser
{
    public function extractTicketNumber(string $subject): ?string
    {
        if (preg_match('/\[(TKT-\d{6})-([a-f0-9]{8})\]/i', $subject, $matches) === 1) {
            $number = strtoupper($matches[1]);

            if (TicketReplyToken::verify($number, $matches[2])) {
                return $number;
            }

            return null;
        }

        if (preg_match('/\[(TKT-\d{6})\]/i', $subject, $matches) === 1) {
            return strtoupper($matches[1]);
        }

        return null;
    }

    public function hasInvalidSignedToken(string $subject): bool
    {
        if (preg_match('/\[(TKT-\d{6})-([a-f0-9]{8})\]/i', $subject, $matches) !== 1) {
            return false;
        }

        return ! TicketReplyToken::verify(strtoupper($matches[1]), $matches[2]);
    }

    public function cleanSubject(string $subject): string
    {
        $cleaned = preg_replace('/\[(TKT-\d{6})(?:-[a-f0-9]{8})?\]\s*/i', '', $subject) ?? $subject;
        $cleaned = preg_replace('/^(Re|Fwd|Fw):\s*/i', '', $cleaned) ?? $cleaned;

        return trim($cleaned) ?: 'Consulta por email';
    }

    public function normalizeMessageId(string $messageId): string
    {
        return trim($messageId, " \t\n\r\0\x0B<>");
    }
}
