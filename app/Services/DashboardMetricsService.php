<?php

namespace App\Services;

use App\Enums\TicketStatus;
use App\Models\Department;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class DashboardMetricsService
{
    /**
     * @return array<string, mixed>
     */
    public function forUser(User $user): array
    {
        $baseQuery = $this->scopedTicketsQuery($user);
        $openStatuses = [TicketStatus::Open->value, TicketStatus::InProgress->value];

        $openQuery = (clone $baseQuery)->whereIn('status', $openStatuses);

        $overdueTickets = (clone $openQuery)
            ->whereNotNull('due_at')
            ->where('due_at', '<', now())
            ->with(['department:id,name', 'assignee:id,name'])
            ->orderBy('due_at')
            ->limit(8)
            ->get(['id', 'number', 'subject', 'status', 'priority', 'department_id', 'assigned_to', 'due_at']);

        return [
            'open_count' => (clone $openQuery)->count(),
            'sla_overdue_count' => (clone $openQuery)
                ->whereNotNull('due_at')
                ->where('due_at', '<', now())
                ->count(),
            'my_assigned_count' => $user->isStaff()
                ? Ticket::query()
                    ->where('assigned_to', $user->id)
                    ->whereIn('status', $openStatuses)
                    ->count()
                : 0,
            'by_status' => (clone $baseQuery)
                ->selectRaw('status, count(*) as count')
                ->groupBy('status')
                ->pluck('count', 'status'),
            'by_department' => $this->departmentBreakdown($user, $openStatuses),
            'overdue_tickets' => $overdueTickets,
        ];
    }

    /**
     * @param  array<int, string>  $openStatuses
     * @return array<int, array<string, mixed>>
     */
    private function departmentBreakdown(User $user, array $openStatuses): array
    {
        $departmentIds = Department::query()
            ->when($user->isAgent(), fn (Builder $query) => $query->where('id', $user->department_id))
            ->orderBy('name')
            ->pluck('id');

        return $departmentIds->map(function (int $departmentId) use ($openStatuses, $user) {
            $department = Department::query()->find($departmentId, ['id', 'name']);
            $query = $this->scopedTicketsQuery($user)->where('department_id', $departmentId);

            $openCount = (clone $query)->whereIn('status', $openStatuses)->count();
            $overdueCount = (clone $query)
                ->whereIn('status', $openStatuses)
                ->whereNotNull('due_at')
                ->where('due_at', '<', now())
                ->count();

            return [
                'id' => $department?->id,
                'name' => $department?->name,
                'open_count' => $openCount,
                'overdue_count' => $overdueCount,
            ];
        })->values()->all();
    }

    /** @return Builder<Ticket> */
    private function scopedTicketsQuery(User $user): Builder
    {
        $query = Ticket::query();

        if ($user->isAgent()) {
            $query->where(function (Builder $builder) use ($user) {
                $builder->where('department_id', $user->department_id)
                    ->orWhere('assigned_to', $user->id);
            });
        }

        return $query;
    }
}
