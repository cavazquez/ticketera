<?php

declare(strict_types=1);

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreKbArticleRequest;
use App\Http\Requests\StoreKbCategoryRequest;
use App\Http\Requests\UpdateKbArticleRequest;
use App\Http\Requests\UpdateKbCategoryRequest;
use App\Models\KbArticle;
use App\Models\KbCategory;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class KnowledgeBaseController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', KbCategory::class);

        return Inertia::render('Panel/KnowledgeBase/Index', [
            'categories' => KbCategory::query()
                ->withCount('articles')
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(),
            'articles' => KbArticle::query()
                ->with(['category:id,name', 'author:id,name'])
                ->orderBy('sort_order')
                ->orderBy('title')
                ->get(),
        ]);
    }

    public function storeCategory(StoreKbCategoryRequest $request): RedirectResponse
    {
        KbCategory::create($request->validated());

        return back()->with('success', __('messages.kb_category_created'));
    }

    public function updateCategory(UpdateKbCategoryRequest $request, KbCategory $kbCategory): RedirectResponse
    {
        $this->authorize('update', $kbCategory);
        $kbCategory->update($request->validated());

        return back()->with('success', __('messages.kb_category_updated'));
    }

    public function destroyCategory(KbCategory $kbCategory): RedirectResponse
    {
        $this->authorize('delete', $kbCategory);
        $kbCategory->delete();

        return back()->with('success', __('messages.kb_category_deleted'));
    }

    public function storeArticle(StoreKbArticleRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['created_by'] = $request->user()?->id;

        if ($data['is_published'] ?? false) {
            $data['published_at'] = now();
        }

        KbArticle::create($data);

        return back()->with('success', __('messages.kb_article_created'));
    }

    public function updateArticle(UpdateKbArticleRequest $request, KbArticle $kbArticle): RedirectResponse
    {
        $this->authorize('update', $kbArticle);

        $data = $request->validated();

        if (($data['is_published'] ?? false) && $kbArticle->published_at === null) {
            $data['published_at'] = now();
        }

        if (! ($data['is_published'] ?? false)) {
            $data['published_at'] = null;
        }

        $kbArticle->update($data);

        return back()->with('success', __('messages.kb_article_updated'));
    }

    public function destroyArticle(KbArticle $kbArticle): RedirectResponse
    {
        $this->authorize('delete', $kbArticle);
        $kbArticle->delete();

        return back()->with('success', __('messages.kb_article_deleted'));
    }
}
