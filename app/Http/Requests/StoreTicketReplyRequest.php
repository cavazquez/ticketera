<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesTicketAttachments;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreTicketReplyRequest extends FormRequest
{
    use ValidatesTicketAttachments;
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<int, ValidationRule|string>|string>
     */
    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'min:3'],
            'is_internal' => ['sometimes', 'boolean'],
            ...$this->attachmentRules(),
        ];
    }

    #[\Override]
    public function messages(): array
    {
        return [
            'body.required' => 'Escribí un mensaje.',
            'body.min' => 'El mensaje es demasiado corto.',
        ];
    }
}
