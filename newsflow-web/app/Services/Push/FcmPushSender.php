<?php

namespace App\Services\Push;

use App\Contracts\PushSender;
use App\Models\DeviceToken;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Android push via Firebase Cloud Messaging HTTP v1. Authenticates with a
 * service-account JSON by minting a short-lived OAuth access token (RS256 JWT
 * → token endpoint), cached for reuse. No third-party SDK required.
 *
 * Requires (config/services.php → fcm): a project_id and the decoded
 * service-account credentials (client_email + private_key). Until those are
 * set the app uses NullPushSender instead.
 */
class FcmPushSender implements PushSender
{
    /**
     * @param  array<string, mixed>  $credentials  decoded service-account JSON
     */
    public function __construct(
        private string $projectId,
        private array $credentials,
    ) {
    }

    public function platform(): string
    {
        return 'android';
    }

    public function send(DeviceToken $token, PushMessage $message): PushResult
    {
        try {
            $accessToken = $this->accessToken();
            if (! $accessToken) {
                return PushResult::Failed;
            }

            $response = Http::withToken($accessToken)
                ->acceptJson()
                ->post("https://fcm.googleapis.com/v1/projects/{$this->projectId}/messages:send", [
                    'message' => [
                        'token'        => $token->token,
                        'notification' => ['title' => $message->title, 'body' => $message->body],
                        'data'         => array_map('strval', $message->data),
                        'android'      => ['priority' => 'high'],
                    ],
                ]);

            if ($response->successful()) {
                return PushResult::Sent;
            }

            $status = $response->status();
            $error = (string) $response->json('error.status', '');
            if ($status === 404 || in_array($error, ['UNREGISTERED', 'INVALID_ARGUMENT'], true)) {
                return PushResult::Invalid;
            }

            Log::warning('[push:fcm] send failed', ['status' => $status, 'body' => $response->body()]);

            return PushResult::Failed;
        } catch (\Throwable $e) {
            report($e);

            return PushResult::Failed;
        }
    }

    /** OAuth2 access token for FCM, cached just under its 1-hour lifetime. */
    private function accessToken(): ?string
    {
        return Cache::remember('newsflow:fcm:access_token', now()->addMinutes(50), function () {
            $now = time();
            $jwt = $this->signRs256(
                ['alg' => 'RS256', 'typ' => 'JWT'],
                [
                    'iss'   => $this->credentials['client_email'] ?? '',
                    'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
                    'aud'   => 'https://oauth2.googleapis.com/token',
                    'iat'   => $now,
                    'exp'   => $now + 3600,
                ],
                (string) ($this->credentials['private_key'] ?? ''),
            );
            if (! $jwt) {
                return null;
            }

            $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion'  => $jwt,
            ]);

            return $response->successful() ? $response->json('access_token') : null;
        });
    }

    /**
     * @param  array<string, mixed>  $header
     * @param  array<string, mixed>  $claims
     */
    private function signRs256(array $header, array $claims, string $privateKey): ?string
    {
        if ($privateKey === '') {
            return null;
        }

        $input = self::b64url((string) json_encode($header)).'.'.self::b64url((string) json_encode($claims));
        $signature = '';
        if (! openssl_sign($input, $signature, $privateKey, OPENSSL_ALGO_SHA256)) {
            return null;
        }

        return $input.'.'.self::b64url($signature);
    }

    private static function b64url(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
