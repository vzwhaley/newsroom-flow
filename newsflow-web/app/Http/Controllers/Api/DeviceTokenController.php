<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DeviceToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Registers/removes a device's APNs (iOS) or FCM (Android) push token. The
 * native apps POST here after permission is granted, and DELETE on sign-out.
 */
class DeviceTokenController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'platform' => ['required', 'string', Rule::in(['ios', 'android'])],
            'token'    => ['required', 'string', 'max:512'],
        ]);

        // A token is globally unique to one device; updateOrCreate re-points it
        // at the current user (e.g. after a device changes hands or re-login).
        DeviceToken::updateOrCreate(
            ['token' => $data['token']],
            [
                'user_id'      => $request->user()->id,
                'platform'     => $data['platform'],
                'last_used_at' => now(),
            ],
        );

        return response()->json(['registered' => true]);
    }

    public function destroy(Request $request): JsonResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string'],
        ]);

        $request->user()->deviceTokens()->where('token', $data['token'])->delete();

        return response()->json(['message' => 'Token removed.']);
    }
}
