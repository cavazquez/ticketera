<?php

namespace App\Http\Controllers\Client;

use App\Enums\TicketPriority;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTicketReplyRequest;
use App\Http\Requests\StoreTicketRequest;
use App\Models\Department;
use App\Models\Ticket;
use App\Services\TicketAttachmentService;
use App\Services\TicketReplyPaginator;
use App\Services\TicketReplyService;
use App\Services\TicketSetupService;
use App\Support\EnumOptions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TicketController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $this->requireUser($request);

        $tickets = Ticket::query()
            ->with(['department:id,name', 'assignee:id,name'])
            ->where('user_id', $user->id)
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Client/Tickets/Index', [
            'tickets' => $tickets,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Client/Tickets/Create', [
            'departments' => Department::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'priorities' => EnumOptions::from(TicketPriority::class, true),
        ]);
    }

    public function store(StoreTicketRequest $request, TicketSetupService $setup, TicketAttachmentService $attachments): RedirectResponse
    {
        $user = $this->requireUser($request);

        $ticket = Ticket::create([
            ...$request->safe()->only(['subject', 'department_id', 'priority', 'body']),
            'user_id' => $user->id,
        ]);

        $attachments->storeMany($ticket, $user, $request->validatedAttachments());

        $setup->configureNewTicket($ticket);

        return redirect()
            ->route('client.tickets.show', $ticket)
            ->with('success', 'Ticket creado correctamente.');
    }

    public function show(
        Request $request,
        Ticket $ticket,
        TicketReplyPaginator $replyPaginator,
    ): Response {
        $this->authorize('view', $ticket);

        $ticket->load([
            'department:id,name',
            'assignee:id,name',
            'attachments' => fn ($query) => $query->whereNull('ticket_reply_id'),
        ]);

        return Inertia::render('Client/Tickets/Show', [
            'ticket' => $ticket,
            'replies' => $replyPaginator->paginate($ticket, $request, false),
        ]);
    }

    public function reply(
        StoreTicketReplyRequest $request,
        Ticket $ticket,
        TicketReplyService $replyService,
    ): RedirectResponse {
        $this->authorize('reply', $ticket);

        $user = $this->requireUser($request);

        $replyService->reply(
            $ticket,
            $user,
            $request->validated('body'),
            false,
            $request->validatedAttachments(),
        );

        return back()->with('success', 'Respuesta enviada.');
    }
}
