<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * PUT /api/preferences — update the native client's news preferences.
 */
class PreferencesController extends Controller
{
    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'refresh_hour'    => ['required', 'integer', 'between:0,23'],
            'timezone'        => ['required', 'string', Rule::in(timezone_identifiers_list())],
            'digest_enabled'  => ['required', 'boolean'],
            'digest_new_only' => ['required', 'boolean'],
        ]);

        $request->user()->forceFill($data)->save();

        return response()->json(['user' => $request->user()->toApiArray()]);
    }
}
