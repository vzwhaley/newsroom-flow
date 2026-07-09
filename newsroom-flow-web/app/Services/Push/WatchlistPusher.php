<?php

namespace App\Services\Push;

use App\Models\Topic;
use App\Support\FetchedArticle;

/**
 * Priority watchlist push (Pro): the moment a topic refresh lands an article
 * matching one of the user's watch keywords, push it — don't wait for the
 * daily "newsroom ready" notification.
 *
 * Only newly-inserted articles are considered, so a story is never pushed
 * twice. Capped per refresh to avoid notification storms on broad keywords.
 */
class WatchlistPusher
{
    private const MAX_PER_REFRESH = 3;

    public function __construct(private readonly PushNotifier $notifier)
    {
    }

    /**
     * @param  array<int, FetchedArticle>  $added  articles just inserted by the refresher
     * @return int  number of pushes sent
     */
    public function pushMatches(Topic $topic, array $added): int
    {
        $user = $topic->user;

        if (
            ! $user
            || ! $user->isPro()
            || ! $user->push_enabled
            || ! $user->watchlist_push_enabled
            || empty($user->watch_keywords)
            || empty($added)
            || ! $user->deviceTokens()->exists()
        ) {
            return 0;
        }

        $sent = 0;

        foreach ($added as $article) {
            if ($sent >= self::MAX_PER_REFRESH) {
                break;
            }

            $matches = $user->watchMatches($article->headline, $article->description);

            if (empty($matches)) {
                continue;
            }

            $this->notifier->sendToUser($user, new PushMessage(
                title: 'Watchlist: '.$matches[0],
                body: $article->headline,
                data: [
                    'type'  => 'watchlist',
                    'url'   => (string) $article->url,
                    'topic' => $topic->name,
                ],
            ));

            $sent++;
        }

        return $sent;
    }
}
