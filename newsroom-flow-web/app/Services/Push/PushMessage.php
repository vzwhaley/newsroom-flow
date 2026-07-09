<?php

namespace App\Services\Push;

/**
 * A platform-neutral push payload. `data` values are coerced to strings by the
 * senders (FCM data messages and APNs custom keys are string maps).
 */
class PushMessage
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        public string $title,
        public string $body,
        public array $data = [],
    ) {
    }
}
