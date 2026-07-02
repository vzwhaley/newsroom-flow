<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ResendVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_unverified_user_can_request_a_new_verification_email(): void
    {
        Notification::fake();
        $user = User::factory()->unverified()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/auth/resend-verification')
            ->assertOk()
            ->assertJson(['verified' => false]);

        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_already_verified_user_gets_a_no_op(): void
    {
        Notification::fake();
        $user = User::factory()->create(); // verified by default

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/auth/resend-verification')
            ->assertOk()
            ->assertJson(['verified' => true]);

        Notification::assertNothingSent();
    }

    public function test_guests_cannot_hit_the_endpoint(): void
    {
        $this->postJson('/api/auth/resend-verification')->assertUnauthorized();
    }
}
