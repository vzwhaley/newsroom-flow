<?php

namespace App\Http\Controllers;

use App\Http\Requests\AreaRequest;
use App\Jobs\DiscoverAreaLocalSources;
use App\Models\Topic;
use App\Services\Articles\LocationQuery;
use App\Services\Articles\TopicRefresher;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Local-area news feeds. Free users get one area, permanent after a short
 * typo-grace window; Pro users add/edit/delete unlimited areas. Areas are a
 * separate surface from topics and don't count against the topic limit.
 */
class AreaController extends Controller
{
    public function store(AreaRequest $request, LocationQuery $locator, TopicRefresher $refresher): RedirectResponse
    {
        $user = $request->user();

        if (! $user->canAddArea()) {
            return back()->with('error', $user->isPro()
                ? 'Could not add that area.'
                : 'Free accounts include one local area. Upgrade to Pro to follow more places.');
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

        $this->safeRefresh($refresher, $area);

        // Learn this location's real local outlets in the background (no-op if
        // already curated/discovered or discovery is disabled).
        DiscoverAreaLocalSources::dispatch($area->id);

        return back()->with('success', "Now following local news for {$resolved['label']}.");
    }

    public function update(AreaRequest $request, Topic $area, LocationQuery $locator, TopicRefresher $refresher): RedirectResponse
    {
        $this->authorizeArea($request, $area);

        $user = $request->user();
        if (! $user->canModifyArea($area)) {
            return back()->with('error', 'This local area is locked. Upgrade to Pro to change your area.');
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

        // Location changed — clear the old feed and repopulate for the new place.
        $area->articles()->delete();
        $this->safeRefresh($refresher, $area->fresh());

        DiscoverAreaLocalSources::dispatch($area->id);

        return back()->with('success', "Updated your local area to {$resolved['label']}.");
    }

    public function destroy(Request $request, Topic $area): RedirectResponse
    {
        $this->authorizeArea($request, $area);

        if (! $request->user()->canModifyArea($area)) {
            return back()->with('error', 'This local area is locked. Upgrade to Pro to change your area.');
        }

        $label = $area->name;
        $area->delete();

        return back()->with('success', "Removed local news for {$label}.");
    }

    private function safeRefresh(TopicRefresher $refresher, Topic $area): void
    {
        try {
            $refresher->refresh($area);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    private function authorizeArea(Request $request, Topic $area): void
    {
        abort_unless($area->user_id === $request->user()->id && $area->isArea(), 403);
    }
}
