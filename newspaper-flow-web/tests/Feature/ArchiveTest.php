<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Articles\TopicRefresher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Support\FakeArticleProvider;
use Tests\TestCase;

class ArchiveTest extends TestCase
{
    use RefreshDatabase;

    private function pro(): User
    {
        return User::factory()->create([
            'email_verified_at'     => Carbon::now(),
            'lifetime_purchased_at' => Carbon::now(),
        ]);
    }

    public function test_rotated_out_articles_are_archived_for_pro(): void
    {
        $user = $this->pro();
        $topic = $user->topics()->create(['name' => 'World News', 'position' => 0]);

        $provider = new FakeArticleProvider();
        (new TopicRefresher($provider))->refresh($topic);          // fill 1..12
        (new TopicRefresher($provider))->refresh($topic->fresh()); // 13..24 push 1..12 out

        $this->assertSame(12, $topic->fresh()->articles()->count());
        $this->assertSame(12, $user->archivedArticles()->count());
        $this->assertSame('World News', $user->archivedArticles()->first()->topic_name);
    }

    public function test_free_users_do_not_accrue_an_archive(): void
    {
        $user = User::factory()->create(); // free
        $topic = $user->topics()->create(['name' => 'World News', 'position' => 0]);

        $provider = new FakeArticleProvider();
        (new TopicRefresher($provider))->refresh($topic);
        (new TopicRefresher($provider))->refresh($topic->fresh());

        $this->assertSame(0, $user->archivedArticles()->count());
    }

    public function test_archive_page_lists_entries_for_pro(): void
    {
        $user = $this->pro();
        $user->archivedArticles()->create([
            'topic_name' => 'World News', 'headline' => 'An old story', 'description' => 'x',
            'url' => 'https://e.test/old', 'fingerprint' => 'old', 'archived_at' => Carbon::now(),
        ]);

        $this->actingAs($user)->get(route('archive'))->assertInertia(fn ($page) => $page
            ->component('Archive')
            ->where('locked', false)
            ->where('articles.data.0.headline', 'An old story')
        );
    }

    public function test_archive_is_locked_for_free_users(): void
    {
        $user = User::factory()->create(['email_verified_at' => Carbon::now()]);

        $this->actingAs($user)->get(route('archive'))->assertInertia(fn ($page) => $page
            ->component('Archive')
            ->where('locked', true)
        );
    }
}
