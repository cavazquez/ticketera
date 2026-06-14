<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreAgentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        $isClient = $this->input('role') === UserRole::Client->value;

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
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
