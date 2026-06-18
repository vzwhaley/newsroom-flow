<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): Response
    {
        $user = $request->user();

        // Topics for the digest-inclusion checklist (with parent context).
        $topics = $user->topics()
            ->with('parent:id,name')
            ->orderBy('position')
            ->get()
            ->map(fn ($t) => [
                'id'                => $t->id,
                'name'              => $t->name,
                'parent_name'       => $t->parent?->name,
                'include_in_digest' => (bool) $t->include_in_digest,
            ]);

        return Inertia::render('Profile/Edit', [
            'mustVerifyEmail' => $user instanceof MustVerifyEmail,
            'status'          => session('status'),
            'topics'          => $topics,
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
