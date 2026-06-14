<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\TicketReply;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

class TicketReplyPaginator
{
    private const int PER_PAGE = 20;

    /** @return LengthAwarePaginator<int, TicketReply> */
    public function paginate(Ticket $ticket, Request $request, bool $includeInternal): LengthAwarePaginator
    {
        $query = $ticket->replies()
            ->with(['user:id,name,role', 'attachments'])
            ->orderByDesc('created_at');

        if (! $includeInternal) {
            $query->where('is_internal', false);
        }

        return $query
            ->paginate(self::PER_PAGE, ['*'], 'page', max(1, $request->integer('page', 1)))
            ->withQueryString();
    }
}
