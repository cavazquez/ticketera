<?php

namespace App\Http\Controllers\Panel;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTicketReplyRequest;
use App\Http\Requests\UpdateTicketRequest;
use App\Models\CannedResponse;
use App\Models\Department;
use App\Models\Ticket;
use App\Models\User;
use App\Services\TicketActivityLogger;
use App\Services\TicketNotifier;
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

        $query = Ticket::query()
            ->with(['user:id,name,email', 'department:id,name', 'assignee:id,name'])
            ->latest();

        if ($user->isAgent()) {
            $query->where(function ($builder) use ($user) {
                $builder->where('department_id', $user->department_id)
                    ->orWhere('assigned_to', $user->id);
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('department_id')) {
            $query->where('department_id', $request->integer('department_id'));
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->string('priority'));
        }

        if ($request->filled('assigned_to')) {
            if ($request->string('assigned_to')->toString() === 'unassigned') {
                $query->whereNull('assigned_to');
            } else {
                $query->where('assigned_to', $request->integer('assigned_to'));
            }
        }

        if ($request->filled('search')) {
            $search = $request->string('search');
            $query->where(function ($builder) use ($search) {
                $builder->where('number', 'like', "%{$search}%")
                    ->orWhere('subject', 'like', "%{$search}%");
            });
        }

        $selectedAgent = null;
        if ($request->filled('assigned_to') && $request->string('assigned_to')->toString() !== 'unassigned') {
            $selectedAgent = User::query()
                ->whereIn('role', [UserRole::Agent, UserRole::Admin])
                ->find($request->integer('assigned_to'), ['id', 'name', 'email']);
        }

        return Inertia::render('Panel/Tickets/Index', [
            'tickets' => $query->paginate(15)->withQueryString(),
            'filters' => $request->only(['status', 'department_id', 'priority', 'assigned_to', 'search']),
            'departments' => Department::query()->orderBy('name')->get(['id', 'name']),
            'selectedAgent' => $selectedAgent,
            'statuses' => EnumOptions::from(TicketStatus::class, true),
            'priorities' => EnumOptions::from(TicketPriority::class, true),
        ]);
    }

    public function show(
        Request $request,
        Ticket $ticket,
        TicketReplyPaginator $replyPaginator,
    ): Response {
        $this->authorize('view', $ticket);

        $user = $this->requireUser($request);

        $ticket->load([
            'user:id,name,email',
            'department:id,name',
            'assignee:id,name',
            'attachments' => fn ($query) => $query->whereNull('ticket_reply_id'),
        ]);

        $agentsQuery = User::query()
            ->whereIn('role', [UserRole::Agent, UserRole::Admin])
            ->orderBy('name');

        if ($user->isAgent()) {
            $agentsQuery->where('department_id', $user->department_id);
        }

        $activityModels = $ticket->activities()
            ->with('user:id,name')
            ->limit(50)
            ->get();

        $assigneeNames = User::query()
            ->whereIn('id', $activityModels->flatMap->referencedUserIds()->unique()->all())
            ->pluck('name', 'id');

        $activities = $activityModels->map(fn ($activity) => [
            'id' => $activity->id,
            'user_name' => $activity->user_id !== null ? ($activity->user->name ?? 'Sistema') : 'Sistema',
            'field_label' => $activity->fieldLabel(),
            'change' => $activity->formattedChange($assigneeNames->all()),
            'created_at' => $activity->created_at,
        ]);

        return Inertia::render('Panel/Tickets/Show', [
            'ticket' => $ticket,
            'replies' => $replyPaginator->paginate($ticket, $request, true),
            'activities' => $activities,
            'agents' => $agentsQuery->get(['id', 'name', 'department_id']),
            'statuses' => EnumOptions::from(TicketStatus::class, true),
            'priorities' => EnumOptions::from(TicketPriority::class, true),
            'cannedResponses' => CannedResponse::query()
                ->where('is_active', true)
                ->where(function ($query) use ($ticket) {
                    $query->whereNull('department_id')
                        ->orWhere('department_id', $ticket->department_id);
                })
                ->orderBy('title')
                ->get(['id', 'title', 'body']),
        ]);
    }

    public function update(
        UpdateTicketRequest $request,
        Ticket $ticket,
        TicketSetupService $setup,
        TicketActivityLogger $activityLogger,
    ): RedirectResponse {
        $this->authorize('update', $ticket);

        $user = $this->requireUser($request);
        $previousStatus = $ticket->status;
        $previousPriority = $ticket->priority;
        $previousAssignee = $ticket->assigned_to;

        $ticket->update($request->validated());

        $activityLogger->logChange($ticket, $user, 'status', $previousStatus, $ticket->status);
        $activityLogger->logChange($ticket, $user, 'priority', $previousPriority, $ticket->priority);
        $activityLogger->logChange($ticket, $user, 'assigned_to', $previousAssignee, $ticket->assigned_to);

        if ($ticket->priority !== $previousPriority) {
            $setup->applySla($ticket);
        }

        if ($ticket->wasChanged('status')) {
            app(TicketNotifier::class)->notifyStatusChange($ticket, $previousStatus, $user);
        }

        return back()->with('success', 'Ticket actualizado.');
    }

    public function reply(
        StoreTicketReplyRequest $request,
        Ticket $ticket,
        TicketReplyService $replyService,
    ): RedirectResponse {
        $this->authorize('reply', $ticket);

        $user = $this->requireUser($request);
        $isInternal = $user->isStaff() && $request->boolean('is_internal');

        $replyService->reply(
            $ticket,
            $user,
            $request->validated('body'),
            $isInternal,
            $request->validatedAttachments(),
        );

        return back()->with('success', $isInternal ? 'Nota interna agregada.' : 'Respuesta enviada.');
    }
}
