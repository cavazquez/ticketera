<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\DB;

class TicketNumberGenerator
{
    public function next(): string
    {
        return DB::transaction(function (): string {
            $sequence = DB::table('ticket_sequences')
                ->lockForUpdate()
                ->first();

            if ($sequence === null) {
                throw new \RuntimeException('Ticket sequence is not initialized.');
            }

            $next = ((int) $sequence->last_number) + 1;

            DB::table('ticket_sequences')
                ->where('id', $sequence->id)
                ->update(['last_number' => $next]);

            return 'TKT-'.str_pad((string) $next, 6, '0', STR_PAD_LEFT);
        });
    }
}
