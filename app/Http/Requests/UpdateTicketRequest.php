<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

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
            'assigned_to' => [
                'nullable',
                Rule::exists('users', 'id')->whereIn('role', [
                    UserRole::Agent->value,
                    UserRole::Admin->value,
                ]),
            ],
            'department_id' => ['sometimes', 'exists:departments,id'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $user = $this->user();

            // Admins can reassign and move tickets freely; agents are scoped to their department.
            if (! $user instanceof User || ! $user->isAgent()) {
                return;
            }

            if ($this->filled('department_id')
                && (int) $this->input('department_id') !== (int) $user->department_id) {
                $validator->errors()->add(
                    'department_id',
                    'No podés mover tickets a otro departamento.',
                );
            }

            $assignedTo = $this->input('assigned_to');

            if (filled($assignedTo)) {
                $isValidAssignee = User::query()
                    ->whereIn('role', [UserRole::Agent->value, UserRole::Admin->value])
                    ->where('department_id', $user->department_id)
                    ->whereKey($assignedTo)
                    ->exists();

                if (! $isValidAssignee) {
                    $validator->errors()->add(
                        'assigned_to',
                        'Solo podés asignar tickets a integrantes de tu departamento.',
                    );
                }
            }
        });
    }
}
