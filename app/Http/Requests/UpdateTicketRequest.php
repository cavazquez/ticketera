<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isStaff() ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<int, ValidationRule|string>|string>
     */
    public function rules(): array
    {
        return [
            'status' => ['sometimes', 'in:abierto,en_progreso,resuelto,cerrado'],
            'priority' => ['sometimes', 'in:baja,normal,alta,urgente'],
            'assigned_to' => ['nullable', 'exists:users,id'],
            'department_id' => ['sometimes', 'exists:departments,id'],
        ];
    }
}
