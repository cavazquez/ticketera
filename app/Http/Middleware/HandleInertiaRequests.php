<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use App\Support\LocaleManager;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    #[\Override]
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    #[\Override]
    public function share(Request $request): array
    {
        $settings = Setting::current();

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user()?->loadMissing('department:id,name'),
            ],
            'locale' => fn () => app()->getLocale(),
            'locales' => LocaleManager::labels(...),
            'translations' => LocaleManager::uiTranslations(...),
            'appName' => fn () => $settings->app_name,
            'canRegister' => fn () => $settings->allow_public_registration,
            'turnstile' => fn () => [
                'enabled' => $settings->turnstileIsConfigured(),
                'siteKey' => $settings->turnstile_site_key,
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
        ];
    }
}
