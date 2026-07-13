<?php

namespace App\Providers;

use App\Contracts\ArticleProvider;
use App\Listeners\HandleLifetimeCheckout;
use App\Listeners\HandleLifetimeRefund;
use App\Services\Articles\HybridArticleProvider;
use App\Services\Articles\StubArticleProvider;
use App\Services\Push\ApnsPushSender;
use App\Services\Push\FcmPushSender;
use App\Services\Push\NullPushSender;
use App\Services\Push\PushNotifier;
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
            return match (config('newsroomflow.provider', 'hybrid')) {
                'stub'  => $app->make(StubArticleProvider::class),
                default => $app->make(HybridArticleProvider::class),
            };
        });

        // Push notifications: real senders when configured, else no-op senders
        // so token registration + the daily push command run without creds.
        $this->app->singleton(PushNotifier::class, function () {
            return new PushNotifier([
                'android' => $this->makeFcmSender(),
                'ios'     => $this->makeApnsSender(),
            ]);
        });
    }

    private function makeFcmSender(): \App\Contracts\PushSender
    {
        $projectId = config('services.fcm.project_id');
        $path = config('services.fcm.credentials');

        if ($projectId && $path && is_string($path) && is_file($path)) {
            $json = json_decode((string) file_get_contents($path), true);
            if (is_array($json)) {
                return new FcmPushSender($projectId, $json);
            }
        }

        return new NullPushSender('android');
    }

    private function makeApnsSender(): \App\Contracts\PushSender
    {
        $keyId = config('services.apns.key_id');
        $teamId = config('services.apns.team_id');
        $keyPath = config('services.apns.key_path');

        if ($keyId && $teamId && $keyPath && is_string($keyPath) && is_file($keyPath)) {
            return new ApnsPushSender(
                $keyId,
                $teamId,
                config('services.apns.bundle_id', 'com.newsroomflow.ios'),
                (string) file_get_contents($keyPath),
                (bool) config('services.apns.production', false),
            );
        }

        return new NullPushSender('ios');
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
