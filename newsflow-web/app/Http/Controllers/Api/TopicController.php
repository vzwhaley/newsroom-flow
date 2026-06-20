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
            'id'        => $topic->id,
            'name'      => $topic->name,
            'parent_id' => $topic->parent_id,
            'articles'  => $topic->articles()->orderBy('position')->get()->map(fn ($a) => [
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
