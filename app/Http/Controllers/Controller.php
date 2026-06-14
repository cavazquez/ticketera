<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\AuthenticatedUser;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;

abstract class Controller
{
    use AuthorizesRequests, ValidatesRequests;

    protected function requireUser(?Request $request = null): User
    {
        if ($request !== null) {
            return AuthenticatedUser::from($request->user());
        }

        return AuthenticatedUser::from(auth()->user());
    }
}
