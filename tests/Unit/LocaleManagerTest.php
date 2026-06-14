<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\LocaleManager;
use Tests\TestCase;

class LocaleManagerTest extends TestCase
{
    public function test_supported_locales(): void
    {
        $this->assertTrue(LocaleManager::isSupported('es'));
        $this->assertTrue(LocaleManager::isSupported('en'));
        $this->assertFalse(LocaleManager::isSupported('fr'));
    }

    public function test_labels_cover_supported_locales(): void
    {
        $labels = LocaleManager::labels();

        $this->assertSame(['es', 'en'], array_keys($labels));
    }

    public function test_ui_translations_load_per_locale(): void
    {
        $this->assertSame('Help / FAQ', LocaleManager::uiTranslations('en')['nav.help']);
        $this->assertSame('Ayuda / FAQ', LocaleManager::uiTranslations('es')['nav.help']);
    }

    public function test_apply_sets_locale_and_falls_back_for_unsupported(): void
    {
        $this->assertSame('en', LocaleManager::apply('en'));
        $this->assertSame('en', app()->getLocale());
    }
}
