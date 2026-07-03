<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * A branded public share link (/s/{code}) for an article. Snapshots the
 * article fields at share time so the card keeps working after the story
 * rotates out of the user's feed.
 */
class SharedArticle extends Model
{
    protected $fillable = [
        'user_id',
        'code',
        'headline',
        'description',
        'url',
        'source',
        'image_url',
        'topic_name',
        'clicks',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Mint (or reuse) a share link for this user + article URL. Re-sharing
     * the same story returns the same code.
     */
    public static function mint(User $user, array $article): self
    {
        $existing = static::query()
            ->where('user_id', $user->id)
            ->where('url', $article['url'])
            ->first();

        if ($existing) {
            return $existing;
        }

        return static::create([
            'user_id'     => $user->id,
            'code'        => static::freshCode(),
            'headline'    => Str::limit($article['headline'], 490),
            'description' => $article['description'] ?? null,
            'url'         => $article['url'],
            'source'      => $article['source'] ?? null,
            'image_url'   => $article['image_url'] ?? null,
            'topic_name'  => $article['topic_name'] ?? null,
        ]);
    }

    private static function freshCode(): string
    {
        do {
            $code = Str::lower(Str::random(10));
        } while (static::where('code', $code)->exists());

        return $code;
    }

    /**
     * The public URL for this share link.
     */
    public function shareUrl(): string
    {
        return url('/s/'.$this->code);
    }
}
