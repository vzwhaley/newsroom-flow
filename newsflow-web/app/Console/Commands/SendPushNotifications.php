<?php

namespace App\Console\Commands;

use App\Models\Article;
use App\Models\User;
use App\Services\Push\PushMessage;
use App\Services\Push\PushNotifier;
use App\Services\RefreshWindow;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * Sends the daily "your newsroom is ready" push to opted-in users with at
 * least one registered device, due this hour (their timezone). Scheduled a few
 * minutes after the refresh so it announces the morning's fresh stories. If a
 * Pro user has a new article matching a watch keyword, that headline is
 * featured instead of the generic count.
 *
 *   php artisan newsflow:push --due      # scheduled
 *   php artisan newsflow:push --user=7   # one user, ignoring schedule
 */
class SendPushNotifications extends Command
{
    protected $signature = 'newsflow:push
        {--due : Only send to users due this hour (their timezone)}
        {--user= : Send to a single user id regardless of schedule}';

    protected $description = 'Send the daily push notification to opted-in users.';

    public function handle(PushNotifier $notifier): int
    {
        $users = $this->recipients();

        if ($users->isEmpty()) {
            $this->info('No push recipients this run.');

            return self::SUCCESS;
        }

        $notified = 0;

        foreach ($users as $user) {
            $message = $this->buildMessage($user);

            if (! $message) {
                $this->line("  • {$user->email}: nothing new — skipped");
                continue;
            }

            $devices = $notifier->sendToUser($user, $message);
            $user->forceFill(['push_sent_at' => Carbon::now()])->save();

            if ($devices > 0) {
                $notified++;
                $this->line("  • pushed to {$user->email} ({$devices} device(s))");
            } else {
                $this->line("  • {$user->email}: no live devices");
            }
        }

        $this->info("Done. {$notified} user(s) notified.");

        return self::SUCCESS;
    }

    private function buildMessage(User $user): ?PushMessage
    {
        $since = $user->push_sent_at;

        $newArticles = Article::query()
            ->whereIn('topic_id', $user->topics()->pluck('id'))
            ->when($since, fn ($q) => $q->where('fetched_at', '>', $since))
            ->orderByDesc('fetched_at')
            ->get();

        if ($newArticles->isEmpty()) {
            return null;
        }

        // Pro: feature the first new story that matches a watch keyword.
        if ($user->isPro()) {
            foreach ($newArticles as $article) {
                if (! empty($user->watchMatches($article->headline, $article->description))) {
                    return new PushMessage(
                        title: 'In your watchlist',
                        body: $article->headline,
                        data: ['type' => 'watchlist', 'url' => (string) $article->url],
                    );
                }
            }
        }

        $count = $newArticles->count();

        return new PushMessage(
            title: 'Your NewsFlow is ready',
            body: $count === 1
                ? '1 new story across your topics.'
                : "{$count} new stories across your topics.",
            data: ['type' => 'daily'],
        );
    }

    /**
     * @return \Illuminate\Support\Collection<int, User>
     */
    private function recipients()
    {
        $query = User::query()->where('push_enabled', true)->whereHas('deviceTokens');

        if ($userId = $this->option('user')) {
            return $query->whereKey($userId)->get();
        }

        if ($this->option('due')) {
            return $query->whereIn('id', RefreshWindow::dueUserIds())->get();
        }

        return $query->get();
    }
}
