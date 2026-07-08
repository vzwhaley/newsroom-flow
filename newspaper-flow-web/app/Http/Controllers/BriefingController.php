<?php

namespace App\Http\Controllers;

use App\Services\Articles\DailyBriefing;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * GET /briefing — the Pro AI "front page" briefing, generated once per user
 * per local day and cached on the user. XHR endpoint for the dashboard.
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
