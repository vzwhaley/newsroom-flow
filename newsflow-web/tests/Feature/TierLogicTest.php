<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TierLogicTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_user_is_free_with_two_topic_limit(): void
    {
        $user = User::factory()->create();

        $this->assertSame('free', $user->plan());
        $this->assertFalse($user->isPro());
        $this->assertSame(2, $user->topicLimit());
        $this->assertTrue($user->canAddTopic());
        $this->assertNull($user->subscriptionTier());
    }

    public function test_lifetime_user_is_pro_with_unlimited_topics(): void
    {
        $user = User::factory()->create([
            'lifetime_purchased_at' => Carbon::now(),
        ]);

        $this->assertSame('pro', $user->plan());
        $this->assertTrue($user->isPro());
        $this->assertTrue($user->hasLifetime());
        $this->assertNull($user->topicLimit());        // unlimited
        $this->assertSame('lifetime', $user->subscriptionTier());
    }

    public function test_refunded_lifetime_drops_back_to_free(): void
    {
        $user = User::factory()->create([
            'lifetime_purchased_at' => null,
            'lifetime_refunded_at'  => Carbon::now(),
        ]);

        $this->assertFalse($user->hasLifetime());
        $this->assertSame('free', $user->plan());
    }

    public function test_active_subscription_makes_user_pro(): void
    {
        $user = User::factory()->create();

        // Simulate an active Cashier subscription.
        DB::table('subscriptions')->insert([
            'user_id'      => $user->id,
            'type'         => config('billing.subscription_type'),
            'stripe_id'    => 'sub_test123',
            'stripe_status' => 'active',
            'stripe_price' => 'price_monthly_test',
            'quantity'     => 1,
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        $user->refresh()->load('subscriptions');

        $this->assertSame('pro', $user->plan());
        $this->assertTrue($user->isPro());
        $this->assertNull($user->topicLimit());
    }

    public function test_free_user_remaining_slots_counts_down(): void
    {
        $user = User::factory()->create();
        $this->assertSame(2, $user->remainingTopicSlots());

        $user->topics()->create(['name' => 'World News', 'position' => 0]);
        $this->assertSame(1, $user->remainingTopicSlots());

        $user->topics()->create(['name' => 'Technology', 'position' => 1]);
        $this->assertSame(0, $user->remainingTopicSlots());
        $this->assertFalse($user->canAddTopic());
    }
}
