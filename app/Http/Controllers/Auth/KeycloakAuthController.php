<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Auth\KeycloakAuthenticator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class KeycloakAuthController extends Controller
{
    public function redirect(KeycloakAuthenticator $keycloak): RedirectResponse
    {
        return redirect()->away($keycloak->redirectUrl());
    }

    public function callback(KeycloakAuthenticator $keycloak): RedirectResponse
    {
        try {
            $user = $keycloak->authenticateCallback();
        } catch (\Throwable $exception) {
            Log::warning('Keycloak login failed', ['message' => $exception->getMessage()]);

            return redirect()
                ->route('login')
                ->withErrors(['email' => 'No se pudo iniciar sesión con Keycloak.']);
        }

        Auth::login($user, remember: true);
        request()->session()->regenerate();

        return redirect()->intended(route('dashboard', absolute: false));
    }
}
