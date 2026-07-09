<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Browse the user's archive of articles that have rotated out of their feeds
 * (Pro history).
 */
class ArchiveController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $locked = ! $user->isPro();
        $q = trim((string) $request->query('q', ''));

        $articles = null;

        if (! $locked) {
            $query = $user->archivedArticles();

            if ($q !== '') {
                $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $q).'%';
                $query->where(fn ($w) => $w
                    ->where('headline', 'like', $like)
                    ->orWhere('description', 'like', $like)
                    ->orWhere('source', 'like', $like)
                    ->orWhere('topic_name', 'like', $like));
            }

            $articles = $query->paginate(30)->withQueryString()->through(fn ($a) => [
                'id'          => $a->id,
                'headline'    => $a->headline,
                'description' => $a->description,
                'url'         => $a->url,
                'source'      => $a->source,
                'topic_name'  => $a->topic_name,
                'archived_at' => $a->archived_at?->toIso8601String(),
            ]);
        }

        return Inertia::render('Archive', [
            'locked'   => $locked,
            'q'        => $q,
            'articles' => $articles,
        ]);
    }
}
