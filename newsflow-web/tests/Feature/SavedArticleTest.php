<?php

namespace Tests\Feature;

use App\Models\SavedArticle;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class SavedArticleTest extends TestCase
{
    use RefreshDatabase;

    private function pro(): User
    {
        return User::factory()->create([
            'email_verified_at'     => Carbon::now(),
            'lifetime_purchased_at' => Carbon::now(),
        ]);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'headline'    => 'A saved story',
            'description' => 'Worth reading later.',
            'url'         => 'https://example.test/story',
            'source'      => 'Example',
            'image_url'   => null,
            'topic_name'  => 'World News',
        ], $overrides);
    }

    public function test_pro_user_can_save_an_article(): void
    {
        $user = $this->pro();

        $this->actingAs($user)->post(route('saved.store'), $this->payload())->assertRedirect();

        $this->assertSame(1, $user->savedArticles()->count());
    }

    public function test_saving_same_article_twice_is_idempotent(): void
    {
        $user = $this->pro();

        $this->actingAs($user)->post(route('saved.store'), $this->payload());
        $this->actingAs($user)->post(route('saved.store'), $this->payload());

        $this->assertSame(1, $user->savedArticles()->count());
    }

    public function test_free_user_cannot_save(): void
    {
        $user = User::factory()->create(['email_verified_at' => Carbon::now()]);

        $this->actingAs($user)->post(route('saved.store'), $this->payload())
            ->assertSessionHas('error');

        $this->assertSame(0, $user->savedArticles()->count());
    }

    public function test_user_can_unsave_their_own_article(): void
    {
        $user = $this->pro();
        $saved = $user->savedArticles()->create($this->payload() + ['fingerprint' => 'fp']);

        $this->actingAs($user)->delete(route('saved.destroy', $saved))->assertRedirect();

        $this->assertSame(0, $user->savedArticles()->count());
    }

    public function test_user_cannot_unsave_another_users_article(): void
    {
        $owner = $this->pro();
        $saved = $owner->savedArticles()->create($this->payload() + ['fingerprint' => 'fp']);
        $intruder = $this->pro();

        $this->actingAs($intruder)->delete(route('saved.destroy', $saved))->assertForbidden();

        $this->assertDatabaseHas('saved_articles', ['id' => $saved->id]);
    }
}
