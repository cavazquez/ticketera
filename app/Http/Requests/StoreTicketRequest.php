<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ProtectsAgainstBots;
use App\Http\Requests\Concerns\ValidatesTicketAttachments;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreTicketRequest extends FormRequest
{
    use ProtectsAgainstBots;
    use ValidatesTicketAttachments;

    public function authorize(): bool
    {
        return $this->user()?->isClient() ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<int, ValidationRule|string>|string>
     */
    public function rules(): array
    {
        return [
            ...$this->botProtectionRules(),
            'subject' => ['required', 'string', 'max:255'],
            'department_id' => ['required', 'exists:departments,id'],
            'priority' => ['required', 'in:baja,normal,alta,urgente'],
            'body' => ['required', 'string', 'min:10'],
            ...$this->attachmentRules(),
        ];
    }

    #[\Override]
    protected function passedValidation(): void
    {
        $this->validateBotProtection();
    }

    #[\Override]
    public function messages(): array
    {
        return [
            'subject.required' => 'El asunto es obligatorio.',
            'department_id.required' => 'Seleccioná un departamento.',
            'priority.required' => 'Seleccioná una prioridad.',
            'body.required' => 'El mensaje es obligatorio.',
            'body.min' => 'El mensaje debe tener al menos 10 caracteres.',
        ];
    }
}
