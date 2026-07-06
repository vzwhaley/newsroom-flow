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

        // Insert at the TOP of its sibling set (top-level, or under the parent):
        // push every existing sibling down one slot, then take position 0.
        $user->topics()
            ->where('parent_id', $parent?->id)
            ->increment('position');

        $topic = $user->topics()->create([
            'name'      => $name,
            'parent_id' => $parent?->id,
            'position'  => 0,
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
     * Mark every article in a topic as read.
     */
    public function markAllRead(Request $request, Topic $topic): RedirectResponse
    {
        $this->authorizeTopic($request, $topic);

        $topic->articles()->whereNull('read_at')->update(['read_at' => now()]);

        return back();
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

    /**
     * Re-parent a topic: nest it under another top-level topic, or promote it to
     * top level (parent_id = null). Backs both the sidebar drag-and-drop and the
     * per-topic "Move under…" menu. Enforces the one-level nesting rule.
     */
    public function move(Request $request, Topic $topic): RedirectResponse
    {
        $this->authorizeTopic($request, $topic);
        $user = $request->user();

        $validated = $request->validate([
            'parent_id' => ['nullable', 'integer'],
        ]);

        $newParentId = null;

        if (! empty($validated['parent_id'])) {
            $parent = $user->topics()->whereKey($validated['parent_id'])->first();

            if (! $parent) {
                return back()->with('error', 'That category no longer exists.');
            }
            if ($parent->id === $topic->id) {
                return back()->with('error', 'A topic can’t be nested under itself.');
            }
            if ($parent->isChild()) {
                return back()->with('error', 'You can only nest topics one level deep.');
            }
            if ($topic->children()->exists()) {
                return back()->with('error', 'Move or remove this category’s subtopics before nesting it.');
            }

            $newParentId = $parent->id;
        }

        // No-op if nothing changes.
        if ($topic->parent_id === $newParentId) {
            return back();
        }

        // Place at the end of the destination sibling group.
        $maxPos = $user->topics()->where('parent_id', $newParentId)->max('position');
        $topic->forceFill([
            'parent_id' => $newParentId,
            'position'  => ($maxPos ?? -1) + 1,
        ])->save();

        return back()->with('success', "Moved \"{$topic->name}\".");
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
