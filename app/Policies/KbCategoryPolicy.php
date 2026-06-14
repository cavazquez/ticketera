<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\KbCategory;
use App\Models\User;

class KbCategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, KbCategory $kbCategory): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, KbCategory $kbCategory): bool
    {
        return $user->isAdmin();
    }
}
