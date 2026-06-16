<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class EmailNotificationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_sends_email_verification_notification(): void
    {
        Notification::fake();

        $this->post(route('register'), [
            'name'                  => 'New User',
            'email'                 => 'newuser@example.com',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $user = User::where('email', 'newuser@example.com')->first();
        $this->assertNotNull($user);
        $this->assertNull($user->email_verified_at); // must verify first

        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_user_implements_must_verify_email(): void
    {
        $this->assertInstanceOf(
            \Illuminate\Contracts\Auth\MustVerifyEmail::class,
            new User(),
        );
    }

    public function test_forgot_password_sends_reset_link_notification(): void
    {
        Notification::fake();

        $user = User::factory()->create(['email' => 'reset@example.com']);

        $this->post(route('password.email'), ['email' => 'reset@example.com']);

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_unverified_user_is_redirected_from_dashboard(): void
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertRedirect(route('verification.notice'));
    }
}
