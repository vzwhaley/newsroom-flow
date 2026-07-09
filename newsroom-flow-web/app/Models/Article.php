<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Article extends Model
{
    protected $fillable = [
        'topic_id',
        'headline',
        'description',
        'url',
        'source',
        'image_url',
        'fingerprint',
        'popularity_score',
        'position',
        'published_at',
        'fetched_at',
        'read_at',
        'tldr',
    ];

    protected function casts(): array
    {
        return [
            'popularity_score' => 'float',
            'published_at'     => 'datetime',
            'fetched_at'       => 'datetime',
            'read_at'          => 'datetime',
        ];
    }

    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class);
    }
}
