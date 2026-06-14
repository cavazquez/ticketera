<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KbCategory extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'sort_order',
        'is_active',
    ];

    #[\Override]
    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /** @return HasMany<KbArticle, $this> */
    public function articles(): HasMany
    {
        return $this->hasMany(KbArticle::class);
    }

    /** @return HasMany<KbArticle, $this> */
    public function publishedArticles(): HasMany
    {
        return $this->articles()
            ->where('is_published', true)
            ->orderBy('sort_order')
            ->orderBy('title');
    }
}
