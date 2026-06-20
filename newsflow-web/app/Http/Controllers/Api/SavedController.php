<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SavedArticle;
use App\Support\FetchedArticle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SavedController extends Controller
{
    /**
     * GET /api/saved
     */
    public function index(Request $request): JsonResponse
    {
        $saved = $request->user()->savedArticles()->get()->map(fn ($a) => $this->payload($a));

        return response()->json(['saved' => $saved]);
    }

    /**
     * POST /api/saved — save an article (Pro).
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user->isPro()) {
            return response()->json(['message' => 'Saving articles is a Pro feature.'], 403);
        }

        $data = $request->validate([
            'headline'    => ['required', 'string', 'max:512'],
            'description' => ['nullable', 'string'],
            'url'         => ['required', 'url', 'max:2048'],
            'source'      => ['nullable', 'string', 'max:255'],
            'image_url'   => ['nullable', 'string', 'max:2048'],
            'topic_name'  => ['nullable', 'string', 'max:255'],
        ]);

        $fingerprint = FetchedArticle::fingerprintForUrl($data['url']) ?: hash('sha256', $data['headline']);

        $saved = $user->savedArticles()->firstOrCreate(['fingerprint' => $fingerprint], $data);

        return response()->json(['saved' => $this->payload($saved)], 201);
    }

    /**
     * DELETE /api/saved/{saved}
     */
    public function destroy(Request $request, SavedArticle $saved): JsonResponse
    {
        abort_unless($saved->user_id === $request->user()->id, 403);
        $saved->delete();

        return response()->json(['message' => 'Removed.']);
    }

    private function payload(SavedArticle $a): array
    {
        return [
            'id'          => $a->id,
            'headline'    => $a->headline,
            'description' => $a->description,
            'url'         => $a->url,
            'source'      => $a->source,
            'image_url'   => $a->image_url,
            'topic_name'  => $a->topic_name,
            'saved_at'    => $a->created_at?->toIso8601String(),
        ];
    }
}
