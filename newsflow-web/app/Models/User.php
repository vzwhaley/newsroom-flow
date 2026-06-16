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
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function topics(): HasMany
    {
        return $this->hasMany(Topic::class)->orderBy('position');
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
}
