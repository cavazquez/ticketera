<?php

declare(strict_types=1);

namespace App\Services;

use App\DataTransferObjects\IncomingEmailAttachment;
use App\DataTransferObjects\IncomingEmailMessage;
use App\Models\Setting;
use Illuminate\Support\Str;

class ImapInboundMailFetcher
{
    public function __construct(
        private readonly InboundEmailParser $parser,
    ) {}

    /**
     * @return array<int, IncomingEmailMessage>
     */
    public function fetch(): array
    {
        if (! extension_loaded('imap')) {
            throw new \RuntimeException('La extensión PHP IMAP no está instalada.');
        }

        $settings = Setting::current();

        if (! $settings->inbound_email_enabled || ! $settings->inboundEmailIsConfigured()) {
            return [];
        }

        $mailbox = $this->buildMailboxString($settings);
        $connection = @imap_open(
            $mailbox,
            (string) $settings->inbound_imap_username,
            (string) $settings->inbound_imap_password,
            OP_READONLY,
        );

        if ($connection === false) {
            throw new \RuntimeException('No se pudo conectar al buzón IMAP: '.imap_last_error());
        }

        try {
            $messageNumbers = imap_search($connection, 'UNSEEN', SE_UID) ?: [];

            $messages = [];
            foreach ($messageNumbers as $uid) {
                $message = $this->parseMessage($connection, (int) $uid);
                if ($message instanceof IncomingEmailMessage) {
                    $messages[] = $message;
                    imap_setflag_full($connection, (string) $uid, '\\Seen', ST_UID);
                }
            }

            return $messages;
        } finally {
            imap_close($connection);
        }
    }

    public function testConnection(?Setting $settings = null): void
    {
        if (! extension_loaded('imap')) {
            throw new \RuntimeException('La extensión PHP IMAP no está instalada.');
        }

        $settings ??= Setting::current();
        $mailbox = $this->buildMailboxString($settings);
        $connection = @imap_open(
            $mailbox,
            (string) $settings->inbound_imap_username,
            (string) $settings->inbound_imap_password,
            OP_READONLY,
        );

        if ($connection === false) {
            throw new \RuntimeException('No se pudo conectar al buzón IMAP: '.imap_last_error());
        }

        imap_close($connection);
    }

    private function buildMailboxString(Setting $settings): string
    {
        $encryptionFlag = match ($settings->inbound_imap_encryption) {
            'ssl' => '/imap/ssl',
            'tls' => '/imap/tls',
            default => '/imap',
        };

        $folder = $settings->inbound_imap_folder ?: 'INBOX';

        return sprintf(
            '{%s:%d%s/novalidate-cert}%s',
            $settings->inbound_imap_host,
            $settings->inbound_imap_port,
            $encryptionFlag,
            $folder,
        );
    }

    private function parseMessage($connection, int $uid): ?IncomingEmailMessage
    {
        $header = imap_fetchheader($connection, $uid, FT_UID);
        $overview = imap_fetch_overview($connection, (string) $uid, FT_UID)[0] ?? null;
        $structure = imap_fetchstructure($connection, $uid, FT_UID);

        if ($overview === null || $structure === false) {
            return null;
        }

        $fromEmail = isset($overview->from) ? $this->extractEmailAddress($overview->from) : '';
        if ($fromEmail === '') {
            return null;
        }

        $messageId = $this->parser->normalizeMessageId($overview->message_id ?? (string) $uid);
        $body = $this->extractBody($connection, $uid, $structure);
        $attachments = $this->extractAttachments($connection, $uid, $structure);

        return new IncomingEmailMessage(
            messageId: $messageId,
            fromEmail: $fromEmail,
            fromName: isset($overview->from) ? $this->extractDisplayName($overview->from) : null,
            subject: isset($overview->subject) ? imap_utf8($overview->subject) : '(Sin asunto)',
            body: $body,
            attachments: $attachments,
            isAutomated: $this->isAutomatedMessage($header),
        );
    }

    private function isAutomatedMessage(string $header): bool
    {
        return (bool) preg_match('/^(Auto-Submitted|Precedence:\s*(bulk|junk|list)|X-Auto-Response-Suppress)/im', $header);
    }

    private function extractEmailAddress(string $from): string
    {
        if (preg_match('/<([^>]+)>/', $from, $matches) === 1) {
            return Str::lower(trim($matches[1]));
        }

        return Str::lower(trim($from));
    }

    private function extractDisplayName(string $from): ?string
    {
        if (preg_match('/^([^<]+)</', $from, $matches) === 1) {
            $name = trim(str_replace('"', '', $matches[1]));

            return $name !== '' ? imap_utf8($name) : null;
        }

        return null;
    }

    /**
     * @param  object|array<string, mixed>  $structure
     */
    private function extractBody($connection, int $uid, object $structure, string $partNumber = ''): string
    {
        if ($structure->type === 0) {
            $raw = imap_fetchbody($connection, $uid, $partNumber === '' ? '1' : $partNumber, FT_UID);
            $decoded = $this->decodePart($raw, (int) $structure->encoding);

            if (strtoupper($structure->subtype ?? '') === 'HTML') {
                return trim(strip_tags($decoded));
            }

            return trim($decoded);
        }

        if ($structure->type === 1 && isset($structure->parts)) {
            foreach ($structure->parts as $index => $part) {
                $section = $partNumber === '' ? (string) ($index + 1) : "{$partNumber}.".($index + 1);

                if (strtoupper($part->subtype ?? '') === 'PLAIN') {
                    return $this->extractBody($connection, $uid, $part, $section);
                }
            }

            foreach ($structure->parts as $index => $part) {
                $section = $partNumber === '' ? (string) ($index + 1) : "{$partNumber}.".($index + 1);

                if (strtoupper($part->subtype ?? '') === 'HTML') {
                    return $this->extractBody($connection, $uid, $part, $section);
                }
            }
        }

        return '';
    }

    /**
     * @return array<int, IncomingEmailAttachment>
     */
    private function extractAttachments($connection, int $uid, object $structure, string $partNumber = ''): array
    {
        $attachments = [];

        if ($structure->type === 1 && isset($structure->parts)) {
            foreach ($structure->parts as $index => $part) {
                $section = $partNumber === '' ? (string) ($index + 1) : "{$partNumber}.".($index + 1);
                $attachments = array_merge($attachments, $this->extractAttachments($connection, $uid, $part, $section));
            }

            return $attachments;
        }

        $isAttachment = ($structure->ifdisposition && strtoupper($structure->disposition ?? '') === 'ATTACHMENT')
            || ($structure->ifparameters && $this->parameterValue($structure->parameters ?? [], 'name') !== null);

        if (! $isAttachment) {
            return [];
        }

        $filename = $this->parameterValue($structure->dparameters ?? [], 'filename')
            ?? $this->parameterValue($structure->parameters ?? [], 'name')
            ?? 'adjunto.bin';

        $raw = imap_fetchbody($connection, $uid, $partNumber === '' ? '1' : $partNumber, FT_UID);
        $contents = $this->decodePart($raw, (int) $structure->encoding);

        $attachments[] = new IncomingEmailAttachment(
            filename: imap_utf8($filename),
            contents: $contents,
            mimeType: $this->resolveMimeType($structure),
        );

        return $attachments;
    }

    /**
     * @param  array<int, object>|null  $parameters
     */
    private function parameterValue(?array $parameters, string $attribute): ?string
    {
        if ($parameters === null) {
            return null;
        }

        foreach ($parameters as $parameter) {
            if (strtoupper($parameter->attribute ?? '') === strtoupper($attribute)) {
                return $parameter->value ?? null;
            }
        }

        return null;
    }

    private function resolveMimeType(object $structure): string
    {
        $primary = ['TEXT', 'MULTIPART', 'MESSAGE', 'APPLICATION', 'AUDIO', 'IMAGE', 'VIDEO', 'OTHER'][$structure->type] ?? 'APPLICATION';
        $subtype = strtoupper($structure->subtype ?? 'OCTET-STREAM');

        return strtolower("{$primary}/{$subtype}");
    }

    private function decodePart(string $body, int $encoding): string
    {
        return match ($encoding) {
            ENCBASE64 => base64_decode($body) ?: '',
            ENCQUOTEDPRINTABLE => quoted_printable_decode($body),
            default => $body,
        };
    }
}
