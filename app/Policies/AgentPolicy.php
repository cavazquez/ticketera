<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

class AgentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, User $agent): bool
    {
        return $user->isAdmin() && $agent->isStaff();
    }
}
