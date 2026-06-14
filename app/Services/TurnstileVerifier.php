<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class TurnstileVerifier
{
    public function isEnabled(): bool
    {
        $settings = Setting::current();

        return $settings->turnstile_enabled
            && filled($settings->turnstile_site_key)
            && filled($settings->turnstile_secret_key);
    }

    public function assertValid(?string $token, ?string $remoteIp = null): void
    {
        if (! $this->isEnabled()) {
            return;
        }

        if (blank($token)) {
            throw ValidationException::withMessages([
                'cf_turnstile_response' => 'Completá la verificación de seguridad.',
            ]);
        }

        $response = Http::asForm()->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
            'secret' => Setting::current()->turnstile_secret_key,
            'response' => $token,
            'remoteip' => $remoteIp,
        ]);

        if (! $response->json('success')) {
            throw ValidationException::withMessages([
                'cf_turnstile_response' => 'La verificación de seguridad falló. Intentá de nuevo.',
            ]);
        }
    }
}
