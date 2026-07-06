<?php

namespace Tests\Feature;

use App\Contracts\ArticleProvider;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Support\FakeArticleProvider;
use Tests\TestCase;

class TopicHierarchyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->instance(ArticleProvider::class, new FakeArticleProvider());
    }

    private function pro(): User
    {
        return User::factory()->create([
            'email_verified_at'     => Carbon::now(),
            'lifetime_purchased_at' => Carbon::now(),
        ]);
    }

    public function test_user_can_nest_a_subtopic_under_a_parent(): void
    {
        $user = $this->pro();
        $parent = $user->topics()->create(['name' => 'Information Technology', 'position' => 0]);

        $this->actingAs($user)
            ->post(route('topics.store'), ['name' => 'OpenAI', 'parent_id' => $parent->id])
            ->assertRedirect();

        $child = $user->topics()->where('name', 'OpenAI')->first();
        $this->assertNotNull($child);
        $this->assertSame($parent->id, $child->parent_id);
        $this->assertSame(12, $child->articles()->count()); // child has its own feed
        $this->assertTrue($parent->fresh()->children->contains('name', 'OpenAI'));
    }

    public function test_cannot_nest_more_than_one_level_deep(): void
    {
        $user = $this->pro();
        $parent = $user->topics()->create(['name' => 'IT', 'position' => 0]);
        $child = $user->topics()->create(['name' => 'AI', 'parent_id' => $parent->id, 'position' => 0]);

        $this->actingAs($user)
            ->post(route('topics.store'), ['name' => 'GPT', 'parent_id' => $child->id])
            ->assertSessionHasErrors('parent_id');

        $this->assertDatabaseMissing('topics', ['name' => 'GPT']);
    }

    public function test_cannot_nest_under_another_users_topic(): void
    {
        $owner = $this->pro();
        $foreignParent = $owner->topics()->create(['name' => 'Theirs', 'position' => 0]);

        $user = $this->pro();

        $this->actingAs($user)
            ->post(route('topics.store'), ['name' => 'Mine', 'parent_id' => $foreignParent->id])
            ->assertSessionHasErrors('parent_id');
    }

    public function test_deleting_a_parent_cascades_to_children(): void
    {
        $user = $this->pro();
        $parent = $user->topics()->create(['name' => 'IT', 'position' => 0]);
        $child = $user->topics()->create(['name' => 'AI', 'parent_id' => $parent->id, 'position' => 0]);

        $this->actingAs($user)->delete(route('topics.destroy', $parent))->assertRedirect();

        $this->assertDatabaseMissing('topics', ['id' => $parent->id]);
        $this->assertDatabaseMissing('topics', ['id' => $child->id]);
    }

    public function test_dashboard_returns_nested_tree(): void
    {
        $user = $this->pro();
        $parent = $user->topics()->create(['name' => 'IT', 'position' => 0]);
        $user->topics()->create(['name' => 'AI', 'parent_id' => $parent->id, 'position' => 0]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertInertia(fn ($page) => $page
                ->component('Dashboard')
                ->has('topics', 1) // one top-level topic
                ->where('topics.0.name', 'IT')
                ->has('topics.0.children', 1)
                ->where('topics.0.children.0.name', 'AI')
            );
    }

    public function test_move_nests_a_top_level_topic_under_another(): void
    {
        $user = $this->pro();
        $parent = $user->topics()->create(['name' => 'IT', 'position' => 0]);
        $mover = $user->topics()->create(['name' => 'AI', 'position' => 1]);

        $this->actingAs($user)
            ->post(route('topics.move', $mover), ['parent_id' => $parent->id])
            ->assertRedirect();

        $this->assertSame($parent->id, $mover->fresh()->parent_id);
    }

    public function test_move_promotes_a_child_to_top_level(): void
    {
        $user = $this->pro();
        $parent = $user->topics()->create(['name' => 'IT', 'position' => 0]);
        $child = $user->topics()->create(['name' => 'AI', 'parent_id' => $parent->id, 'position' => 0]);

        $this->actingAs($user)
            ->post(route('topics.move', $child), ['parent_id' => null])
            ->assertRedirect();

        $this->assertNull($child->fresh()->parent_id);
    }

    public function test_move_cannot_nest_a_topic_that_has_children(): void
    {
        $user = $this->pro();
        $target = $user->topics()->create(['name' => 'Target', 'position' => 0]);
        $parentWithKids = $user->topics()->create(['name' => 'Parent', 'position' => 1]);
        $user->topics()->create(['name' => 'Kid', 'parent_id' => $parentWithKids->id, 'position' => 0]);

        $this->actingAs($user)
            ->post(route('topics.move', $parentWithKids), ['parent_id' => $target->id])
            ->assertSessionHas('error');

        // Unchanged — still top level.
        $this->assertNull($parentWithKids->fresh()->parent_id);
    }

    public function test_cannot_move_another_users_topic(): void
    {
        $owner = $this->pro();
        $theirs = $owner->topics()->create(['name' => 'Theirs', 'position' => 0]);

        $user = $this->pro();
        $mine = $user->topics()->create(['name' => 'Mine', 'position' => 0]);

        $this->actingAs($user)
            ->post(route('topics.move', $theirs), ['parent_id' => $mine->id])
            ->assertForbidden();
    }

    public function test_subtopic_counts_against_the_free_limit(): void
    {
        $user = User::factory()->create(['email_verified_at' => Carbon::now()]); // free, limit 2
        $parent = $user->topics()->create(['name' => 'IT', 'position' => 0]);
        $user->topics()->create(['name' => 'AI', 'parent_id' => $parent->id, 'position' => 0]);

        // That's 2 topics already; a third (even nested) is blocked.
        $this->actingAs($user)
            ->post(route('topics.store'), ['name' => 'OpenAI', 'parent_id' => $parent->id])
            ->assertSessionHasErrors('name');
    }
}
