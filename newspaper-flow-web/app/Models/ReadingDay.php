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

    /**
     * Everything the /stats page needs: current stats + longest-ever streak,
     * days active, and a day→reads heatmap covering the last $weeks weeks.
     *
     * @return array{streak:int, read_today:bool, total_reads:int,
     *               longest_streak:int, days_active:int,
     *               heatmap:array<string,int>, from:string, to:string}
     */
    public static function fullStatsFor(User $user, int $weeks = 26): array
    {
        $stats = static::statsFor($user);

        $tz = $user->timezone ?: config('app.timezone');
        $to = Carbon::now($tz)->startOfDay();
        $from = $to->copy()->subWeeks($weeks)->startOfWeek();

        $all = static::query()
            ->where('user_id', $user->id)
            ->orderBy('date')
            ->get(['date', 'reads']);

        // Longest streak ever: walk the full sorted day list.
        $longest = 0;
        $run = 0;
        $prev = null;
        foreach ($all as $row) {
            $run = ($prev !== null && $row->date->copy()->subDay()->isSameDay($prev)) ? $run + 1 : 1;
            $longest = max($longest, $run);
            $prev = $row->date;
        }

        $heatmap = $all
            ->filter(fn ($row) => $row->date->gte($from))
            ->mapWithKeys(fn ($row) => [$row->date->toDateString() => (int) $row->reads])
            ->all();

        return $stats + [
            'longest_streak' => $longest,
            'days_active'    => $all->count(),
            'heatmap'        => $heatmap,
            'from'           => $from->toDateString(),
            'to'             => $to->toDateString(),
        ];
    }
}
