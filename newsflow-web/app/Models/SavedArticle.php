<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SavedArticle extends Model
{
    protected $fillable = [
        'user_id',
        'headline',
        'description',
        'url',
        'source',
        'image_url',
        'topic_name',
        'fingerprint',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
