<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Mockery;
use Tests\TestCase;

class SocialAuthTest extends TestCase
{
    use RefreshDatabase;

    private function fakeSocialUser(string $id, ?string $email, string $name = 'Social User', array $raw = []): void
    {
        $abstract = Mockery::mock(\Laravel\Socialite\Contracts\User::class);
        $abstract->shouldReceive('getId')->andReturn($id);
        $abstract->shouldReceive('getEmail')->andReturn($email);
        $abstract->shouldReceive('getName')->andReturn($name);
        $abstract->shouldReceive('getNickname')->andReturn(null);
        $abstract->shouldReceive('getRaw')->andReturn($raw);

        $provider = Mockery::mock(\Laravel\Socialite\Contracts\Provider::class);
        $provider->shouldReceive('stateless')->andReturnSelf();
        $provider->shouldReceive('scopes')->andReturnSelf();
        $provider->shouldReceive('user')->andReturn($abstract);

        Socialite::shouldReceive('driver')->andReturn($provider);
    }

    public function test_redirect_is_404_when_provider_not_configured(): void
    {
        config()->set('services.google.client_id', null);

        $this->get('/auth/google/redirect')->assertNotFound();
    }

    public function test_google_callback_creates_and_logs_in_new_verified_user(): void
    {
        config()->set('services.google.client_id', 'configured');
        $this->fakeSocialUser('g-123', 'newperson@example.com', 'New Person');

        $this->get('/auth/google/callback')->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticated();
        $user = User::where('email', 'newperson@example.com')->first();
        $this->assertNotNull($user);
        $this->assertSame('g-123', $user->google_id);
        $this->assertNotNull($user->email_verified_at); // Google verifies email
    }

    public function test_google_links_to_existing_account_with_same_email(): void
    {
        config()->set('services.google.client_id', 'configured');
        $existing = User::factory()->create(['email' => 'existing@example.com', 'google_id' => null]);

        $this->fakeSocialUser('g-999', 'existing@example.com');

        $this->get('/auth/google/callback')->assertRedirect();

        $this->assertSame('g-999', $existing->fresh()->google_id);
        $this->assertSame(1, User::where('email', 'existing@example.com')->count());
    }

    public function test_unverified_discord_email_does_not_take_over_account(): void
    {
        config()->set('services.discord.client_id', 'configured');
        User::factory()->create(['email' => 'victim@example.com']);

        // Discord returns the email but marks it unverified.
        $this->fakeSocialUser('d-1', 'victim@example.com', 'Imposter', ['verified' => false]);

        $this->get('/auth/discord/callback')->assertRedirect(route('login'));

        $this->assertGuest();
        $this->assertNull(User::where('email', 'victim@example.com')->first()->discord_id);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
