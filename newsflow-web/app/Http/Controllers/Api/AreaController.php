<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AreaRequest;
use App\Jobs\DiscoverAreaLocalSources;
use App\Models\Topic;
use App\Services\Articles\LocationQuery;
use App\Services\Articles\TopicRefresher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Local-area news for the native apps. Mirrors the web AreaController:
 * Free = one area, permanent after the typo-grace window; Pro = unlimited.
 */
class AreaController extends Controller
{
    public function store(AreaRequest $request, LocationQuery $locator, TopicRefresher $refresher): JsonResponse
    {
        $user = $request->user();

        if (! $user->canAddArea()) {
            return response()->json([
                'message' => $user->isPro()
                    ? 'Could not add that area.'
                    : 'Free accounts include one local area. Upgrade to Pro to follow more places.',
            ], 422);
        }

        $resolved = $locator->resolve($request->validated());

        $area = $user->areas()->create([
            'kind'         => 'area',
            'name'         => $resolved['label'],
            'query'        => $resolved['query'],
            'locality'     => $resolved['locality'],
            'region'       => $resolved['region'],
            'postal_code'  => $resolved['postal_code'],
            'country_code' => $resolved['country_code'],
            'position'     => ($user->areas()->max('position') ?? -1) + 1,
        ]);

        try {
            $refresher->refresh($area);
        } catch (\Throwable $e) {
            report($e);
        }

        DiscoverAreaLocalSources::dispatch($area->id);

        return response()->json(['area' => $this->area($area->fresh(), $user)], 201);
    }

    public function update(AreaRequest $request, Topic $area, LocationQuery $locator, TopicRefresher $refresher): JsonResponse
    {
        $this->authorizeArea($request, $area);

        $user = $request->user();
        if (! $user->canModifyArea($area)) {
            return response()->json(['message' => 'This local area is locked. Upgrade to Pro to change it.'], 403);
        }

        $resolved = $locator->resolve($request->validated());

        $area->forceFill([
            'name'         => $resolved['label'],
            'query'        => $resolved['query'],
            'locality'     => $resolved['locality'],
            'region'       => $resolved['region'],
            'postal_code'  => $resolved['postal_code'],
            'country_code' => $resolved['country_code'],
        ])->save();

        $area->articles()->delete();
        try {
            $refresher->refresh($area->fresh());
        } catch (\Throwable $e) {
            report($e);
        }

        DiscoverAreaLocalSources::dispatch($area->id);

        return response()->json(['area' => $this->area($area->fresh(), $user)]);
    }

    public function destroy(Request $request, Topic $area): JsonResponse
    {
        $this->authorizeArea($request, $area);

        if (! $request->user()->canModifyArea($area)) {
            return response()->json(['message' => 'This local area is locked. Upgrade to Pro to change it.'], 403);
        }

        $area->delete();

        return response()->json(['deleted' => true]);
    }

    private function area(Topic $area, $user): array
    {
        return [
            'id'           => $area->id,
            'name'         => $area->name,
            'locality'     => $area->locality,
            'region'       => $area->region,
            'postal_code'  => $area->postal_code,
            'country_code' => $area->country_code,
            'locked'       => ! $user->canModifyArea($area),
            'last_refreshed_at' => $area->last_refreshed_at?->toIso8601String(),
            'articles'     => \App\Support\Region::order($area->articles()->orderBy('position')->get())
                ->map(fn ($a) => [
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

    private function authorizeArea(Request $request, Topic $area): void
    {
        abort_unless($area->user_id === $request->user()->id && $area->isArea(), 403);
    }
}
