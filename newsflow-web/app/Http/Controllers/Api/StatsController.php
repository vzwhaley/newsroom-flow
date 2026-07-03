<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ReadingDay;
use App\Models\SharedStreak;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * GET /api/stats + POST /api/stats/share — reading stats and streak brag
 * cards for the native apps (all tiers).
 */
class StatsController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        return response()->json(['stats' => ReadingDay::fullStatsFor($request->user())]);
    }

    public function share(Request $request): JsonResponse
    {
        $stats = ReadingDay::statsFor($request->user());

        if ($stats['streak'] < 1) {
            return response()->json(['message' => 'Read an article to start a streak first!'], 422);
        }

        $card = SharedStreak::mint($request->user(), $stats['streak'], $stats['total_reads']);

        return response()->json(['code' => $card->code, 'url' => $card->shareUrl()]);
    }
}
