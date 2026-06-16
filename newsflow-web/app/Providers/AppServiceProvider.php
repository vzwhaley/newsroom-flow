<?php

namespace App\Providers;

use App\Contracts\ArticleProvider;
use App\Listeners\HandleLifetimeCheckout;
use App\Listeners\HandleLifetimeRefund;
use App\Services\Articles\HybridArticleProvider;
use App\Services\Articles\StubArticleProvider;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Laravel\Cashier\Events\WebhookReceived;
use SocialiteProviders\Manager\SocialiteWasCalled;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Resolve the configured article provider. 'hybrid' (recommended)
        // blends news APIs + popularity signals + an LLM and degrades to the
        // stub when nothing is configured; 'stub' is pure offline placeholder.
        $this->app->singleton(ArticleProvider::class, function ($app) {
            return match (config('newsflow.provider', 'hybrid')) {
                'stub'  => $app->make(StubArticleProvider::class),
                default => $app->make(HybridArticleProvider::class),
            };
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        // Grant / revoke Lifetime Pro in response to Stripe webhooks.
        Event::listen(WebhookReceived::class, HandleLifetimeCheckout::class);
        Event::listen(WebhookReceived::class, HandleLifetimeRefund::class);

        // Register the extra Socialite providers (Google is built-in).
        Event::listen(function (SocialiteWasCalled $event) {
            $event->extendSocialite('apple', \SocialiteProviders\Apple\Provider::class);
            $event->extendSocialite('discord', \SocialiteProviders\Discord\Provider::class);
        });
    }
}
