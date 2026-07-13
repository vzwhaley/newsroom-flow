# NewsroomFlow

Build your own newsroom. NewsroomFlow lets users follow only the topics they care
about and delivers the day's most popular headlines on each one every morning —
a more customizable Google News.

Part of **Moon Whale Media**. Stack and conventions mirror the sibling apps
(FileFlow, The Cardback Cantina, My Emergency Screen).

## Stack

- **Laravel 13** (PHP 8.4) · **Inertia 2** · **Vue 3** · **Tailwind 3**
- **Laravel Cashier 16** (Stripe) for subscriptions + one-time Lifetime
- **Sanctum** (API tokens, for future Android/iOS apps)
- **Socialite** (Google / Apple / Discord sign-in, optional)
- **Breeze** (Vue/Inertia auth scaffolding)
- SQLite by default (swap `DB_CONNECTION` for production)

Served locally by Herd at **https://newsroom.test**.

## Subscription tiers

| Tier            | Price      | Topics      |
| --------------- | ---------- | ----------- |
| Free            | $0         | 2 topics    |
| Pro Monthly     | $4.99/mo   | Unlimited   |
| Pro Yearly      | $49.99/yr  | Unlimited   |
| Pro Lifetime    | $149.99    | Unlimited   |

Tier logic lives in `app/Models/User.php` (`plan()`, `isPro()`,
`topicLimit()`, `canAddTopic()`). Limits are config-driven in
`config/billing.php`.

### Free vs Pro (v1)

**Free:** up to 2 topics · 12 popularity-ranked articles each · daily refresh
at a chosen hour/timezone · read/unread tracking · collapsible categories.

**Pro (Monthly / Yearly / Lifetime):**

- Unlimited topics **+ nested categories/subtopics**
- AI **"TL;DR this"** article summaries (Claude)
- **Keyword watchlist** — matching stories pinned atop the feed
- **Search** across all feeds + saved articles
- **Article archive** — rotated-out stories kept as browsable history
- **Save / read-later** bookmarking
- **Mute keywords** per topic + **block publishers** account-wide
- **Daily email digest** with per-topic selection + "new headlines only"
- **Daily push notification** (native apps) — opt-in, featuring watchlist hits
- Topic reordering

## The article engine

Each topic always holds **12 articles**. A daily refresh scours the web for
fresh, popular stories and applies a "prepend new, drop oldest" rule.

- **Contract:** `App\Contracts\ArticleProvider`
- **Providers:**
  - `HybridArticleProvider` (recommended) — blends news aggregator APIs
    (NewsAPI / GNews / NewsData) for fresh coverage, public engagement signals
    (Reddit / Hacker News) to approximate "most popular", and an optional LLM
    (Claude) to summarize/dedupe. Falls back to the stub when nothing is
    configured.
  - `StubArticleProvider` — realistic placeholder feed, no network calls, so
    the app is fully clickable before any API keys are added. Daily-stable and
    rotates each morning.
- **Refresher:** `App\Services\Articles\TopicRefresher` — the keep-12 /
  prepend-new / drop-oldest logic.
- **Command:** `php artisan newsroomflow:refresh` (all topics) ·
  `--due` (only users due this hour, in their timezone) · `--topic=ID` ·
  `--user=ID`
- **Schedule:** runs hourly with `--due` so each user's feed refreshes at the
  hour they chose, in their own timezone (`routes/console.php`).
- **Daily digest:** opted-in users get a "Your NewsroomFlow is ready" email
  (`newsroomflow:digest --due`, scheduled 5 min after the refresh).

Configure sources in `.env` (`NEWSAPI_KEY`, `GNEWS_KEY`, `NEWSDATA_KEY`,
`ANTHROPIC_API_KEY`). With none set, the hybrid provider serves the stub feed.

## Setup

```bash
composer install
php artisan key:generate
php artisan migrate:fresh --seed   # seeds two demo users + full feeds
npm install && npm run build       # or: npm run dev
```

Demo logins (password `password`):
- `free@newsroom.test` — Free (2 topics)
- `pro@newsroom.test` — Pro Lifetime (unlimited)

### Daily refresh (production)

Point the system scheduler at Laravel every minute:

```
* * * * * cd /path/to/newsroom-flow-web && php artisan schedule:run >> /dev/null 2>&1
```

On Windows, create a Task Scheduler entry that runs
`php artisan schedule:run` each minute.

### Stripe

Add `STRIPE_KEY`, `STRIPE_SECRET`, `STRIPE_WEBHOOK_SECRET`, and the three
`STRIPE_PRICE_PRO_*` price IDs to `.env`. Point a Stripe webhook at
`/stripe/webhook` and subscribe to `customer.subscription.*`, `invoice.*`,
`checkout.session.completed`, and `charge.refunded` (the last enables Lifetime
refund handling). Lifetime grants/revocations are handled by
`App\Listeners\HandleLifetimeCheckout` and `HandleLifetimeRefund`.

## v1 launch checklist

Code-complete. Remaining steps are configuration:

- [ ] **News source** — set `THENEWSAPI_KEY` (real-time, commercial) or
      `NEWSDATA_KEY` (free tier allows commercial use, 12h delay) in `.env`.
      Without a key the app serves the realistic stub feed.
- [ ] **AI summaries** — set `ANTHROPIC_API_KEY` to enable "TL;DR this" + LLM
      digest polishing (Claude Haiku).
- [ ] **Stripe** — keys + 3 `STRIPE_PRICE_PRO_*` price IDs + webhook (see
      below). Checkout buttons stay disabled until configured.
- [ ] **Mail** — Gmail SMTP is wired; confirm with `php artisan newsroomflow:mail-test you@example.com`.
- [ ] **Social login** (optional) — set Google/Apple/Discord client IDs to
      show those buttons.
- [ ] **Scheduler** — ensure `php artisan schedule:run` runs every minute
      (cron / Windows Task Scheduler) so the daily refresh + digest + push fire.
- [ ] **Push notifications** (native apps) — optional. **Android/FCM:** set
      `FCM_PROJECT_ID` + `FCM_CREDENTIALS` (path to the service-account JSON).
      **iOS/APNs:** set `APNS_KEY_ID`, `APNS_TEAM_ID`, `APNS_BUNDLE_ID`,
      `APNS_KEY_PATH` (the `.p8`), and `APNS_PRODUCTION=true` for the App Store
      build. Until configured, push runs through a no-op sender (token
      registration + `newsroomflow:push` still work, nothing is delivered).
- [ ] `php artisan migrate --force && npm run build` on the server.

Everything is covered by the test suite (`php artisan test`, 100+ tests).

## Future apps

The backend is API-first so `newsroom-flow-android` and `newsroom-flow-ios` can become
additional Sanctum-authenticated clients of the same API, mirroring the
FileFlow multi-platform layout. Deferred to v1.1 / mobile: push/breaking-news
alerts, RSS export, audio briefings.
