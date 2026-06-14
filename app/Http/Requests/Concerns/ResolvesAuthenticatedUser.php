<?php

declare(strict_types=1);

namespace App\Http\Requests\Concerns;

use App\Models\User;
use App\Support\AuthenticatedUser;

trait ResolvesAuthenticatedUser
{
    protected function authenticatedUser(): User
    {
        return AuthenticatedUser::from($this->user());
    }
}
