<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KbArticle extends Model
{
    protected $fillable = [
        'kb_category_id',
        'title',
        'slug',
        'summary',
        'body',
        'is_published',
        'is_featured',
        'sort_order',
        'view_count',
        'created_by',
        'published_at',
    ];

    #[\Override]
    protected function casts(): array
    {
        return [
            'kb_category_id' => 'integer',
            'is_published' => 'boolean',
            'is_featured' => 'boolean',
            'sort_order' => 'integer',
            'view_count' => 'integer',
            'published_at' => 'datetime',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(KbCategory::class, 'kb_category_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    #[\Override]
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function recordView(): void
    {
        $this->increment('view_count');
    }
}
