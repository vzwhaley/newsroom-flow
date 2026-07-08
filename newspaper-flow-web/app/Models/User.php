<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Cashier\Billable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use Billable, HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'refresh_hour',
        'timezone',
        'digest_enabled',
        'digest_new_only',
        'push_enabled',
        'blocked_sources',
        'watch_keywords',
        'google_id',
        'apple_id',
        'discord_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at'     => 'datetime',
            'password'              => 'hashed',
            'lifetime_purchased_at' => 'datetime',
            'lifetime_refunded_at'  => 'datetime',
            'digest_enabled'        => 'boolean',
            'digest_new_only'       => 'boolean',
            'digest_sent_at'        => 'datetime',
            'push_enabled'          => 'boolean',
            'push_sent_at'          => 'datetime',
            'watchlist_push_enabled' => 'boolean',
            'briefing_for'          => 'date',
            'blocked_sources'       => 'array',
            'watch_keywords'        => 'array',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function topics(): HasMany
    {
        return $this->hasMany(Topic::class)->where('kind', 'topic')->orderBy('position');
    }

    /**
     * Top-level topics only (categories + standalone topics), ordered.
     */
    public function topLevelTopics(): HasMany
    {
        return $this->hasMany(Topic::class)->where('kind', 'topic')->whereNull('parent_id')->orderBy('position');
    }

    /**
     * Local-area news feeds (city/state/ZIP or city/country). A separate
     * surface from chosen topics and outside the topic limit.
     */
    public function areas(): HasMany
    {
        return $this->hasMany(Topic::class)->where('kind', 'area')->orderBy('position');
    }

    public function savedArticles(): HasMany
    {
        return $this->hasMany(SavedArticle::class)->latest();
    }

    public function archivedArticles(): HasMany
    {
        return $this->hasMany(ArticleArchive::class)->latest('archived_at');
    }

    public function deviceTokens(): HasMany
    {
        return $this->hasMany(DeviceToken::class);
    }

    public function readingDays(): HasMany
    {
        return $this->hasMany(ReadingDay::class);
    }

    public function sharedArticles(): HasMany
    {
        return $this->hasMany(SharedArticle::class)->latest();
    }

    /*
    |--------------------------------------------------------------------------
    | Subscription / tier helpers — single source of truth for "what am I?"
    |--------------------------------------------------------------------------
    */

    /**
     * Has the user bought the one-time Lifetime Pro plan (and not refunded)?
     */
    public function hasLifetime(): bool
    {
        return ! is_null($this->lifetime_purchased_at)
            && is_null($this->lifetime_refunded_at);
    }

    /**
     * Is the user on any Pro tier (monthly, yearly, or lifetime)?
     */
    public function isPro(): bool
    {
        return $this->plan() === 'pro';
    }

    /**
     * Whether this user should be served ads. Pro (incl. lifetime) is ad-free.
     */
    public function showsAds(): bool
    {
        return ! $this->isPro();
    }

    /**
     * 'pro' or 'free'.
     */
    public function plan(): string
    {
        if ($this->hasLifetime()) {
            return 'pro';
        }

        if ($this->subscribed(config('billing.subscription_type'))) {
            return 'pro';
        }

        return 'free';
    }

    /**
     * The specific tier name a client should display:
     * 'lifetime' | 'yearly' | 'monthly' | null (free).
     */
    public function subscriptionTier(): ?string
    {
        if ($this->hasLifetime()) {
            return 'lifetime';
        }

        $subscription = $this->subscription(config('billing.subscription_type'));

        if (! $subscription || ! $subscription->valid()) {
            return null;
        }

        $price = $subscription->stripe_price;

        return match ($price) {
            config('billing.prices.annual')  => 'yearly',
            config('billing.prices.monthly') => 'monthly',
            default                          => 'monthly',
        };
    }

    /*
    |--------------------------------------------------------------------------
    | Topic limits — Free = 2 topics, Pro = unlimited
    |--------------------------------------------------------------------------
    */

    /**
     * Maximum number of topics this user may follow. null = unlimited.
     */
    public function topicLimit(): ?int
    {
        return $this->isPro() ? null : (int) config('billing.free_limits.topics', 2);
    }

    /**
     * Can the user add another topic right now?
     */
    public function canAddTopic(): bool
    {
        $limit = $this->topicLimit();

        if ($limit === null) {
            return true;
        }

        return $this->topics()->count() < $limit;
    }

    /**
     * How many topic slots remain. null = unlimited.
     */
    public function remainingTopicSlots(): ?int
    {
        $limit = $this->topicLimit();

        if ($limit === null) {
            return null;
        }

        return max(0, $limit - $this->topics()->count());
    }

    /*
    |--------------------------------------------------------------------------
    | Local-area limits — Free = 1 area (locked after grace), Pro = unlimited
    |--------------------------------------------------------------------------
    */

    /**
     * Maximum number of local areas this user may follow. null = unlimited.
     */
    public function areaLimit(): ?int
    {
        return $this->isPro() ? null : (int) config('billing.free_limits.areas', 1);
    }

    /**
     * Can the user add another local area right now?
     */
    public function canAddArea(): bool
    {
        $limit = $this->areaLimit();

        if ($limit === null) {
            return true;
        }

        return $this->areas()->count() < $limit;
    }

    /**
     * May the user edit/delete this area right now? Pro can always. Free may
     * only during the typo-grace window just after creating it — after that
     * their single area is permanent until they upgrade.
     */
    public function canModifyArea(Topic $area): bool
    {
        if ($this->isPro()) {
            return true;
        }

        $graceHours = (int) config('newspaperflow.areas.edit_grace_hours', 24);

        return $area->created_at !== null
            && $area->created_at->gt(now()->subHours($graceHours));
    }

    /**
     * Compact representation for the native apps' JSON API.
     */
    public function toApiArray(): array
    {
        return [
            'id'                => $this->id,
            'name'              => $this->name,
            'email'             => $this->email,
            'email_verified'    => ! is_null($this->email_verified_at),
            'plan'              => $this->plan(),
            'is_pro'            => $this->isPro(),
            'tier'              => $this->subscriptionTier(),
            'topic_limit'       => $this->topicLimit(),
            'topic_count'       => $this->topics()->count(),
            'area_limit'        => $this->areaLimit(),
            'area_count'        => $this->areas()->count(),
            'refresh_hour'      => $this->refresh_hour,
            'timezone'          => $this->timezone,
            'digest_enabled'    => (bool) $this->digest_enabled,
            'digest_new_only'   => (bool) $this->digest_new_only,
            'push_enabled'      => (bool) $this->push_enabled,
            'watchlist_push_enabled' => (bool) $this->watchlist_push_enabled,
            'watch_keywords'    => $this->watch_keywords ?? [],
            'blocked_sources'   => $this->blocked_sources ?? [],
            'reading'           => ReadingDay::statsFor($this),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Pro power features — blocked sources & keyword watchlist
    |--------------------------------------------------------------------------
    */

    /**
     * Is this article's publisher on the user's blocklist? Matches a blocklist
     * entry against both the source name and the URL host (case-insensitive
     * substring). Pro only — no-op for free accounts.
     */
    public function isSourceBlocked(?string $source, ?string $url = null): bool
    {
        if (! $this->isPro()) {
            return false;
        }

        $blocked = $this->blocked_sources ?: [];
        if (empty($blocked)) {
            return false;
        }

        $host = '';
        if ($url) {
            $host = strtolower(preg_replace('/^www\./', '', parse_url($url, PHP_URL_HOST) ?? ''));
        }
        $haystack = strtolower(trim(($source ?? '').' '.$host));

        foreach ($blocked as $entry) {
            $entry = strtolower(trim($entry));
            if ($entry !== '' && str_contains($haystack, $entry)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Which of the user's watch keywords this text matches (Pro). Returns the
     * list of matched keywords (empty if none / free).
     *
     * @return array<int, string>
     */
    public function watchMatches(string $headline, ?string $description = null): array
    {
        if (! $this->isPro()) {
            return [];
        }

        $keywords = $this->watch_keywords ?: [];
        if (empty($keywords)) {
            return [];
        }

        $haystack = mb_strtolower($headline.' '.($description ?? ''));
        $hits = [];

        foreach ($keywords as $word) {
            $word = trim($word);
            if ($word !== '' && str_contains($haystack, mb_strtolower($word))) {
                $hits[] = $word;
            }
        }

        return $hits;
    }
}
