<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PreferencesController extends Controller
{
    /**
     * Update the user's news preferences: refresh hour + timezone, and the
     * daily email digest (on/off, new-only, and which topics to include).
     */
    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'refresh_hour'       => ['required', 'integer', 'between:0,23'],
            'timezone'           => ['required', 'string', Rule::in(timezone_identifiers_list())],
            'digest_enabled'     => ['required', 'boolean'],
            'digest_new_only'    => ['required', 'boolean'],
            'watchlist_push_enabled' => ['sometimes', 'boolean'],
            'digest_topic_ids'   => ['array'],
            'digest_topic_ids.*' => ['integer'],
            'blocked_sources'    => ['array', 'max:100'],
            'blocked_sources.*'  => ['string', 'max:100'],
            'watch_keywords'     => ['array', 'max:100'],
            'watch_keywords.*'   => ['string', 'max:60'],
        ]);

        $clean = fn ($list) => collect($list ?? [])
            ->map(fn ($s) => trim($s))->filter()->unique()->values()->all();

        $fill = [
            'refresh_hour'    => $validated['refresh_hour'],
            'timezone'        => $validated['timezone'],
            'digest_enabled'  => $validated['digest_enabled'],
            'digest_new_only' => $validated['digest_new_only'],
            'blocked_sources' => $clean($validated['blocked_sources'] ?? []),
            'watch_keywords'  => $clean($validated['watch_keywords'] ?? []),
        ];

        if ($request->has('watchlist_push_enabled')) {
            $fill['watchlist_push_enabled'] = $validated['watchlist_push_enabled'];
        }

        $user->forceFill($fill)->save();

        // Per-topic digest inclusion: the submitted ids are the included ones.
        if ($request->has('digest_topic_ids')) {
            $included = $validated['digest_topic_ids'] ?? [];
            $user->topics()->update(['include_in_digest' => false]);
            if (! empty($included)) {
                $user->topics()->whereIn('id', $included)->update(['include_in_digest' => true]);
            }
        }

        return back()->with('success', 'Your news preferences were saved.');
    }
}
