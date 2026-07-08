<?php

namespace App\Listeners;

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Laravel\Cashier\Cashier;
use Laravel\Cashier\Events\WebhookReceived;

/**
 * Revokes Lifetime Pro when a Lifetime purchase is refunded.
 *
 * Listens for `charge.refunded`. The endpoint must be subscribed to this
 * event in the Stripe dashboard. Idempotent on re-delivery.
 */
class HandleLifetimeRefund
{
    public function handle(WebhookReceived $event): void
    {
        if ($event->payload['type'] !== 'charge.refunded') {
            return;
        }

        if (empty(config('cashier.webhook.secret'))) {
            Log::warning('Ignoring lifetime refund webhook: no STRIPE_WEBHOOK_SECRET configured.');

            return;
        }

        $charge = $event->payload['data']['object'] ?? [];

        $purchase = $charge['metadata']['purchase']
            ?? ($charge['payment_intent_metadata']['purchase'] ?? null);

        if ($purchase !== 'lifetime') {
            return;
        }

        $user = $this->resolveUser($charge);

        if (! $user || ! $user->hasLifetime()) {
            return; // idempotent / nothing to revoke
        }

        $user->forceFill([
            'lifetime_purchased_at' => null,
            'lifetime_refunded_at'  => Carbon::now(),
        ])->save();

        Log::info('Revoked Lifetime Pro after refund.', ['user_id' => $user->id]);
    }

    private function resolveUser(array $charge): ?User
    {
        if ($userId = ($charge['metadata']['user_id'] ?? null)) {
            if ($user = User::find($userId)) {
                return $user;
            }
        }

        if ($customer = ($charge['customer'] ?? null)) {
            return Cashier::findBillable($customer);
        }

        return null;
    }
}
