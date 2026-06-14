<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Setting;
use App\Services\MailConfigurator;
use App\Support\LocaleManager;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Queue\Events\Looping;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use SocialiteProviders\Keycloak\KeycloakExtendSocialite;
use SocialiteProviders\Manager\SocialiteWasCalled;

class AppServiceProvider extends ServiceProvider
{
    #[\Override]
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        Event::listen(SocialiteWasCalled::class, KeycloakExtendSocialite::class.'@handle');
        Event::listen(MessageSending::class, fn () => MailConfigurator::apply());
        Event::listen(Looping::class, function (): void {
            Cache::put('system_health:queue_heartbeat', now()->timestamp, 300);
        });

        $this->configureMailFromSettings();
        $this->configureTimezoneFromSettings();
        $this->configureLocaleFromSettings();
        $this->configureRateLimiting();
    }

    private function configureMailFromSettings(): void
    {
        try {
            if (! Schema::hasTable('settings')) {
                return;
            }

            MailConfigurator::apply(Setting::current());
        } catch (\Throwable) {
            //
        }
    }

    private function configureTimezoneFromSettings(): void
    {
        try {
            if (! Schema::hasTable('settings')) {
                return;
            }

            $timezone = Setting::current()->timezone;

            if (filled($timezone)) {
                config(['app.timezone' => $timezone]);
                date_default_timezone_set($timezone);
            }
        } catch (\Throwable) {
            //
        }
    }

    private function configureLocaleFromSettings(): void
    {
        try {
            if (! Schema::hasTable('settings')) {
                return;
            }

            $locale = Setting::current()->locale;

            if (filled($locale)) {
                LocaleManager::apply($locale);
            }
        } catch (\Throwable) {
            //
        }
    }

    private function configureRateLimiting(): void
    {
        RateLimiter::for('ticket-creation', fn (Request $request) => Limit::perMinute(10)->by($request->user()?->id ?: $request->ip()));

        RateLimiter::for('ticket-replies', fn (Request $request) => Limit::perMinute(30)->by($request->user()?->id ?: $request->ip()));

        RateLimiter::for('registrations', fn (Request $request) => Limit::perMinute(3)->by($request->ip()));

        RateLimiter::for('password-reset', fn (Request $request) => Limit::perMinute(3)->by($request->ip()));
    }
}
