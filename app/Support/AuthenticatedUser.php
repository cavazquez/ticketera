<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;

final class AuthenticatedUser
{
    public static function from(?Authenticatable $user): User
    {
        if (! $user instanceof User) {
            abort(403);
        }

        return $user;
    }
}
