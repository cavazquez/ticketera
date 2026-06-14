<?php

namespace App\Http\Requests\Concerns;

use App\Services\TurnstileVerifier;
use Illuminate\Validation\ValidationException;

trait ProtectsAgainstBots
{
    /**
     * @return array<string, array<int, string>>
     */
    protected function botProtectionRules(): array
    {
        return [
            'cf_turnstile_response' => ['nullable', 'string'],
        ];
    }

    protected function validateBotProtection(): void
    {
        if (filled($this->input('company_website'))) {
            throw ValidationException::withMessages([
                'subject' => 'No se pudo procesar el formulario.',
            ]);
        }

        app(TurnstileVerifier::class)->assertValid(
            $this->input('cf_turnstile_response'),
            $this->ip(),
        );
    }
}
