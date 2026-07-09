<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * NewsroomFlow Pro billing — Stripe Checkout for new subscriptions (Monthly /
 * Yearly) and one-time Lifetime, plus the Stripe Billing Portal for managing
 * existing subscriptions. Laravel Cashier does the heavy lifting; webhooks
 * (cancellations, payment failures, refunds) are handled at POST
 * /stripe/webhook (Cashier) and our lifetime listeners.
 */
class BillingController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $invoices = [];
        if ($this->stripeConfigured() && $user->stripe_id) {
            try {
                $invoices = $user->invoices()->map(fn ($invoice) => [
                    'id'     => $invoice->id,
                    'date'   => $invoice->date()->toDateString(),
                    'total'  => $invoice->total(),
                    'status' => $invoice->status,
                ])->all();
            } catch (\Throwable $e) {
                // Stripe call failed — show no invoices rather than 500.
            }
        }

        $subscription = $user->subscription(config('billing.subscription_type'));

        $currentPeriodEnd = null;
        if ($subscription) {
            try {
                $periodEnd = $subscription->asStripeSubscription()->current_period_end ?? null;
                $currentPeriodEnd = $periodEnd ? date('Y-m-d', $periodEnd) : null;
            } catch (\Throwable $e) {
                // Leave null.
            }
        }

        return Inertia::render('Billing', [
            'plan'         => $user->plan(),
            'tier'         => $user->subscriptionTier(),
            'subscription' => $subscription ? [
                'status'             => $subscription->stripe_status,
                'on_grace_period'    => $subscription->onGracePeriod(),
                'canceled'           => $subscription->canceled(),
                'ends_at'            => optional($subscription->ends_at)->toDateString(),
                'current_period_end' => $currentPeriodEnd,
            ] : null,
            'invoices'         => $invoices,
            'stripeConfigured' => $this->stripeConfigured(),
            'pricesConfigured' => ! empty(config('billing.prices.monthly')),
            'hasLifetime'      => $user->hasLifetime(),
            'prices'           => [
                'monthly'  => config('billing.prices.monthly'),
                'annual'   => config('billing.prices.annual'),
                'lifetime' => config('billing.prices.lifetime'),
            ],
        ]);
    }

    /**
     * POST /billing/checkout — recurring subscription (monthly | annual).
     */
    public function checkout(Request $request)
    {
        if (! $this->stripeConfigured()) {
            return back()->withErrors(['billing' => 'Stripe is not configured yet. Add your keys to .env.']);
        }

        $plan = $request->input('plan', 'monthly');
        $priceId = $plan === 'annual'
            ? config('billing.prices.annual')
            : config('billing.prices.monthly');

        if (! $priceId) {
            return back()->withErrors(['billing' => "No Stripe Price ID configured for the {$plan} plan."]);
        }

        $checkout = $request->user()
            ->newSubscription(config('billing.subscription_type'), $priceId)
            ->allowPromotionCodes()
            ->checkout([
                'success_url' => route('billing.success').'?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url'  => route('billing'),
            ]);

        // Inertia's XHR can't follow a cross-origin 303 to checkout.stripe.com;
        // Inertia::location forces a hard browser navigation instead.
        return Inertia::location($checkout->url);
    }

    /**
     * POST /billing/lifetime — one-time Lifetime purchase (payment mode).
     * The Pro grant happens in HandleLifetimeCheckout on the Stripe webhook.
     */
    public function lifetime(Request $request)
    {
        if (! $this->stripeConfigured()) {
            return back()->withErrors(['billing' => 'Stripe is not configured yet. Add your keys to .env.']);
        }

        $priceId = config('billing.prices.lifetime');

        if (! $priceId) {
            return back()->withErrors(['billing' => 'No Stripe Price ID configured for the lifetime plan.']);
        }

        $checkout = $request->user()->checkout([$priceId => 1], [
            'mode'        => 'payment',
            'success_url' => route('billing.success').'?session_id={CHECKOUT_SESSION_ID}&kind=lifetime',
            'cancel_url'  => route('billing'),
            'automatic_payment_methods' => ['enabled' => true],
            'metadata'    => [
                'purchase' => 'lifetime',
                'user_id'  => (string) $request->user()->id,
            ],
        ]);

        return Inertia::location($checkout->url);
    }

    /**
     * GET /billing/success — post-checkout landing.
     */
    public function success(Request $request): RedirectResponse
    {
        $message = $request->query('kind') === 'lifetime'
            ? 'Thank you! Your NewsroomFlow Pro Lifetime purchase is confirmed — every Pro feature in the current version is unlocked, with no recurring billing.'
            : 'Welcome to NewsroomFlow Pro! Your subscription is active — enjoy unlimited topics.';

        return redirect()->route('billing')->with('success', $message);
    }

    /**
     * POST /billing/portal — Stripe Billing Portal.
     */
    public function portal(Request $request)
    {
        if (! $this->stripeConfigured()) {
            return back()->withErrors(['billing' => 'Stripe is not configured yet.']);
        }

        return Inertia::location(
            $request->user()->billingPortalUrl(route('billing')),
        );
    }

    private function stripeConfigured(): bool
    {
        return ! empty(config('cashier.secret')) && ! empty(config('cashier.key'));
    }
}
