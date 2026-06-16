<?php

namespace App\Http\Controllers;

use App\Models\SavedArticle;
use App\Support\FetchedArticle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Saved ("read later") articles — a Pro feature. Snapshots the article so it
 * survives the daily feed rotation.
 */
class SavedArticleController extends Controller
{
    public function index(Request $request): Response
    {
        $saved = $request->user()->savedArticles()
            ->get()
            ->map(fn ($a) => [
                'id'          => $a->id,
                'headline'    => $a->headline,
                'description' => $a->description,
                'url'         => $a->url,
                'source'      => $a->source,
                'image_url'   => $a->image_url,
                'topic_name'  => $a->topic_name,
                'saved_at'    => $a->created_at?->toIso8601String(),
            ]);

        return Inertia::render('Saved', [
            'articles' => $saved,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        // Saving is a Pro feature.
        if (! $user->isPro()) {
            return back()->with('error', 'Saving articles is a Pro feature. Upgrade for unlimited saves.');
        }

        $validated = $request->validate([
            'headline'    => ['required', 'string', 'max:512'],
            'description' => ['nullable', 'string'],
            'url'         => ['required', 'url', 'max:2048'],
            'source'      => ['nullable', 'string', 'max:255'],
            'image_url'   => ['nullable', 'string', 'max:2048'],
            'topic_name'  => ['nullable', 'string', 'max:255'],
        ]);

        $fingerprint = FetchedArticle::fingerprintForUrl($validated['url'])
            ?: hash('sha256', $validated['headline']);

        // Idempotent: saving the same story twice is a no-op.
        $user->savedArticles()->firstOrCreate(
            ['fingerprint' => $fingerprint],
            $validated,
        );

        return back()->with('success', 'Saved to read later.');
    }

    public function destroy(Request $request, SavedArticle $saved): RedirectResponse
    {
        abort_unless($saved->user_id === $request->user()->id, 403);

        $saved->delete();

        return back()->with('success', 'Removed from saved.');
    }
}
