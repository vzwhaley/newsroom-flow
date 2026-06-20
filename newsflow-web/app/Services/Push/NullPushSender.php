<?php

namespace App\Services\Push;

use App\Contracts\PushSender;
use App\Models\DeviceToken;
use Illuminate\Support\Facades\Log;

/**
 * Used when a platform has no push credentials configured. It logs what it
 * *would* have sent and reports success, so the rest of the system (token
 * registration, the daily push command, dead-token pruning) works end-to-end
 * without FCM/APNs set up.
 */
class NullPushSender implements PushSender
{
    public function __construct(private string $platform)
    {
    }

    public function platform(): string
    {
        return $this->platform;
    }

    public function send(DeviceToken $token, PushMessage $message): PushResult
    {
        Log::debug("[push:{$this->platform}:null] would send notification", [
            'token' => substr($token->token, 0, 12).'…',
            'title' => $message->title,
            'body'  => $message->body,
        ]);

        return PushResult::Sent;
    }
}
