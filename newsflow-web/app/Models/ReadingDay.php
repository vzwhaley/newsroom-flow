<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * One row per user per local day with at least one article read. Articles
 * rotate out of feeds daily, so streaks are tracked here rather than derived
 * from article read_at timestamps.
 */
class ReadingDay extends Model
{
    protected $fillable = ['user_id', 'date', 'reads'];

    protected function casts(): array
    {
        return [
            'date' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Record a read for the user's current local day (idempotent per read —
     * increments the day's counter, creating the row on first read).
     */
    public static function bump(User $user): void
    {
        // Carbon (not a Y-m-d string) so the lookup matches the stored
        // datetime format and repeat reads hit the same row.
        $today = Carbon::now($user->timezone ?: config('app.timezone'))->startOfDay();

        $row = static::firstOrCreate(
            ['user_id' => $user->id, 'date' => $today],
            ['reads' => 0],
        );

        $row->increment('reads');
    }

    /**
     * The user's reading stats: current streak (consecutive local days with a
     * read, anchored to today — or yesterday, so an unopened morning doesn't
     * break the streak), whether they've read today, and the all-time total.
     *
     * @return array{streak:int, read_today:bool, total_reads:int}
     */
    public static function statsFor(User $user): array
    {
        $tz = $user->timezone ?: config('app.timezone');
        $today = Carbon::now($tz)->startOfDay();

        $days = static::query()
            ->where('user_id', $user->id)
            ->orderByDesc('date')
            ->limit(400)
            ->pluck('date')
            ->map(fn ($d) => $d->toDateString())
            ->all();

        $readToday = in_array($today->toDateString(), $days, true);

        // Anchor on today if read, else yesterday (streak still alive).
        $cursor = $readToday ? $today->copy() : $today->copy()->subDay();

        $streak = 0;
        $set = array_flip($days);
        while (isset($set[$cursor->toDateString()])) {
            $streak++;
            $cursor->subDay();
        }

        return [
            'streak'      => $streak,
            'read_today'  => $readToday,
            'total_reads' => (int) static::where('user_id', $user->id)->sum('reads'),
        ];
    }
}
