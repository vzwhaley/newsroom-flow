<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Response;

/**
 * One-click unsubscribe from the daily digest email. Reached via a signed URL
 * embedded in every digest (both the footer link and the RFC 8058
 * List-Unsubscribe header), so it works without a login — the signature proves
 * the link came from us.
 */
class DigestUnsubscribeController extends Controller
{
    public function __invoke(User $user): Response
    {
        $user->forceFill(['digest_enabled' => false])->save();

        return response()->view('digest-unsubscribed', ['user' => $user]);
    }
}
