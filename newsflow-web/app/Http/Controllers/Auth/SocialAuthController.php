<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

/**
 * Web social sign-in: Google + Apple + Discord.
 *
 * Flow per provider:
 *   GET  /auth/{provider}/redirect  → bounce to the provider
 *   GET  /auth/{provider}/callback  → Google + Discord return here (GET)
 *   POST /auth/{provider}/callback  → Apple returns here (form_post)
 *
 * Matching strategy (in order):
 *   1. provider ID column (google_id / apple_id / discord_id) — the stable
 *      key (Apple only sends email on the first authorization).
 *   2. VERIFIED email from the provider — links the social identity to an
 *      existing account. Discord can return unverified emails, so linking is
 *      gated on its `verified` flag to prevent account takeover.
 *   3. no match → create a new account with a random password (users set a
 *      real one via "Forgot Password" if they want it).
 */
class SocialAuthController extends Controller
{
    /** Providers we support + the users-table column for each. */
    private const PROVIDERS = [
        'google'  => 'google_id',
        'apple'   => 'apple_id',
        'discord' => 'discord_id',
    ];

    public function redirect(string $provider): RedirectResponse
    {
        abort_unless($this->enabled($provider), 404);

        $driver = Socialite::driver($provider);

        // Apple posts the callback cross-site, so session-based state
        // validation can't work; stateless skips it (the code exchange still
        // proves the response came from Apple).
        if ($provider === 'apple') {
            $driver = $driver->stateless();
        }

        // Discord defaults to `identify` only; we also need `email`.
        if ($provider === 'discord') {
            $driver = $driver->scopes(['identify', 'email']);
        }

        return $driver->redirect();
    }

    public function callback(string $provider): RedirectResponse
    {
        abort_unless($this->enabled($provider), 404);

        try {
            $driver = Socialite::driver($provider);
            if ($provider === 'apple') {
                $driver = $driver->stateless();
            }
            $socialUser = $driver->user();
        } catch (\Throwable $e) {
            Log::info("Social login ({$provider}) callback failed: {$e->getMessage()}");

            return redirect()->route('login')->withErrors([
                'email' => 'Sign-in was cancelled or failed. Please try again, or use your email and password.',
            ]);
        }

        $idColumn = self::PROVIDERS[$provider];
        $providerId = (string) $socialUser->getId();
        $email = $socialUser->getEmail();
        $emailVerified = $email && $this->emailIsVerified($provider, $socialUser);

        // 1. Returning social user.
        $user = User::where($idColumn, $providerId)->first();

        // 2. Existing account with the same VERIFIED email — link it.
        if (! $user && $emailVerified) {
            $user = User::where('email', $email)->first();
            if ($user) {
                $user->forceFill([$idColumn => $providerId])->save();
            }
        }

        // 3. Brand-new user.
        if (! $user) {
            if (! $email) {
                return redirect()->route('register')->withErrors([
                    'email' => 'Apple did not share an email address. Remove NewsFlow from Settings → Apple ID → Sign in with Apple and try again, or register with email and password.',
                ]);
            }

            if (User::where('email', $email)->exists()) {
                return redirect()->route('login')->withErrors([
                    'email' => 'An account already exists for this email. Sign in with your password (or the provider you first used).',
                ]);
            }

            $user = User::create([
                'name' => $socialUser->getName()
                    ?: $socialUser->getNickname()
                    ?: Str::before($email, '@'),
                'email'    => $email,
                'password' => Hash::make(Str::random(40)),
                $idColumn  => $providerId,
            ]);

            if ($emailVerified) {
                $user->forceFill(['email_verified_at' => now()])->save();
            }
        }

        Auth::login($user, remember: true);
        request()->session()->regenerate();

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Whether to trust the email this provider asserted. Google + Apple verify
     * ownership; Discord only when its `verified` flag is set.
     */
    private function emailIsVerified(string $provider, $socialUser): bool
    {
        if (in_array($provider, ['google', 'apple'], true)) {
            return true;
        }

        if ($provider === 'discord') {
            return (bool) ($socialUser->getRaw()['verified'] ?? false);
        }

        return false;
    }

    /** A provider is enabled when its client_id is configured. */
    private function enabled(string $provider): bool
    {
        return array_key_exists($provider, self::PROVIDERS)
            && filled(config("services.{$provider}.client_id"));
    }
}
