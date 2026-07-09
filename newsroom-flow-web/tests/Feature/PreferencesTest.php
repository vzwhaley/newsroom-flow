<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class PreferencesTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_update_refresh_hour_and_timezone(): void
    {
        $user = User::factory()->create(['email_verified_at' => Carbon::now()]);

        $this->actingAs($user)
            ->patch(route('preferences.update'), [
                'refresh_hour'    => 7,
                'timezone'        => 'America/Chicago',
                'digest_enabled'  => true,
                'digest_new_only' => false,
            ])
            ->assertRedirect();

        $user->refresh();
        $this->assertSame(7, $user->refresh_hour);
        $this->assertSame('America/Chicago', $user->timezone);
        $this->assertTrue($user->digest_enabled);
    }

    public function test_user_can_set_new_only_and_choose_digest_topics(): void
    {
        $user = User::factory()->create(['email_verified_at' => Carbon::now()]);
        $keep = $user->topics()->create(['name' => 'World News', 'position' => 0]);
        $drop = $user->topics()->create(['name' => 'Sports', 'position' => 1]);

        $this->actingAs($user)
            ->patch(route('preferences.update'), [
                'refresh_hour'     => 6,
                'timezone'         => 'UTC',
                'digest_enabled'   => true,
                'digest_new_only'  => true,
                'digest_topic_ids' => [$keep->id],
            ])
            ->assertRedirect();

        $this->assertTrue($user->fresh()->digest_new_only);
        $this->assertTrue($keep->fresh()->include_in_digest);
        $this->assertFalse($drop->fresh()->include_in_digest);
    }

    public function test_invalid_hour_is_rejected(): void
    {
        $user = User::factory()->create(['email_verified_at' => Carbon::now()]);

        $this->actingAs($user)
            ->patch(route('preferences.update'), ['refresh_hour' => 99, 'timezone' => 'UTC'])
            ->assertSessionHasErrors('refresh_hour');
    }

    public function test_invalid_timezone_is_rejected(): void
    {
        $user = User::factory()->create(['email_verified_at' => Carbon::now()]);

        $this->actingAs($user)
            ->patch(route('preferences.update'), ['refresh_hour' => 6, 'timezone' => 'Mars/Olympus'])
            ->assertSessionHasErrors('timezone');
    }
}
