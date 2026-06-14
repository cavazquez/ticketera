<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\KbArticle;
use App\Models\KbCategory;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HelpController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('q', ''));
        $categorySlug = (string) $request->query('category', '');

        $categories = KbCategory::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->withCount(['articles as published_count' => fn ($query) => $query->where('is_published', true)])
            ->get(['id', 'name', 'slug', 'description']);

        $articlesQuery = KbArticle::query()
            ->where('is_published', true)
            ->with('category:id,name,slug')
            ->orderByDesc('is_featured')
            ->orderBy('sort_order')
            ->orderBy('title');

        if ($categorySlug !== '') {
            $articlesQuery->whereHas('category', fn ($query) => $query->where('slug', $categorySlug));
        }

        if ($search !== '') {
            $articlesQuery->where(function ($query) use ($search) {
                $query->where('title', 'like', "%{$search}%")
                    ->orWhere('summary', 'like', "%{$search}%")
                    ->orWhere('body', 'like', "%{$search}%");
            });
        }

        return Inertia::render('Help/Index', [
            'categories' => $categories,
            'articles' => $articlesQuery->get([
                'id', 'kb_category_id', 'title', 'slug', 'summary', 'is_featured', 'view_count', 'published_at',
            ]),
            'filters' => [
                'q' => $search,
                'category' => $categorySlug,
            ],
            'featured' => $search === '' && $categorySlug === ''
                ? KbArticle::query()
                    ->where('is_published', true)
                    ->where('is_featured', true)
                    ->with('category:id,name,slug')
                    ->orderBy('sort_order')
                    ->limit(6)
                    ->get(['id', 'kb_category_id', 'title', 'slug', 'summary'])
                : [],
        ]);
    }

    public function show(KbArticle $kbArticle): Response
    {
        abort_unless($kbArticle->is_published, 404);

        $kbArticle->load(['category:id,name,slug', 'author:id,name']);
        $kbArticle->recordView();

        $related = KbArticle::query()
            ->where('is_published', true)
            ->where('id', '!=', $kbArticle->id)
            ->when(
                $kbArticle->kb_category_id,
                fn ($query) => $query->where('kb_category_id', $kbArticle->kb_category_id),
            )
            ->orderBy('sort_order')
            ->limit(5)
            ->get(['id', 'title', 'slug', 'summary']);

        return Inertia::render('Help/Show', [
            'article' => $kbArticle->fresh(['category:id,name,slug', 'author:id,name']),
            'related' => $related,
        ]);
    }
}
