<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

/**
 * Serves /ads.txt (AdSense — this website) and /app-ads.txt (AdMob — the mobile
 * apps, which list this site as their developer URL). Both are authorized-seller
 * declarations per the IAB ads.txt / app-ads.txt spec, served as plain text at
 * the domain root and built from the configured publisher IDs so they stay
 * correct the moment the IDs are set — no hardcoded ID, no stale static file.
 *
 * Without ads.txt, AdSense treats the inventory as unauthorized and many
 * advertisers won't bid — so this directly protects ad revenue.
 */
class AdsTxtController extends Controller
{
    /** Google's fixed certification-authority ID for ads.txt lines. */
    private const GOOGLE_CERT_ID = 'f08c47fec0942fa0';

    /** GET /ads.txt — AdSense (web). Publisher ID from ADSENSE_CLIENT. */
    public function ads(): Response
    {
        $pub = $this->publisherId(config('adsense.client'));

        return $this->plain($this->googleLine($pub));
    }

    /** GET /app-ads.txt — AdMob (apps). Publisher ID from the AdMob app ID. */
    public function appAds(): Response
    {
        $appId = config('admob.app_id.android') ?: config('admob.app_id.ios');
        $pub = $this->publisherId($appId);

        return $this->plain($this->googleLine($pub));
    }

    /**
     * Extract the `pub-XXXXXXXXXXXXXXXX` account ID from any Google
     * publisher/app identifier: `ca-pub-1234…`, `ca-app-pub-1234~5678`, or a
     * bare `pub-1234`. Returns null when nothing is configured.
     */
    private function publisherId(?string $raw): ?string
    {
        if ($raw && preg_match('/pub-(\d+)/', $raw, $m)) {
            return 'pub-'.$m[1];
        }

        return null;
    }

    private function googleLine(?string $pub): string
    {
        if (! $pub) {
            return "# No publisher ID configured yet. Set ADSENSE_CLIENT (web) "
                ."or ADMOB_APP_ID_* (apps) and this file populates automatically.\n";
        }

        return sprintf("google.com, %s, DIRECT, %s\n", $pub, self::GOOGLE_CERT_ID);
    }

    private function plain(string $body): Response
    {
        return response($body, 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }
}
