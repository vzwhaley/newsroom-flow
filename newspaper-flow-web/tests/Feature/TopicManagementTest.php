<?php

namespace Tests\Feature;

use App\Contracts\ArticleProvider;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Support\FakeArticleProvider;
use Tests\TestCase;

class TopicManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Deterministic feed for every test in this file.
        $this->app->instance(ArticleProvider::class, new FakeArticleProvider());
    }

    public function test_user_can_add_a_topic_and_it_is_filled_with_twelve_articles(): void
    {
        $user = User::factory()->create(['email_verified_at' => Carbon::now()]);

        $this->actingAs($user)
            ->post(route('topics.store'), ['name' => 'World News'])
            ->assertRedirect();

        $topic = $user->topics()->first();
        $this->assertNotNull($topic);
        $this->assertSame('World News', $topic->name);
        $this->assertSame(12, $topic->articles()->count());
    }

    public function test_free_user_cannot_exceed_two_topics(): void
    {
        $user = User::factory()->create(['email_verified_at' => Carbon::now()]);
        $user->topics()->create(['name' => 'One', 'position' => 0]);
        $user->topics()->create(['name' => 'Two', 'position' => 1]);

        $this->actingAs($user)
            ->post(route('topics.store'), ['name' => 'Three'])
            ->assertSessionHasErrors('name');

        $this->assertSame(2, $user->topics()->count());
    }

    public function test_pro_user_can_add_many_topics(): void
    {
        $user = User::factory()->create([
            'email_verified_at'     => Carbon::now(),
            'lifetime_purchased_at' => Carbon::now(),
        ]);

        foreach (['A', 'B', 'C', 'D', 'E'] as $name) {
            $this->actingAs($user)->post(route('topics.store'), ['name' => $name]);
        }

        $this->assertSame(5, $user->topics()->count());
    }

    public function test_duplicate_topic_is_rejected_case_insensitively(): void
    {
        $user = User::factory()->create(['email_verified_at' => Carbon::now()]);
        $user->topics()->create(['name' => 'World News', 'position' => 0]);

        $this->actingAs($user)
            ->post(route('topics.store'), ['name' => 'world news'])
            ->assertSessionHasErrors('name');

        $this->assertSame(1, $user->topics()->count());
    }

    public function test_user_cannot_delete_another_users_topic(): void
    {
        $owner = User::factory()->create(['email_verified_at' => Carbon::now()]);
        $topic = $owner->topics()->create(['name' => 'Private', 'position' => 0]);

        $intruder = User::factory()->create(['email_verified_at' => Carbon::now()]);

        $this->actingAs($intruder)
            ->delete(route('topics.destroy', $topic))
            ->assertForbidden();

        $this->assertDatabaseHas('topics', ['id' => $topic->id]);
    }

    public function test_user_can_delete_their_own_topic(): void
    {
        $user = User::factory()->create(['email_verified_at' => Carbon::now()]);
        $topic = $user->topics()->create(['name' => 'Temp', 'position' => 0]);

        $this->actingAs($user)
            ->delete(route('topics.destroy', $topic))
            ->assertRedirect();

        $this->assertDatabaseMissing('topics', ['id' => $topic->id]);
    }

    public function test_guest_cannot_manage_topics(): void
    {
        $this->post(route('topics.store'), ['name' => 'X'])->assertRedirect(route('login'));
    }

    public function test_reorder_updates_positions(): void
    {
        $user = User::factory()->create(['email_verified_at' => Carbon::now()]);
        $a = $user->topics()->create(['name' => 'A', 'position' => 0]);
        $b = $user->topics()->create(['name' => 'B', 'position' => 1]);

        $this->actingAs($user)
            ->post(route('topics.reorder'), ['order' => [$b->id, $a->id]])
            ->assertRedirect();

        $this->assertSame(0, $b->fresh()->position);
        $this->assertSame(1, $a->fresh()->position);
    }
}
