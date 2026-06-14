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

        if ($request !== null && $request->hasSession()) {
            $candidate = $request->session()->get('locale');
        }

        if (is_string($candidate) && self::isSupported($candidate)) {
            return $candidate;
        }

        try {
            $settingsLocale = Setting::current()->locale;
            if (is_string($settingsLocale) && self::isSupported($settingsLocale)) {
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
        $locale = $locale ?? self::resolve($request);
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
        $locale = $locale ?? App::getLocale();
        $path = lang_path("{$locale}.json");

        if (! File::exists($path)) {
            $path = lang_path('es.json');
        }

        $contents = File::get($path);

        return json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
    }
}
