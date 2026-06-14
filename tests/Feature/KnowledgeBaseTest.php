<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\KbArticle;
use App\Models\KbCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KnowledgeBaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_can_view_published_help_articles(): void
    {
        $category = KbCategory::create([
            'name' => 'General',
            'slug' => 'general',
            'is_active' => true,
        ]);

        $article = KbArticle::create([
            'kb_category_id' => $category->id,
            'title' => 'FAQ publicada',
            'slug' => 'faq-publicada',
            'body' => 'Contenido de ayuda.',
            'is_published' => true,
            'published_at' => now(),
        ]);

        KbArticle::create([
            'title' => 'Borrador',
            'slug' => 'borrador',
            'body' => 'No visible.',
            'is_published' => false,
        ]);

        $this->get(route('help.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Help/Index')
                ->has('articles', 1)
                ->where('articles.0.slug', $article->slug));

        $this->get(route('help.show', $article->slug))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Help/Show')
                ->where('article.title', 'FAQ publicada'));

        $this->assertSame(1, $article->fresh()->view_count);
    }

    public function test_draft_article_is_not_accessible(): void
    {
        $article = KbArticle::create([
            'title' => 'Privado',
            'slug' => 'privado',
            'body' => 'Secreto.',
            'is_published' => false,
        ]);

        $this->get(route('help.show', $article->slug))->assertNotFound();
    }

    public function test_admin_can_manage_knowledge_base(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $this->actingAs($admin)
            ->get(route('panel.knowledge-base.index'))
            ->assertOk();

        $this->actingAs($admin)
            ->post(route('panel.kb-categories.store'), [
                'name' => 'Soporte',
                'slug' => 'soporte',
                'description' => 'Ayuda técnica',
                'sort_order' => 1,
                'is_active' => true,
            ])
            ->assertRedirect();

        $category = KbCategory::query()->where('slug', 'soporte')->first();
        $this->assertNotNull($category);

        $this->actingAs($admin)
            ->post(route('panel.kb-articles.store'), [
                'kb_category_id' => $category->id,
                'title' => 'Restablecer contraseña',
                'slug' => 'restablecer-contrasena',
                'summary' => 'Guía rápida',
                'body' => 'Usá «Olvidé mi contraseña» en el login.',
                'is_published' => true,
                'is_featured' => true,
                'sort_order' => 1,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('kb_articles', [
            'slug' => 'restablecer-contrasena',
            'is_published' => true,
        ]);
    }

    public function test_agent_cannot_manage_knowledge_base(): void
    {
        $agent = User::factory()->create(['role' => UserRole::Agent]);

        $this->actingAs($agent)
            ->get(route('panel.knowledge-base.index'))
            ->assertForbidden();
    }
}
