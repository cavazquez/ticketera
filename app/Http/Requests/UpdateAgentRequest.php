<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAgentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<int, ValidationRule|string>|string>
     */
    public function rules(): array
    {
        $agent = $this->route('agent');
        $agentId = $agent instanceof User ? $agent->id : null;
        $isClient = $this->input('role') === UserRole::Client->value;

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($agentId)],
            'role' => ['required', Rule::in([
                UserRole::Client->value,
                UserRole::Agent->value,
                UserRole::Admin->value,
            ])],
            'department_id' => [
                Rule::requiredIf(! $isClient),
                'nullable',
                'exists:departments,id',
            ],
        ];
    }
}
