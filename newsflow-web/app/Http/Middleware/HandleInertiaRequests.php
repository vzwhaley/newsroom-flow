<?php

namespace App\Http\Middleware;

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
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),

            'auth' => [
                'user' => $user ? [
                    'id'                  => $user->id,
                    'name'                => $user->name,
                    'email'               => $user->email,
                    'email_verified_at'   => $user->email_verified_at,
                    'plan'                => $user->plan(),
                    'is_pro'              => $user->isPro(),
                    'tier'                => $user->subscriptionTier(),
                    'topic_limit'         => $user->topicLimit(),
                    'topic_count'         => $user->topics()->count(),
                    'remaining_topics'    => $user->remainingTopicSlots(),
                    'refresh_hour'        => $user->refresh_hour,
                    'timezone'            => $user->timezone,
                    'digest_enabled'      => (bool) $user->digest_enabled,
                    'digest_new_only'     => (bool) $user->digest_new_only,
                ] : null,
            ],

            'flash' => [
                'status'  => fn () => $request->session()->get('status'),
                'success' => fn () => $request->session()->get('success'),
                'error'   => fn () => $request->session()->get('error'),
            ],

            // Which social sign-in buttons to render (only if configured).
            'socialProviders' => [
                'google'  => filled(config('services.google.client_id')),
                'apple'   => filled(config('services.apple.client_id')),
                'discord' => filled(config('services.discord.client_id')),
            ],

            // Marketing display prices (Stripe is the source of truth for the
            // actual charge — these are for UI only).
            'pricing' => [
                'monthly'  => config('billing.display_prices.monthly'),
                'annual'   => config('billing.display_prices.annual'),
                'lifetime' => config('billing.display_prices.lifetime'),
                'free_topics' => (int) config('billing.free_limits.topics'),
            ],
        ];
    }
}
