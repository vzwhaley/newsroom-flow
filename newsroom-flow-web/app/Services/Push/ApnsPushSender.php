<?php

namespace App\Services\Push;

use App\Contracts\PushSender;
use App\Models\DeviceToken;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * iOS push via APNs HTTP/2 using token-based (.p8 key) auth. Builds an ES256
 * provider JWT, cached and reused (Apple allows up to ~1/hour). No third-party
 * SDK required.
 *
 * Requires (config/services.php → apns): key_id, team_id, bundle_id and the
 * contents of the AuthKey .p8. Until those are set the app uses NullPushSender.
 */
class ApnsPushSender implements PushSender
{
    public function __construct(
        private string $keyId,
        private string $teamId,
        private string $bundleId,
        private string $privateKey,
        private bool $production = false,
    ) {
    }

    public function platform(): string
    {
        return 'ios';
    }

    public function send(DeviceToken $token, PushMessage $message): PushResult
    {
        try {
            $jwt = $this->providerToken();
            if (! $jwt) {
                return PushResult::Failed;
            }

            $host = $this->production
                ? 'https://api.push.apple.com'
                : 'https://api.sandbox.push.apple.com';

            $payload = ['aps' => ['alert' => ['title' => $message->title, 'body' => $message->body], 'sound' => 'default']];
            foreach ($message->data as $key => $value) {
                $payload[$key] = (string) $value;
            }

            $response = Http::withHeaders([
                'authorization'  => 'bearer '.$jwt,
                'apns-topic'     => $this->bundleId,
                'apns-push-type' => 'alert',
            ])
                ->withOptions(['version' => 2.0])
                ->withBody((string) json_encode($payload), 'application/json')
                ->post("{$host}/3/device/{$token->token}");

            if ($response->successful()) {
                return PushResult::Sent;
            }

            $status = $response->status();
            $reason = (string) $response->json('reason', '');
            if ($status === 410 || in_array($reason, ['BadDeviceToken', 'Unregistered', 'DeviceTokenNotForTopic'], true)) {
                return PushResult::Invalid;
            }

            Log::warning('[push:apns] send failed', ['status' => $status, 'reason' => $reason]);

            return PushResult::Failed;
        } catch (\Throwable $e) {
            report($e);

            return PushResult::Failed;
        }
    }

    /** ES256-signed APNs provider token, cached well under Apple's 1-hour cap. */
    private function providerToken(): ?string
    {
        return Cache::remember('newsroomflow:apns:provider_token', now()->addMinutes(40), function () {
            $input = self::b64url((string) json_encode(['alg' => 'ES256', 'kid' => $this->keyId]))
                .'.'.self::b64url((string) json_encode(['iss' => $this->teamId, 'iat' => time()]));

            $der = '';
            if (! openssl_sign($input, $der, $this->privateKey, OPENSSL_ALGO_SHA256)) {
                return null;
            }

            // openssl gives a DER-encoded ECDSA signature; APNs ES256 wants the
            // raw 64-byte R||S (JOSE / P1363) form.
            $raw = self::derToRaw($der);
            if ($raw === null) {
                return null;
            }

            return $input.'.'.self::b64url($raw);
        });
    }

    /** Convert a DER ECDSA signature to fixed 64-byte R||S for ES256. */
    private static function derToRaw(string $der): ?string
    {
        $offset = 0;
        if (($der[$offset++] ?? '') !== "\x30") {
            return null; // not a SEQUENCE
        }
        // sequence length (skip; may be one byte for P-256)
        $seqLen = ord($der[$offset++] ?? "\x00");
        if ($seqLen & 0x80) {
            $offset += ($seqLen & 0x7f);
        }

        $read = function (string $der, int &$offset): ?string {
            if (($der[$offset++] ?? '') !== "\x02") {
                return null; // not an INTEGER
            }
            $len = ord($der[$offset++] ?? "\x00");
            $val = substr($der, $offset, $len);
            $offset += $len;

            // strip leading zero pad, then left-pad to 32 bytes
            $val = ltrim($val, "\x00");

            return str_pad($val, 32, "\x00", STR_PAD_LEFT);
        };

        $r = $read($der, $offset);
        $s = $read($der, $offset);
        if ($r === null || $s === null) {
            return null;
        }

        return $r.$s;
    }

    private static function b64url(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
