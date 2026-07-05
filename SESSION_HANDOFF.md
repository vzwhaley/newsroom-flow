# NewsFlow™ — Session Handoff

**Last updated:** 2026-07-05
**Repo:** `vzwhaley/news-flow` (GitHub) · local: `C:\Users\vzwhaley\Herd\MOON_WHALE_MEDIA\NewsFlow`
**Branch:** `main` (in sync with `origin/main` at commit `de0ba08`) — **working tree CLEAN, nothing uncommitted**

> Paste this whole file as your first message in a new Claude Code session,
> or just say "read SESSION_HANDOFF.md". The memory notes auto-load already;
> this doc is the crisp "where we are / what's next" snapshot on top of that.

---

## 1. What NewsFlow is

"Build your own newsroom" by **Moon Whale Media, LLC** — users follow only the
topics they choose; each topic shows the previous day's most-popular articles,
refreshed daily at the user's chosen hour. Three clients, one backend:

- **newsflow-web/** — Laravel 13 + Inertia 2 (Vue 3, **no SSR**) + Tailwind 3 +
  Cashier 16 (Stripe) + Sanctum + Breeze. The backend API, billing, marketing
  site, and web dashboard. **Source of truth for auth, tiers, feeds, ads config.**
  Served by Herd at **https://newsflow.test** (SQLite dev DB). Production domain:
  **https://newsflow.app** (SEO/canonical URLs already point there).
- **newsflow-android/** — Kotlin 2.2 + Compose Material3, Retrofit +
  kotlinx.serialization, EncryptedSharedPreferences token store. Builds locally.
- **newsflow-ios/** — SwiftUI (iOS 16+), MVVM, URLSession async/await, Keychain
  token store. Feature parity with Android. **Cannot compile on this Windows
  machine — needs a Mac + Xcode**; sources are complete but build-unverified.

Article engine: `ArticleProvider` contract — `HybridArticleProvider`
(TheNewsAPI/GNews/NewsData + Reddit/HN popularity signals + optional Claude
TL;DR) with `StubArticleProvider` fallback when no API keys are set. Everything
is env-gated: the app runs fully on placeholder data today.

---

## 2. Current status

**v1 is feature-complete on all three platforms, with full mobile↔web Pro
parity, push-notification plumbing, ads plumbing, SEO, and a completed
3-platform audit/fix round (2026-07-02).** Web suite: **211 passing**.

### Recent work (this session, newest first)

| Commit | What landed |
|---|---|
| `de0ba08` | **Web: pricing-page tier order** changed to Free → Lifetime → Yearly → Monthly (display order only; containers/styling/prices unchanged, Yearly still the highlighted "Best Value"). |
| _(uncommitted, local only)_ | **Media Kit refreshed** — `NewsFlow_Media_Kit.docx`/`.pdf` at repo root (gitignored). Now has the official logo lockup at the top + a full visual tour at the bottom: sliced full-page screenshots of **all 17 pages** (10 public + 7 signed-in app), captured from a fictitious "John Doe" Pro demo account (no real data). See §8 for the regeneration pipeline. |
| `add7e89` / `f78a49e` | **Daily safety-net discovery sweep** — `newsflow:discover-sources --reverify --queue --limit=50` scheduled daily at 03:20; catches areas created while discovery was off, failed discoveries, and records past the re-verify TTL. Added `--queue`/`--limit`. |
| `50fd71d` | **Self-learning AI local-source discovery** (web). When an area's location isn't in the curated `localnews.php`, a web-search-grounded Claude call (`LocalSourceDiscovery` + Anthropic `web_search` tool) finds its real local outlets, validates domains (liveness + redirect-canonicalization auto-catches rebrands), and caches them in `discovered_local_sources` **globally per location** (discovered once, reused by everyone). Resolution: curated metro → discovered cache → statewide → country. Queued `DiscoverAreaLocalSources` job on area create/update; `newsflow:discover-sources` backfill/reverify command. Fully env-gated on `NEWSFLOW_DISCOVERY`+`ANTHROPIC_API_KEY` (clean no-op without them). **Needs a queue worker in prod** for async discovery. |
| `c039133` | **Northeast TN + Knoxville local outlets** — Greeneville Sun, Johnson City Press, Kingsport Times-News, Bristol Herald Courier, WJHL/WCYB/WETS, Knoxville (News Sentinel/WBIR/WATE/WVLT/WUOT), all web-verified; test locks in resolution. |
| `e57980c` | **Broadened local-outlet directory** — ~95 metros, all 50 states, 20 countries; every domain web-verified (6 rebrand/defunct fixes). |
| `985d7d9` / `f18b6d1` / `53937f1` | **Local-area news — all 3 platforms.** New area-tailored feed, separate from topics. USA form = city/state/ZIP, international = city/country. **Free = 1 area, permanent after a 24h typo-grace window; Pro = unlimited add/edit/delete.** Reuses the topic pipeline (`kind='area'` on topics, outside the topic limit). Precision = geocoded queries (ZIP→city via Zippopotam.us) + country hints + curated local-outlet domain biasing (`config/localnews.php` + new `LocationAwareProvider`/`fetchLocal`). Endpoints `/areas` + `/api/areas`; areas in dashboard/feed/`/api/me`. 14 tests. iOS build-unverified. |
| `7a3cab6` | **Web: briefing rides push + digest email (Pro)** — morning push body IS the briefing (watchlist hit still wins), digest email opens with it, one shared LLM call/user/day. **Reading stats + streak brag cards (Free)** — `/stats` heatmap page (`ReadingDay::fullStatsFor`), `shared_streaks` + public `/streak/{code}` OG card, `GET /api/stats` ready for the apps (**mobile stats screens are a follow-up**). |
| `8ed6f15` | **iOS: briefing card, streak chip, share sheet, watchlist-push toggle** (parity pass; no new files, no pbxproj change; build-unverified). |
| `e6b2385` | **Android: same four features** (compileDebugKotlin green). |
| `5d89804` | **Web/API: three new features** — ① AI daily briefing (Pro): `DailyBriefing` service, cached per user/day on `users.briefing(_for)`, `GET /briefing` + `GET /api/briefing`, deterministic non-AI fallback when no `ANTHROPIC_API_KEY` (`ai=false` → "Preview" tag). ② Watchlist priority push (Pro): `WatchlistPusher` fires at refresh time for newly-inserted watch-keyword matches (cap 3/refresh, never repushes), `users.watchlist_push_enabled` toggle everywhere. ③ Streaks + share cards (Free): `reading_days` table + `ReadingDay::bump/statsFor` (streak/read_today/total_reads in `/api/me.user.reading` + dashboard props + 🔥 chip), `shared_articles` + public `/s/{code}` OG share page (noindex, click counter) + share buttons on all clients. 20 new tests. |
| `425c1bf` | **Web: accessibility (WCAG 2.1 AA) + SEO hardening sweep** — skip links, aria-labels on all icon buttons, keyboard fixes, focus rings, noindex on private pages, full JSON-LD offers, sitemap lastmod. |
| `ef06133` | **iOS: adaptive dark-mode palette** (dark mode was ink-on-black illegible — Brand tokens now dynamic light/dark mirroring Android), verify-email banner, reorder rollback. |
| `790bbf2` | Android: verify-email banner + reorder rollback. |
| `59275fa` | Web: `POST /api/auth/resend-verification` (throttled) backing the apps' verify-email banners. |
| `11eeda0` | **iOS: offline cold launch no longer logs the user out** (only 401/403 clears the token), foreground token re-validation, pull-to-refresh, retry state, "X of 2 topics used" limit UI, save-failure handling, push-token sync on toggle, release builds never fall back to the test ad unit. |
| `270aefc` | Android: same fix set — `feed.body()!!` NPE fixed, PullToRefreshBox, retry state, topic-limit UI, "Saved." only on real success, push register/unregister sync, stable notification IDs, test-ad-unit release guard. |
| `000515f` | **Web: one-click digest unsubscribe** (signed URL + RFC 8058 List-Unsubscribe headers → Gmail/Apple Mail native button) + throttles on topic store/refresh and TL;DR summary (web + API). |
| `f3c74a1` | Web: comprehensive SEO — per-page `<SeoHead>` (title/description/canonical/OG/Twitter), server-rendered scraper defaults in app.blade.php (no SSR!), JSON-LD (Organization/WebSite/SoftwareApplication on `/`, FAQPage on `/faq`), 1200×630 OG image, robots/sitemap. |
| `f475c64` | `build-tools/` — asset-generation + validation scripts (favicons, OG image, Android icon, hero optimizer, pbxproj validator). |

### Audit verdicts (2026-07-02) — findings **rejected on verification**, don't re-raise
- Search/Archive "client-side Pro bypass" — server-side gating is correct and
  leaks no data (`locked` flag, empty payloads for Free).
- DeviceToken `updateOrCreate` re-pointing — deliberate device-rebinding pattern.
- Archive.vue pagination `v-html` — server-generated paginator labels, safe.
- Archive captures articles for Pro only — deliberate (storage trade-off).
- Android `open(article)` URL crash — already guarded with `runCatching`.

---

## 3. What's LEFT for development (code)

**Nothing is pending or uncommitted.** Open items, in rough priority:

1. **Decision needed: API email verification.** The website requires a verified
   email for the dashboard; the JSON API deliberately does not (would strand app
   signups — the apps have no verify screen). Current compromise: non-blocking
   verify-email banner + resend button in both apps. If you want hard parity,
   the apps need a proper verification gate/screen first.
2. **iOS build verification** — everything since the last Mac build (audit fixes,
   dark-mode palette, banners, local-area feature) compiles on paper only. Needs one Xcode build.
3. **Mobile Reading-Stats screens** — the web `/stats` heatmap shipped and
   `GET /api/stats` is live and ready, but the Android/iOS stats screens aren't
   built yet. Small parity follow-up (mirror the streak/heatmap UI).
4. v1.1 backlog (parked): offline reading, RSS/OPML export, widgets, audio digest.

---

## 4. What's LEFT to SHIP v1 — operational (needs YOU / credentials)

1. **News API key** — `THENEWSAPI_KEY` or `NEWSDATA_KEY` in `newsflow-web/.env`
   (site currently runs on the stub provider).
2. **Stripe** — live keys + create the 3 products/prices (Monthly $4.99, Yearly
   $49.99, Lifetime $149.99) + webhook secret.
3. **Ads** — AdSense: `ADSENSE_CLIENT` + slot IDs (+ certified CMP before
   serving in EEA/UK/CH). AdMob: real app IDs (**both mobile manifests still
   carry Google's TEST app IDs**) + real unit IDs served via `/api/config`
   (`ADMOB_*` env). Release apps render no ads until the server sends real units.
4. **Push** — Android: `google-services.json` + FCM service-account JSON.
   iOS: APNs `.p8` key + Push capability. Wire senders are implemented but
   unverified against the real services.
5. **iOS on a Mac** — add the GoogleMobileAds SPM package (Xcode UI, ~2 min),
   build, run the unit tests, archive.
6. **Production deployment** — newsflow.app hosting/DNS/TLS; SEO + canonical +
   signed-URL links already assume `https://newsflow.app`.
7. **Android upload keystore** for Play (user generates; never Claude).

---

## 5. How to work in this repo (build/test/git rules)

- **PHP/Composer/Herd only work via the PowerShell tool** — WSL bash can't see them.
- **Web:** `cd newsflow-web; php artisan test` (211 green). After editing
  `resources/js|css` run `npm run build`. PHP-only edits need no build.
- **`php artisan tinker` hangs with multiline `--execute`** — use a seeder or a
  one-liner instead.
- **Android:** `$env:JAVA_HOME = "C:\Program Files\Android\Android Studio\jbr"`
  then `cd newsflow-android; .\gradlew.bat :app:compileDebugKotlin` (or
  `:app:assembleDebug`). No `java` on PATH otherwise; SDK path is in
  `local.properties`. Compose BOM 2024.12.01 → Material3 1.3 (PullToRefreshBox
  is available, needs `@OptIn(ExperimentalMaterial3Api::class)`).
- **iOS:** edit sources freely; validate hand-edits to `project.pbxproj` with
  `python build-tools/validate-pbxproj.py`; actual builds need a Mac.
- **Git:** monorepo root is `NewsFlow/` (not newsflow-web). **Push to
  `origin/main` after every commit.** Commit messages containing double-quotes
  break PS 5.1 arg-passing — write the message to a scratch file and
  `git commit -F <file>`. Commit style: `Web:|Android:|iOS:|Apps:` prefix.
- **Cross-platform parity:** any Android UX change gets the iOS equivalent in
  the same pass (and vice versa).
- **Emulator/base URLs:** Android dev uses `http://10.0.2.2:8000`, iOS simulator
  `http://localhost:8000`, release both `https://newsflow.app`.

---

## 6. Locked decisions — do NOT re-litigate without explicit direction

- **Tiers:** Free = **2 topics** + ads; Pro Monthly **$4.99**, Yearly **$49.99**,
  Lifetime **$149.99** = unlimited topics, no ads, TL;DR, watchlist, search,
  archive, saved, mutes, blocked sources, digest controls. Config-driven in
  `config/billing.php`.
- **Ads:** Free tier only — web AdSense (728×90 leaderboards fixed-size like the
  sibling sites) + mobile AdMob banner on the Feed. **Pro is 100% ad-free.**
  Server (`/api/config`) omits ad units for Pro. Release apps must never load
  Google's test ad unit (guarded in code).
- **Auth resilience (all clients):** only an explicit **401/403** from `/api/me`
  logs the user out; transport failures/5xx keep the session (offline launch
  must not sign out). Implemented web-of-truth style on both apps.
- **SEO architecture (no SSR):** server-rendered site-level meta in
  `app.blade.php` for JS-less scrapers + per-page `<SeoHead>` via Inertia for
  Google + JSON-LD injected via DOM (Inertia `<Head>` can't render `<script>`).
  Duplicate og:title (blade default + per-page) is the accepted trade-off.
- **Digest emails** must keep the signed one-click unsubscribe + RFC 8058 headers.
- **Feed ordering:** topic feeds ordered by region — American, European, Asian;
  World News demo shows one article per publisher.
- **Archive** records articles for Pro users only (storage trade-off, deliberate).
- **Feature tiers (added 2026-07-03):** AI daily briefing + priority watchlist
  push are **Pro**; reading streaks + branded share cards (`/s/{code}`) are
  **Free** (shares are marketing). Briefing costs ≤1 LLM call per user per day
  (cached on the user row); non-AI fallback is labeled "Preview".
- **Local-area news (added 2026-07-04):** separate from topics, both tiers.
  Free = **1 area, locked after a 24h typo-grace window** (edit/delete only
  within grace; `config('newsflow.areas.edit_grace_hours')`); Pro = unlimited.
  An area is a `kind='area'` topic (outside the topic limit) — do NOT let it
  count against topics(); `User::topics()` is scoped to `kind='topic'`, areas
  via `User::areas()`. Precision ceiling is deliberate: metros excellent,
  small ZIPs degrade to nearest city. Local-outlet directory in
  `config/localnews.php` is a living asset — extend it to improve precision.
- **Brand:** "NewsFlow™" with ™ on the web; "by moon whale media, llc" lowercase
  signature is deliberate.
- **Pricing-page card order (set 2026-07-05):** Free → Pro Lifetime → Pro Yearly
  → Pro Monthly. Display order only; prices and the highlighted "Best Value"
  (Yearly) are unchanged. Don't revert without direction.

---

## 7. Key files & docs to read first in a new session

- `~/.claude/projects/...NewsFlow/memory/MEMORY.md` → `newsflow-project.md`
  (auto-loads — full history incl. env gotchas).
- `newsflow-web/README.md` — feature split, launch checklist.
- `newsflow-android/README.md`, `newsflow-ios/README.md` — per-app setup incl.
  push/ads credential steps.
- `build-tools/README.md` — asset generators + pbxproj validator.
- `newsflow-web/routes/api.php` — the mobile API contract (both apps mirror it
  method-for-method).
- `newsflow-web/config/billing.php`, `config/adsense.php`, `config/admob.php`,
  `config/newsflow.php` — all the env-gated switches.

---

## 8. Loose ends / notes

- **iOS is build-unverified** since the last Mac session — everything compiles
  on paper (patterns already proven in the codebase) but needs one real build.
- **`newsflow-web/.claude/launch.json`** is gitignored local tooling (preview
  server on port 8011) — leave it untracked.
- Herd serves https://newsflow.test; the Claude preview config uses
  `php artisan serve --port=8011` instead (both work).
- Verification emails DO send on app signup (`Registered` event) — Gmail SMTP
  creds are already configured and proven.
- The scheduler runs `newsflow:refresh --due` hourly, `newsflow:digest --due`
  hourly at :05, `newsflow:push --due` hourly at :07, and
  `newsflow:discover-sources --reverify --queue --limit=50` daily at 03:20 —
  production needs a real cron entry for `schedule:run` **and a queue worker**
  (`php artisan queue:work`) for the discovery jobs.
- **Media Kit** (`NewsFlow_Media_Kit.docx`/`.pdf` at repo root, **gitignored**):
  regenerable, not in git. Generation pipeline lives in the *session scratchpad*
  (does NOT survive into a new session — recreate it if you need to rebuild):
  (1) a `_demoseed.php` in `newsflow-web/` seeds a fake **John Doe** Pro account
  (topics + subtopic, a Springfield-IL area, reading_days for the streak/heatmap,
  saved + archived articles) — **use only fake data, never the user's**;
  (2) a Node script (`puppeteer-core` driving installed Chrome against
  `https://newsflow.test`) logs in as John Doe, dismisses the cookie banner,
  full-page-screenshots all 17 pages, then slices each tall PNG into
  page-height bands (`pngjs`) encoded as JPEG (`jpeg-js`, q84, 1.5×) so images
  flow across doc pages without clipping; (3) an extended
  `docx_pdf_generator`-style PS script embeds the logo (top) + slices (bottom)
  — DOCX via OOXML `word/media` parts + drawing XML, PDF via headless-Chrome
  `<img>`. Delete John Doe (`_democlean.php`) + the scratch `_demo*.php` after.
  The logo lockup is reproduced from `ApplicationLogo.vue` (newspaper mark) +
  "NewsFlow™" + the Spantaran-font "by moon whale media, llc" signature.

---

## 9. Suggested first message for the new session

Pick based on what you want to do:

- **Go live:** "Here are my keys: <news API / Stripe / AdSense / AdMob> — wire
  them into .env and walk me through the launch checklist."
- **Deploy:** "Set up production deployment for newsflow.app on <host details>."
- **iOS build day (on the Mac):** "I'm on the Mac — walk me through the Xcode
  build, the GoogleMobileAds package add, and fixing anything that doesn't compile."
- **Email verification decision:** "Let's enforce email verification on the API —
  build the in-app verification screens."
- **v1.1:** "Start v1.1 — offline reading / RSS export / widgets."
- **Just verify state:** "Read SESSION_HANDOFF.md and confirm everything is
  committed, pushed, and green."
