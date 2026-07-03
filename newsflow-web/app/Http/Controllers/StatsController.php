<?php

namespace App\Http\Controllers;

use App\Models\ReadingDay;
use App\Models\SharedStreak;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Reading stats (all tiers): the /stats heatmap page, the streak brag-card
 * minting endpoint, and the public /streak/{code} card.
 */
class StatsController extends Controller
{
    /**
     * GET /stats — heatmap + streak stats page.
     */
    public function show(Request $request): Response
    {
        return Inertia::render('Stats', [
            'stats' => ReadingDay::fullStatsFor($request->user()),
        ]);
    }

    /**
     * POST /stats/share — mint (or reuse) the public brag card for the
     * user's current streak.
     */
    public function share(Request $request): JsonResponse
    {
        $stats = ReadingDay::statsFor($request->user());

        if ($stats['streak'] < 1) {
            return response()->json([
                'error' => 'Read an article to start a streak first!',
            ], 422);
        }

        $card = SharedStreak::mint($request->user(), $stats['streak'], $stats['total_reads']);

        return response()->json([
            'code' => $card->code,
            'url'  => $card->shareUrl(),
        ]);
    }

    /**
     * GET /streak/{code} — the public, server-rendered brag card.
     */
    public function card(string $code): View
    {
        $card = SharedStreak::where('code', $code)->firstOrFail();

        $card->increment('clicks');

        return view('streak', ['card' => $card]);
    }
}
