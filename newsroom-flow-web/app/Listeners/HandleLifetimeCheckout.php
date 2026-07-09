<?php

namespace App\Listeners;

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Laravel\Cashier\Cashier;
use Laravel\Cashier\Events\WebhookReceived;

/**
 * Grants Lifetime Pro when Stripe confirms a one-time Lifetime purchase.
 *
 * The Lifetime plan is bought via a payment-mode Checkout session (see
 * BillingController::lifetime), so there is no Cashier subscription to key
 * off. Instead we listen for `checkout.session.completed` and set
 * lifetime_purchased_at on the matching user.
 *
 * SECURITY: Cashier only fires WebhookReceived for payloads whose signature
 * it has already verified against STRIPE_WEBHOOK_SECRET. We additionally fail
 * closed if no webhook secret is configured.
 */
class HandleLifetimeCheckout
{
    public function handle(WebhookReceived $event): void
    {
        if ($event->payload['type'] !== 'checkout.session.completed') {
            return;
        }

        if (empty(config('cashier.webhook.secret'))) {
            Log::warning('Ignoring lifetime checkout webhook: no STRIPE_WEBHOOK_SECRET configured.');

            return;
        }

        $session = $event->payload['data']['object'] ?? [];

        $isLifetime = ($session['mode'] ?? null) === 'payment'
            && ($session['payment_status'] ?? null) === 'paid'
            && (($session['metadata']['purchase'] ?? null) === 'lifetime');

        if (! $isLifetime) {
            return;
        }

        $user = $this->resolveUser($session);

        if (! $user) {
            Log::warning('Lifetime checkout completed but no user resolved.', [
                'session' => $session['id'] ?? null,
            ]);

            return;
        }

        if ($user->hasLifetime()) {
            return; // idempotent on webhook re-delivery
        }

        $user->forceFill([
            'lifetime_purchased_at' => Carbon::now(),
            'lifetime_refunded_at'  => null,
        ])->save();

        Log::info('Granted Lifetime Pro.', ['user_id' => $user->id]);
    }

    private function resolveUser(array $session): ?User
    {
        if ($userId = ($session['metadata']['user_id'] ?? null)) {
            if ($user = User::find($userId)) {
                return $user;
            }
        }

        if ($customer = ($session['customer'] ?? null)) {
            return Cashier::findBillable($customer);
        }

        return null;
    }
}
