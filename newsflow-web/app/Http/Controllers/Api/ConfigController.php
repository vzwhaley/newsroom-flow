<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Runtime config delivered to authenticated mobile clients. Serves the AdMob
 * ad config: Free-tier clients get the app ID + unit IDs (so they can render
 * banners); Pro clients get show=false and null units/app_id. Keeping the IDs
 * out of the Pro response means a Pro user literally can't load an ad — there's
 * no unit ID in their payload.
 *
 * (Web reads its own AdSense config from Inertia shared props instead.)
 *
 * Mirrors the sibling apps' /api/config pattern.
 */
class ConfigController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        $showsAds = $user->showsAds();

        return response()->json([
            'data' => [
                'plan'              => $user->plan(),
                'subscription_tier' => $user->subscriptionTier(),
                'ads' => [
                    'show'   => $showsAds,
                    'units'  => $showsAds ? config('admob.units') : null,
                    'app_id' => $showsAds ? config('admob.app_id') : null,
                ],
            ],
        ]);
    }
}
