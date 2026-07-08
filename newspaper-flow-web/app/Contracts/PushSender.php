<?php

namespace App\Contracts;

use App\Models\DeviceToken;
use App\Services\Push\PushMessage;
use App\Services\Push\PushResult;

/**
 * Sends a single push notification to one device token. Implementations are
 * platform-specific (APNs for iOS, FCM for Android). A NullPushSender is used
 * when a platform isn't configured, so the app runs fine without credentials.
 */
interface PushSender
{
    /** 'ios' | 'android' */
    public function platform(): string;

    public function send(DeviceToken $token, PushMessage $message): PushResult;
}
