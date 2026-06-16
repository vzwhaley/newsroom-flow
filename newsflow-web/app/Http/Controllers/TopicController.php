<?php

namespace App\Http\Controllers;

use App\Models\Topic;
use App\Services\Articles\TopicRefresher;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class TopicController extends Controller
{
    /**
     * Add a new topic. Free users are capped (config: free_limits.topics);
     * Pro users are unlimited. A freshly added topic is refreshed immediately
     * so it shows a full feed right away.
     */
    public function store(Request $request, TopicRefresher $refresher): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'name'      => ['required', 'string', 'max:80'],
            'parent_id' => ['nullable', 'integer'],
        ]);

        $name = trim($validated['name']);

        // Enforce the Free-tier topic cap server-side.
        if (! $user->canAddTopic()) {
            throw ValidationException::withMessages([
                'name' => "Free accounts can follow up to {$user->topicLimit()} topics. "
                    .'Upgrade to Pro for unlimited topics.',
            ]);
        }

        // Resolve + validate the parent (must be the user's own, top-level
        // topic — we only allow one level of nesting).
        $parent = null;
        if (! empty($validated['parent_id'])) {
            $parent = $user->topics()->whereKey($validated['parent_id'])->first();

            if (! $parent) {
                throw ValidationException::withMessages([
                    'parent_id' => 'That category no longer exists.',
                ]);
            }

            if ($parent->isChild()) {
                throw ValidationException::withMessages([
                    'parent_id' => 'You can only nest topics one level deep.',
                ]);
            }
        }

        // Prevent duplicates (case-insensitive) for this user.
        $exists = $user->topics()
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'name' => "You're already following \"{$name}\".",
            ]);
        }

        // Position within the topic's sibling set (top-level, or under parent).
        $siblingMax = $user->topics()
            ->where('parent_id', $parent?->id)
            ->max('position');

        $topic = $user->topics()->create([
            'name'      => $name,
            'parent_id' => $parent?->id,
            'position'  => ($siblingMax ?? -1) + 1,
        ]);

        // Populate the feed straight away so the user sees articles instantly.
        try {
            $refresher->refresh($topic);
        } catch (\Throwable $e) {
            report($e);
        }

        return back()->with('success', "Now following \"{$name}\".");
    }

    /**
     * Manually refresh a single topic (available to all; the daily auto
     * refresh covers everyone, this is the on-demand button).
     */
    public function refresh(Request $request, Topic $topic, TopicRefresher $refresher): RedirectResponse
    {
        $this->authorizeTopic($request, $topic);

        try {
            $stats = $refresher->refresh($topic);
            $msg = $stats['added'] > 0
                ? "Found {$stats['added']} new article(s) for \"{$topic->name}\"."
                : "No new stories for \"{$topic->name}\" right now — your feed is up to date.";

            return back()->with('success', $msg);
        } catch (\Throwable $e) {
            report($e);

            return back()->with('error', 'Could not refresh that topic right now. Please try again.');
        }
    }

    /**
     * Update a topic's muted keywords (Pro). Removes any currently-stored
     * articles that now match, then refills the feed so the mute takes effect
     * immediately.
     */
    public function mutes(Request $request, Topic $topic, TopicRefresher $refresher): RedirectResponse
    {
        $this->authorizeTopic($request, $topic);

        if (! $request->user()->isPro()) {
            return back()->with('error', 'Keyword muting is a Pro feature. Upgrade to filter your topics.');
        }

        $validated = $request->validate([
            'mute_keywords'   => ['present', 'array', 'max:50'],
            'mute_keywords.*' => ['string', 'max:50'],
        ]);

        // Normalise: trim, drop blanks, lowercase, dedupe.
        $keywords = collect($validated['mute_keywords'])
            ->map(fn ($k) => mb_strtolower(trim($k)))
            ->filter()
            ->unique()
            ->values()
            ->all();

        $topic->forceFill(['mute_keywords' => $keywords])->save();

        // Purge currently-stored articles that now match a mute.
        $removed = 0;
        foreach ($topic->articles()->get() as $article) {
            if ($topic->isMuted($article->headline, $article->description)) {
                $article->delete();
                $removed++;
            }
        }

        // Refill so the user still has a full feed.
        if ($removed > 0) {
            try {
                $refresher->refresh($topic->fresh());
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return back()->with('success', 'Mute keywords updated.');
    }

    /**
     * Reorder the user's topics. Expects an ordered array of topic ids.
     */
    public function reorder(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'order'   => ['required', 'array'],
            'order.*' => ['integer'],
        ]);

        $ids = $request->user()->topics()->pluck('id')->all();

        foreach ($validated['order'] as $position => $id) {
            if (in_array($id, $ids)) {
                Topic::whereKey($id)->update(['position' => $position]);
            }
        }

        return back();
    }

    public function destroy(Request $request, Topic $topic): RedirectResponse
    {
        $this->authorizeTopic($request, $topic);

        $name = $topic->name;
        $topic->delete();

        return back()->with('success', "Stopped following \"{$name}\".");
    }

    private function authorizeTopic(Request $request, Topic $topic): void
    {
        abort_unless($topic->user_id === $request->user()->id, 403);
    }
}
