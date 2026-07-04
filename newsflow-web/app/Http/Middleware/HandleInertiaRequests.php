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

        // Web ad eligibility: ads run on the public marketing pages for EVERY
        // non-Pro visitor (anonymous prospects included), and on the signed-in
        // app pages for Free users. The bright line: Pro is 100% ad-free — a
        // Pro user receives no client and no slot IDs, so AdSlot.vue cannot
        // render anything for them. "Ad-eligible" = simply "not Pro".
        $adEligible = ! ($user && $user->isPro());
        $adsense = [
            'shows_ads'      => $adEligible,
            'client'         => $adEligible ? config('adsense.client') : null,
            'slots'          => $adEligible ? config('adsense.slots') : [],
            'use_google_cmp' => (bool) config('adsense.use_google_cmp'),
        ];

        return [
            ...parent::share($request),

            // AdSense config for the AdSlot.vue component. A null client / empty
            // slot map means "render nothing" — the default for Pro + unconfigured.
            'adsense' => $adsense,

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
                    'area_limit'          => $user->areaLimit(),
                    'area_count'          => $user->areas()->count(),
                    'refresh_hour'        => $user->refresh_hour,
                    'timezone'            => $user->timezone,
                    'digest_enabled'      => (bool) $user->digest_enabled,
                    'digest_new_only'     => (bool) $user->digest_new_only,
                    'blocked_sources'     => $user->blocked_sources ?? [],
                    'watch_keywords'      => $user->watch_keywords ?? [],
                    'watchlist_push_enabled' => (bool) $user->watchlist_push_enabled,
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
