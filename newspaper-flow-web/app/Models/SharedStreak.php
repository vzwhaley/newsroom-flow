<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * A public streak brag card (/streak/{code}). Snapshots the streak at share
 * time; re-sharing with a bigger streak mints a fresh card.
 */
class SharedStreak extends Model
{
    protected $fillable = ['user_id', 'code', 'name', 'streak', 'total_reads', 'clicks'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Mint (or reuse) the card for this user + streak length.
     */
    public static function mint(User $user, int $streak, int $totalReads): self
    {
        $existing = static::query()
            ->where('user_id', $user->id)
            ->where('streak', $streak)
            ->first();

        if ($existing) {
            return $existing;
        }

        return static::create([
            'user_id'     => $user->id,
            'code'        => static::freshCode(),
            'name'        => $user->name,
            'streak'      => $streak,
            'total_reads' => $totalReads,
        ]);
    }

    private static function freshCode(): string
    {
        do {
            $code = Str::lower(Str::random(10));
        } while (static::where('code', $code)->exists());

        return $code;
    }

    public function shareUrl(): string
    {
        return url('/streak/'.$this->code);
    }
}
