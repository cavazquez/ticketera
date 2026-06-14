<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Ticket;
use App\Models\User;

class TicketPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Ticket $ticket): bool
    {
        if ($user->isClient()) {
            return $ticket->user_id === $user->id;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isAgent()) {
            return $ticket->department_id === $user->department_id
                || $ticket->assigned_to === $user->id;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->isClient();
    }

    public function reply(User $user, Ticket $ticket): bool
    {
        return $this->view($user, $ticket);
    }

    public function update(User $user, Ticket $ticket): bool
    {
        return $user->isStaff() && $this->view($user, $ticket);
    }
}
