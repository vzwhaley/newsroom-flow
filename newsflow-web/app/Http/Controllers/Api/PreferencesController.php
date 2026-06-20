<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * PUT /api/preferences — update the native client's news preferences:
 * refresh hour + timezone, the daily digest, plus the Pro power features
 * (keyword watchlist, blocked publishers, per-topic digest inclusion).
 */
class PreferencesController extends Controller
{
    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'refresh_hour'       => ['required', 'integer', 'between:0,23'],
            'timezone'           => ['required', 'string', Rule::in(timezone_identifiers_list())],
            'digest_enabled'     => ['required', 'boolean'],
            'digest_new_only'    => ['required', 'boolean'],
            'push_enabled'       => ['sometimes', 'boolean'],
            'watch_keywords'     => ['sometimes', 'array', 'max:100'],
            'watch_keywords.*'   => ['string', 'max:60'],
            'blocked_sources'    => ['sometimes', 'array', 'max:100'],
            'blocked_sources.*'  => ['string', 'max:100'],
            'digest_topic_ids'   => ['sometimes', 'array'],
            'digest_topic_ids.*' => ['integer'],
        ]);

        $user = $request->user();

        $clean = fn ($list) => collect($list ?? [])
            ->map(fn ($s) => trim($s))->filter()->unique()->values()->all();

        $fill = [
            'refresh_hour'    => $data['refresh_hour'],
            'timezone'        => $data['timezone'],
            'digest_enabled'  => $data['digest_enabled'],
            'digest_new_only' => $data['digest_new_only'],
        ];

        // Only touch the Pro power-feature lists when the client sends them, so
        // a plain preferences save doesn't wipe an existing watchlist/blocklist.
        if ($request->has('watch_keywords')) {
            $fill['watch_keywords'] = $clean($data['watch_keywords'] ?? []);
        }
        if ($request->has('blocked_sources')) {
            $fill['blocked_sources'] = $clean($data['blocked_sources'] ?? []);
        }
        if ($request->has('push_enabled')) {
            $fill['push_enabled'] = $data['push_enabled'];
        }

        $user->forceFill($fill)->save();

        // Per-topic digest inclusion: the submitted ids are the included ones.
        if ($request->has('digest_topic_ids')) {
            $included = $data['digest_topic_ids'] ?? [];
            $user->topics()->update(['include_in_digest' => false]);
            if (! empty($included)) {
                $user->topics()->whereIn('id', $included)->update(['include_in_digest' => true]);
            }
        }

        return response()->json(['user' => $user->fresh()->toApiArray()]);
    }
}
