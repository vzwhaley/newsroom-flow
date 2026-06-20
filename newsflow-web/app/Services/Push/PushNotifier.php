<?php

namespace App\Services\Push;

use App\Contracts\PushSender;
use App\Models\User;
use Illuminate\Support\Carbon;

/**
 * Dispatches a PushMessage to all of a user's registered devices, routing each
 * token to the right platform sender and pruning tokens the push service
 * reports as dead.
 */
class PushNotifier
{
    /**
     * @param  array<string, PushSender>  $senders  keyed by platform ('ios', 'android')
     */
    public function __construct(private array $senders)
    {
    }

    /**
     * @return int  number of devices the message was accepted for
     */
    public function sendToUser(User $user, PushMessage $message): int
    {
        $delivered = 0;

        foreach ($user->deviceTokens()->get() as $token) {
            $sender = $this->senders[$token->platform] ?? null;
            if (! $sender) {
                continue;
            }

            switch ($sender->send($token, $message)) {
                case PushResult::Sent:
                    $token->forceFill(['last_used_at' => Carbon::now()])->save();
                    $delivered++;
                    break;
                case PushResult::Invalid:
                    $token->delete();
                    break;
                case PushResult::Failed:
                    // keep the token; next run retries
                    break;
            }
        }

        return $delivered;
    }
}
