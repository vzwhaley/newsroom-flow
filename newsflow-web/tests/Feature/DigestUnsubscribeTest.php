<?php

namespace Tests\Feature;

use App\Mail\DailyDigest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class DigestUnsubscribeTest extends TestCase
{
    use RefreshDatabase;

    public function test_signed_link_unsubscribes_without_a_login(): void
    {
        $user = User::factory()->create(['digest_enabled' => true]);

        $url = URL::signedRoute('digest.unsubscribe', ['user' => $user->id]);

        $this->get($url)->assertOk()->assertSee('unsubscribed');

        $this->assertFalse($user->fresh()->digest_enabled);
    }

    public function test_one_click_post_unsubscribes_too(): void
    {
        // RFC 8058: mailbox providers POST to the List-Unsubscribe URL.
        $user = User::factory()->create(['digest_enabled' => true]);

        $url = URL::signedRoute('digest.unsubscribe', ['user' => $user->id]);

        $this->post($url)->assertOk();

        $this->assertFalse($user->fresh()->digest_enabled);
    }

    public function test_unsigned_link_is_rejected(): void
    {
        $user = User::factory()->create(['digest_enabled' => true]);

        $this->get(route('digest.unsubscribe', ['user' => $user->id]))
            ->assertForbidden();

        $this->assertTrue($user->fresh()->digest_enabled);
    }

    public function test_digest_email_carries_unsubscribe_link_and_headers(): void
    {
        $user = User::factory()->create(['digest_enabled' => true]);
        $mail = new DailyDigest($user, [
            ['name' => 'World News', 'articles' => []],
        ]);

        $rendered = $mail->render();
        $this->assertStringContainsString('digest/unsubscribe/'.$user->id, $rendered);

        $headers = $mail->headers()->text;
        $this->assertArrayHasKey('List-Unsubscribe', $headers);
        $this->assertSame('List-Unsubscribe=One-Click', $headers['List-Unsubscribe-Post']);
    }
}
