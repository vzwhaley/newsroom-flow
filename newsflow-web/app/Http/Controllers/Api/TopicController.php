<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Topic;
use App\Services\Articles\TopicRefresher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class TopicController extends Controller
{
    /**
     * POST /api/topics — add a topic (optionally under a parent), fill it,
     * and return it with its articles.
     */
    public function store(Request $request, TopicRefresher $refresher): JsonResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'name'      => ['required', 'string', 'max:80'],
            'parent_id' => ['nullable', 'integer'],
        ]);

        $name = trim($data['name']);

        if (! $user->canAddTopic()) {
            throw ValidationException::withMessages([
                'name' => "Free accounts can follow up to {$user->topicLimit()} topics. Upgrade to Pro for unlimited topics.",
            ]);
        }

        $parent = null;
        if (! empty($data['parent_id'])) {
            $parent = $user->topics()->whereKey($data['parent_id'])->first();
            if (! $parent || $parent->isChild()) {
                throw ValidationException::withMessages(['parent_id' => 'Invalid parent topic.']);
            }
        }

        if ($user->topics()->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])->exists()) {
            throw ValidationException::withMessages(['name' => "You're already following \"{$name}\"."]);
        }

        $topic = $user->topics()->create([
            'name'      => $name,
            'parent_id' => $parent?->id,
            'position'  => ($user->topics()->where('parent_id', $parent?->id)->max('position') ?? -1) + 1,
        ]);

        try {
            $refresher->refresh($topic);
        } catch (\Throwable $e) {
            report($e);
        }

        return response()->json(['topic' => $this->topicWithArticles($topic->fresh())], 201);
    }

    /**
     * POST /api/topics/{topic}/refresh
     */
    public function refresh(Request $request, Topic $topic, TopicRefresher $refresher): JsonResponse
    {
        $this->authorizeTopic($request, $topic);

        try {
            $stats = $refresher->refresh($topic);
        } catch (\Throwable $e) {
            report($e);

            return response()->json(['message' => 'Could not refresh right now.'], 503);
        }

        return response()->json([
            'topic' => $this->topicWithArticles($topic->fresh()),
            'added' => $stats['added'],
        ]);
    }

    /**
     * PATCH /api/topics/{topic}/mutes — set the topic's muted keywords (Pro).
     * Purges currently-stored articles that now match, then refills the feed
     * so the mute takes effect immediately.
     */
    public function mutes(Request $request, Topic $topic, TopicRefresher $refresher): JsonResponse
    {
        $this->authorizeTopic($request, $topic);

        if (! $request->user()->isPro()) {
            return response()->json(['message' => 'Keyword muting is a Pro feature.'], 403);
        }

        $data = $request->validate([
            'mute_keywords'   => ['present', 'array', 'max:50'],
            'mute_keywords.*' => ['string', 'max:50'],
        ]);

        $keywords = collect($data['mute_keywords'])
            ->map(fn ($k) => mb_strtolower(trim($k)))
            ->filter()->unique()->values()->all();

        $topic->forceFill(['mute_keywords' => $keywords])->save();

        // Purge stored articles that now match a mute, then refill.
        $removed = 0;
        foreach ($topic->articles()->get() as $article) {
            if ($topic->isMuted($article->headline, $article->description)) {
                $article->delete();
                $removed++;
            }
        }
        if ($removed > 0) {
            try {
                $refresher->refresh($topic->fresh());
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return response()->json(['topic' => $this->topicWithArticles($topic->fresh())]);
    }

    /**
     * PATCH /api/topics/{topic}/digest — include/exclude a topic from the
     * user's daily email digest.
     */
    public function digest(Request $request, Topic $topic): JsonResponse
    {
        $this->authorizeTopic($request, $topic);

        $data = $request->validate([
            'include_in_digest' => ['required', 'boolean'],
        ]);

        $topic->forceFill(['include_in_digest' => $data['include_in_digest']])->save();

        return response()->json(['topic' => $this->topicWithArticles($topic->fresh())]);
    }

    /**
     * POST /api/topics/{topic}/read-all — mark every article in a topic read.
     */
    public function markAllRead(Request $request, Topic $topic): JsonResponse
    {
        $this->authorizeTopic($request, $topic);

        $count = $topic->articles()->whereNull('read_at')->update(['read_at' => now()]);

        return response()->json(['marked' => $count]);
    }

    /**
     * POST /api/topics/reorder — persist a new top-to-bottom topic order.
     * Expects an ordered array of the user's topic ids.
     */
    public function reorder(Request $request): JsonResponse
    {
        $data = $request->validate([
            'order'   => ['required', 'array'],
            'order.*' => ['integer'],
        ]);

        $ids = $request->user()->topics()->pluck('id')->all();

        foreach ($data['order'] as $position => $id) {
            if (in_array($id, $ids, true)) {
                Topic::whereKey($id)->update(['position' => $position]);
            }
        }

        return response()->json(['message' => 'Order saved.']);
    }

    /**
     * DELETE /api/topics/{topic}
     */
    public function destroy(Request $request, Topic $topic): JsonResponse
    {
        $this->authorizeTopic($request, $topic);
        $topic->delete();

        return response()->json(['message' => 'Topic removed.']);
    }

    private function topicWithArticles(Topic $topic): array
    {
        return [
            'id'                => $topic->id,
            'name'              => $topic->name,
            'parent_id'         => $topic->parent_id,
            'mute_keywords'     => $topic->mute_keywords ?? [],
            'include_in_digest' => (bool) $topic->include_in_digest,
            'articles'          => $topic->articles()->orderBy('position')->get()->map(fn ($a) => [
                'id'           => $a->id,
                'headline'     => $a->headline,
                'description'  => $a->description,
                'url'          => $a->url,
                'source'       => $a->source,
                'image_url'    => $a->image_url,
                'fingerprint'  => $a->fingerprint,
                'published_at' => $a->published_at?->toIso8601String(),
                'is_read'      => ! is_null($a->read_at),
                'tldr'         => $a->tldr,
            ])->all(),
        ];
    }

    private function authorizeTopic(Request $request, Topic $topic): void
    {
        abort_unless($topic->user_id === $request->user()->id, 403);
    }
}
