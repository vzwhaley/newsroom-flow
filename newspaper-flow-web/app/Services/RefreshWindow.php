<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Carbon;

/**
 * Determines which users are "due" for their daily refresh/digest right now,
 * based on each user's chosen hour interpreted in their own timezone.
 */
class RefreshWindow
{
    public static function isUserDue(User $user, ?Carbon $now = null): bool
    {
        $now ??= Carbon::now('UTC');
        $tz = $user->timezone ?: 'UTC';

        try {
            $localHour = (int) $now->copy()->setTimezone($tz)->format('G');
        } catch (\Throwable $e) {
            $localHour = (int) $now->format('G');
        }

        return $localHour === (int) $user->refresh_hour;
    }

    /**
     * IDs of users who have at least one topic and are due this hour.
     *
     * @return array<int, int>
     */
    public static function dueUserIds(?Carbon $now = null): array
    {
        $now ??= Carbon::now('UTC');

        return User::query()
            ->whereHas('topics')
            ->get(['id', 'refresh_hour', 'timezone'])
            ->filter(fn (User $user) => self::isUserDue($user, $now))
            ->pluck('id')
            ->all();
    }
}
