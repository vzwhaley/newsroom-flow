<?php

namespace Tests\Support;

use App\Contracts\PushSender;
use App\Models\DeviceToken;
use App\Services\Push\PushMessage;
use App\Services\Push\PushResult;

/**
 * Records every message it "sends" so tests can assert on the daily-push
 * behaviour without real APNs/FCM. Tokens listed in $invalidTokens come back
 * Invalid so dead-token pruning can be exercised.
 */
class FakePushSender implements PushSender
{
    /** @var array<int, array{token: string, platform: string, message: PushMessage}> */
    public array $sent = [];

    /** @var array<int, string> */
    public array $invalidTokens = [];

    public function __construct(private string $platform = 'ios')
    {
    }

    public function platform(): string
    {
        return $this->platform;
    }

    public function send(DeviceToken $token, PushMessage $message): PushResult
    {
        if (in_array($token->token, $this->invalidTokens, true)) {
            return PushResult::Invalid;
        }

        $this->sent[] = ['token' => $token->token, 'platform' => $token->platform, 'message' => $message];

        return PushResult::Sent;
    }
}
