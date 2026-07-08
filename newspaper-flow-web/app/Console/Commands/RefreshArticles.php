<?php

namespace App\Console\Commands;

use App\Models\Topic;
use App\Services\Articles\TopicRefresher;
use App\Services\RefreshWindow;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Article refresh. Scours configured sources for fresh, popular stories for
 * topics and applies the "keep 12, prepend new, drop oldest" rule.
 *
 *   php artisan newspaperflow:refresh                 # every topic (manual/full)
 *   php artisan newspaperflow:refresh --due           # only topics whose owner's
 *                                                # local hour == now (scheduled)
 *   php artisan newspaperflow:refresh --topic=42      # one topic by id
 *   php artisan newspaperflow:refresh --user=7        # all of one user's topics
 *
 * The scheduler (routes/console.php) runs this hourly with --due so each user's
 * feed refreshes at their chosen hour in their own timezone.
 */
class RefreshArticles extends Command
{
    protected $signature = 'newspaperflow:refresh
        {--due : Only refresh topics whose owner\'s chosen hour matches now (in their timezone)}
        {--topic= : Refresh only this topic id}
        {--user= : Refresh only this user\'s topics}';

    protected $description = 'Fetch the latest popular articles for topics and keep each feed full.';

    public function handle(TopicRefresher $refresher): int
    {
        $query = Topic::query();

        if ($id = $this->option('topic')) {
            $query->whereKey($id);
        }

        if ($userId = $this->option('user')) {
            $query->where('user_id', $userId);
        }

        if ($this->option('due')) {
            $dueUserIds = RefreshWindow::dueUserIds();

            if (empty($dueUserIds)) {
                $this->info('No users due for a refresh this hour.');

                return self::SUCCESS;
            }

            $query->whereIn('user_id', $dueUserIds);
        }

        $topics = $query->get();

        if ($topics->isEmpty()) {
            $this->info('No topics to refresh.');

            return self::SUCCESS;
        }

        $this->info("Refreshing {$topics->count()} topic(s)...");

        $totalAdded = 0;

        foreach ($topics as $topic) {
            try {
                $stats = $refresher->refresh($topic);
                $totalAdded += $stats['added'];

                $this->line(sprintf(
                    '  • %-28s +%d new, -%d old, %d total',
                    Str::limit($stats['topic'], 26),
                    $stats['added'],
                    $stats['dropped'],
                    $stats['total'],
                ));
            } catch (\Throwable $e) {
                $this->error("  • {$topic->name}: {$e->getMessage()}");
                report($e);
            }
        }

        $this->info("Done. {$totalAdded} new article(s) added across all topics.");

        return self::SUCCESS;
    }
}
