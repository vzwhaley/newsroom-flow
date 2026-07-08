<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArticleArchive extends Model
{
    protected $fillable = [
        'user_id',
        'topic_name',
        'headline',
        'description',
        'url',
        'source',
        'image_url',
        'fingerprint',
        'published_at',
        'archived_at',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'archived_at'  => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
