<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Articles\DailyBriefing;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * GET /api/briefing — the Pro AI daily briefing for the native apps.
 * Same semantics as the web endpoint: cached per user per local day.
 */
class BriefingController extends Controller
{
    public function show(Request $request, DailyBriefing $briefing): JsonResponse
    {
        $user = $request->user();

        if (! $user->isPro()) {
            return response()->json([
                'error' => 'The daily briefing is a Pro feature.',
            ], 403);
        }

        $result = $briefing->for($user);

        if (! $result) {
            return response()->json([
                'error' => 'No articles yet — add a topic and refresh to get your first briefing.',
            ], 404);
        }

        return response()->json($result);
    }
}
