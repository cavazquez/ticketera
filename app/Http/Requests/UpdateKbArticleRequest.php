<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\KbArticle;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UpdateKbArticleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $articleId = $this->route('kbArticle');
        $articleId = $articleId instanceof KbArticle ? $articleId->id : null;

        return [
            'kb_category_id' => ['nullable', 'integer', 'exists:kb_categories,id'],
            'title' => ['required', 'string', 'max:200'],
            'slug' => [
                'required',
                'string',
                'max:200',
                'alpha_dash',
                Rule::unique('kb_articles', 'slug')->ignore($articleId),
            ],
            'summary' => ['nullable', 'string', 'max:500'],
            'body' => ['required', 'string', 'max:50000'],
            'is_published' => ['required', 'boolean'],
            'is_featured' => ['required', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ];
    }

    #[\Override]
    protected function prepareForValidation(): void
    {
        if (blank($this->input('slug')) && filled($this->input('title'))) {
            $this->merge(['slug' => Str::slug((string) $this->input('title'))]);
        }
    }
}
