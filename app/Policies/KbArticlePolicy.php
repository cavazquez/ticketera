<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\KbArticle;
use App\Models\User;

class KbArticlePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, KbArticle $kbArticle): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, KbArticle $kbArticle): bool
    {
        return $user->isAdmin();
    }
}
