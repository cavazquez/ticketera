<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\File;

class LocaleManager
{
    /** @var list<string> */
    public const SUPPORTED = ['es', 'en'];

    /**
     * Per-process cache of decoded translation files. FPM workers are long-lived,
     * so this avoids re-reading and re-decoding the JSON on every request.
     *
     * @var array<string, array<string, string>>
     */
    private static array $translations = [];

    /** @return array<string, string> */
    public static function labels(): array
    {
        return [
            'es' => 'Español',
            'en' => 'English',
        ];
    }

    public static function resolve(?Request $request = null): string
    {
        $candidate = null;

        if ($request instanceof Request && $request->hasSession()) {
            $candidate = $request->session()->get('locale');
        }

        if (is_string($candidate) && self::isSupported($candidate)) {
            return $candidate;
        }

        try {
            $settingsLocale = Setting::current()->locale;
            if (self::isSupported($settingsLocale)) {
                return $settingsLocale;
            }
        } catch (\Throwable) {
            //
        }

        $configured = (string) config('app.locale', 'es');

        return self::isSupported($configured) ? $configured : 'es';
    }

    public static function apply(?string $locale = null, ?Request $request = null): string
    {
        $locale ??= self::resolve($request);
        App::setLocale($locale);
        config(['app.locale' => $locale]);

        return $locale;
    }

    public static function isSupported(string $locale): bool
    {
        return in_array($locale, self::SUPPORTED, true);
    }

    /**
     * @return array<string, string>
     */
    public static function uiTranslations(?string $locale = null): array
    {
        $locale ??= App::getLocale();

        if (isset(self::$translations[$locale])) {
            return self::$translations[$locale];
        }

        $path = lang_path("{$locale}.json");

        if (! File::exists($path)) {
            $path = lang_path('es.json');
        }

        /** @var array<string, string> $decoded */
        $decoded = json_decode(File::get($path), true, 512, JSON_THROW_ON_ERROR);

        return self::$translations[$locale] = $decoded;
    }
}
