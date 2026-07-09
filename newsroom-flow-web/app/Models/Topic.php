<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Topic extends Model
{
    protected $fillable = [
        'user_id',
        'parent_id',
        'kind',
        'name',
        'query',
        'locality',
        'region',
        'postal_code',
        'country_code',
        'mute_keywords',
        'include_in_digest',
        'position',
        'last_refreshed_at',
        'last_new_articles_at',
    ];

    protected function casts(): array
    {
        return [
            'mute_keywords'        => 'array',
            'include_in_digest'    => 'boolean',
            'last_refreshed_at'    => 'datetime',
            'last_new_articles_at' => 'datetime',
        ];
    }

    /**
     * Does this article text match any of the topic's muted keywords?
     */
    public function isMuted(string $headline, ?string $description = null): bool
    {
        $keywords = $this->mute_keywords ?: [];

        if (empty($keywords)) {
            return false;
        }

        $haystack = mb_strtolower($headline.' '.($description ?? ''));

        foreach ($keywords as $word) {
            $word = mb_strtolower(trim($word));
            if ($word !== '' && str_contains($haystack, $word)) {
                return true;
            }
        }

        return false;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The parent category this topic is nested under (null = top-level).
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Topic::class, 'parent_id');
    }

    /**
     * Subtopics nested under this topic, ordered for display.
     */
    public function children(): HasMany
    {
        return $this->hasMany(Topic::class, 'parent_id')->orderBy('position');
    }

    public function isChild(): bool
    {
        return ! is_null($this->parent_id);
    }

    /**
     * A local-area feed (city/state/ZIP or city/country) rather than a
     * user-chosen keyword topic.
     */
    public function isArea(): bool
    {
        return $this->kind === 'area';
    }

    /**
     * The topic's feed, ordered top-to-bottom (position 0 = freshest/top).
     */
    public function articles(): HasMany
    {
        return $this->hasMany(Article::class)->orderBy('position');
    }

    /**
     * The effective search query — falls back to the display name.
     */
    public function searchQuery(): string
    {
        return $this->query ?: $this->name;
    }
}
