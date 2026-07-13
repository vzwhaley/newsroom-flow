# NewsroomFlow™ — Session Handoff

**Last updated:** 2026-07-13
**Repo:** `vzwhaley/newsroom-flow` (GitHub) · local: `C:\Users\vzwhaley\Herd\MOON_WHALE_MEDIA\NewsroomFlow`
**Branch:** `main` — in sync with `origin/main` at **`980bd47`** — **working tree CLEAN.**

> Paste this whole file as your first message in a new Claude Code session, or
> just say "read SESSION_HANDOFF.md". The memory notes auto-load already; this
> doc is the crisp "where we are / what's next" snapshot on top of that.

---

## 1. What NewsroomFlow is

"Build your own newsroom" by **Moon Whale Media, LLC** — users follow only the
topics they choose; each topic shows the previous day's most-popular articles,
refreshed daily at the user's chosen hour. Three clients, one backend:

- **newsroom-flow-web/** — Laravel 13 + Inertia 2 (Vue 3, **no SSR**) + Tailwind 3 +
  Cashier 16 (Stripe) + Sanctum + Breeze. The backend API, billing, marketing
  site, and web dashboard. **Source of truth for auth, tiers, feeds, ads config.**
  Served by Herd at **https://newsroomflow.test** (SQLite dev DB).
- **newsroom-flow-android/** — Kotlin 2.2 + Compose Material3, Retrofit +
  kotlinx.serialization, EncryptedSharedPreferences token store. Package
  `com.newsroomflow.android`. Compiles locally.
- **newsroom-flow-ios/** — SwiftUI (iOS 16+), MVVM, URLSession async/await, Keychain
  token store. Bundle `com.newsroomflow.ios`. Feature parity with Android.
  **Cannot compile on this Windows machine — needs a Mac + Xcode**; sources are
  complete but build-unverified.

**Domains:** dev = `newsroomflow.test` (Herd). Production = **`newsroomflow.app`**
and **`newsroomflow.com`** (both registered by the user). SEO/canonical URLs point
at `https://newsroomflow.app`.

Article engine: `ArticleProvider` contract — `HybridArticleProvider` blends a
FREE, keyless **Google News RSS** baseline (real, live articles for any topic and
locality — ON by default, `NEWSROOMFLOW_GOOGLE_NEWS`) with optional paid APIs
(TheNewsAPI/GNews/NewsData) + the keyless HN popularity signal + optional Claude
summaries. `StubArticleProvider` only kicks in when EVERY source is disabled (the
test suite). So the app serves **real articles out of the box, no keys required**.
Cross-source dedupe merges the same story across sources (prefers direct publisher
URLs, fills images). A **daily 4 AM ET global refresh** runs alongside the per-user
hourly refresh.

---

## 2. Current status

**v1 is feature-complete on all three platforms** — full mobile↔web Pro parity,
push plumbing, ads plumbing, SEO, WCAG 2.1 AA, and the complete NewsroomFlow
rename. **Web suite: 218 passing.** Android compiles. iOS is source-complete but
build-unverified (needs a Mac).

### This session — 2026-07-13 (web + mobile, newest first)

| Commit | What landed |
|---|---|
| `980bd47` | Revert dev domain back to `newsroomflow.test` (a brief `newsroom.test` experiment was undone). |
| `11ca83c` | **Mobile NewsFlow → NewsroomFlow rename, full/across-the-board.** Folders → `newsroom-flow-android` / `newsroom-flow-ios`; Android package `com.newsflow.android` → **`com.newsroomflow.android`** (java dir moved, `applicationId`+`namespace`, classes `NewsroomFlow*`); iOS bundle → **`com.newsroomflow.ios`**, Xcode project/target/scheme/entitlements/app-file → `NewsroomFlow*`; web APNS default updated to match. **Android `:app:compileDebugKotlin` BUILD SUCCESSFUL**; iOS `validate-pbxproj.py` passes (needs a Mac build to confirm). |
| `fd4a58c` | Stray old-brand `NewsFlow` mentions → NewsroomFlow (`.env.example` `APP_NAME`, tailwind/gitignore comments, media-kit artifact rename). |
| `32b3bf0` | **SEO + WCAG 2.1 AA audit sweep** (see §2 "audit" below). |
| `31d41eb` `08a60df` `87aaccf` `d037156` | FAQ accordion headers all use the brand-blue active color; **fixed a production 500 on `/faq`** (component was `Faq` but file `FAQ.vue` — case mismatch that only broke on a case-sensitive server; normalized to `FAQ.vue` + `render('FAQ')`). How-to-Use dashboard mockup (`AppMockup.vue`) redrawn to match the current UI (dark sidebar, briefing card, 2-col grid) with the real logo lockup. |
| _(earlier this session)_ | **Project slug rename** `newsflow` → `newsroom-flow` / `newsroomflow` (web subdir, config, env, artisan commands, domains, Herd site, AdSense marker); repo already was `newsroom-flow`. og-default.png regenerated with the NewsroomFlow wordmark. |

### 2026-07-13 SEO + accessibility sweep (commit `32b3bf0`) — what changed
- **SEO:** regenerated `og-default.png` (had the old NewsFlow wordmark — it's the
  preview image for every page + share/streak cards); sitemap `lastmod` bumped to
  2026-07-10; `robots.txt` disallows `/stats` + `/briefing`; Pricing page got an
  sr-only `<h2>` (heading skip); dropped dead `Head` imports.
- **A11y:** FAQ accordion ARIA (aria-expanded/controls/region); visible focus rings
  on the dark sidebar; `gray-400` informational text → `gray-500` (contrast);
  `prefers-reduced-motion` honored (global CSS + gated JS smooth-scrolls);
  GuestLayout skip-link + `<main>`; ArticleCard heading level is now a prop (h3
  under h2 topics, h4 under h3 areas); `role="status"` on "Saved." confirmations;
  TagInput accessible name; `aria-describedby`/`aria-invalid` on key form errors.

### Older feature history (all shipped, newest first — condensed)
- **Local-area news (all 3 platforms):** area feed separate from topics; Free = 1
  area locked after a 24h grace, Pro = unlimited. An area is a `kind='area'` topic
  outside the topic limit (`User::areas()`). Precision via geocoded queries +
  curated `config/localnews.php` outlet directory (~95 metros, 50 states, 20
  countries). Self-learning AI local-source discovery caches discovered outlets
  globally per location (env-gated on `NEWSROOMFLOW_DISCOVERY`+`ANTHROPIC_API_KEY`;
  **needs a queue worker in prod**).
- **AI daily briefing (Pro):** 1 Claude call/user/day cached on the user row;
  non-AI "Preview" fallback. **Priority watchlist push (Pro).** **Reading streaks
  + `/s/{code}` & `/streak/{code}` share cards (Free).**
- **Pro feature set:** unlimited topics + 1-level subtopics, TL;DR summaries,
  keyword watchlist, search, archive, save, per-topic mutes + blocked sources,
  daily digest with one-click RFC-8058 unsubscribe, topic reorder.
- **Push notifications** (FCM + APNs wire senders, unverified against real
  services). **Ads** (web AdSense + mobile AdMob, Free-tier only). **Comprehensive
  SEO** (per-page SeoHead, JSON-LD, robots/sitemap, 1200×630 OG). **Social login**
  Google/Apple/Discord. Branded transactional emails.

### Audit verdicts (2026-07-02) — REJECTED on verification, don't re-raise
Search/Archive "client-side Pro bypass" (server-gated, safe); DeviceToken
`updateOrCreate` (deliberate device-rebind); Archive.vue paginator `v-html`
(server-generated, safe); Archive Pro-only (deliberate); Android `open(article)`
crash (already `runCatching`-guarded).

---

## 3. What's LEFT for development (code)

**Nothing pending or uncommitted.** Open items, rough priority:

1. **iOS build verification (needs a Mac).** Everything since the last Mac build —
   audit fixes, dark-mode palette, local-area feature, AND the 2026-07-13
   rename — compiles on paper only. Open `newsroom-flow-ios/NewsroomFlow.xcodeproj`
   (or `xcodegen generate`, `project.yml` is authoritative), add the GoogleMobileAds
   SPM package, build, run the unit tests. If the hand-updated pbxproj misbehaves,
   regenerate with xcodegen.
2. **Mobile Reading-Stats screens.** Web `/stats` heatmap + `GET /api/stats` are
   live; the Android/iOS stats screens aren't built yet. Small parity follow-up.
3. **v1.1 backlog (parked):** offline reading, RSS/OPML export, widgets, audio digest.

---

## 4. What's LEFT to SHIP v1 — operational (needs YOU / credentials)

1. **News API key — OPTIONAL.** Real articles already flow free via Google News
   RSS. A paid key upgrades to direct publisher URLs + thumbnails: `NEWSDATA_KEY`
   or `THENEWSAPI_KEY` in `newsroom-flow-web/.env` (auto-blends, no code change).
   `ANTHROPIC_API_KEY` enables AI briefings / TL;DR / summaries / local discovery.
2. **Stripe** — live keys + 3 products/prices (Monthly $4.99, Yearly $49.99,
   Lifetime $149.99) + webhook secret.
3. **Ads** — AdSense `ADSENSE_CLIENT` + slot IDs (+ certified CMP for EEA/UK/CH).
   AdMob real app IDs (**both mobile manifests still carry Google TEST app IDs**) +
   real unit IDs via `ADMOB_*` env. Release apps show no ads until the server sends
   real units.
4. **Push** — Android `google-services.json` + FCM service-account JSON; iOS APNs
   `.p8` + Push capability. Senders implemented, unverified against real services.
   (Note the iOS bundle is now `com.newsroomflow.ios` — provision accordingly.)
5. **iOS on a Mac** — GoogleMobileAds SPM package, build, tests, archive.
6. **Production deployment** — `newsroomflow.app` (and/or `.com`) hosting/DNS/TLS;
   set `APP_URL=https://newsroomflow.app` in prod. SEO/canonical/signed-URL links
   already assume `https://newsroomflow.app`.
7. **Android upload keystore** for Play (user generates; never Claude).

---

## 5. How to work in this repo (build/test/git rules)

- **PHP/Composer/Herd only work via the PowerShell tool** — WSL bash can't see them.
- **Web:** `cd newsroom-flow-web; php artisan test` (218 green). After editing
  `resources/js|css` run `npm run build`. PHP-only edits need no build.
- **Preview + browser verify:** Herd serves `https://newsroomflow.test` (a directory
  **junction** at `~/.config/herd/config/valet/Sites/newsroomflow` → `NewsroomFlow\newsroom-flow-web`).
  `mcp__Claude_Preview__preview_start` also works (`php artisan serve`, port 8137,
  config in root `.claude/launch.json`). `behavior:'smooth'` scrolls are a no-op
  in the headless preview but work in real browsers.
- **`php artisan tinker` hangs with multiline `--execute`** — use a seeder or a one-liner.
- **Android:** `$env:JAVA_HOME = "C:\Program Files\Android\Android Studio\jbr"` then
  `cd newsroom-flow-android; .\gradlew.bat :app:compileDebugKotlin` (or
  `:app:assembleDebug`). No `java` on PATH otherwise; SDK path in `local.properties`.
  Package is `com.newsroomflow.android`. Compose BOM 2024.12.01 → Material3 1.3.
- **iOS:** edit sources freely; validate hand-edits to `project.pbxproj` with
  `python build-tools/validate-pbxproj.py`; actual builds need a Mac. Project =
  `NewsroomFlow.xcodeproj` (XcodeGen `project.yml` fallback; uses
  file-system-synchronized groups so folder renames + content stay consistent).
- **Git:** monorepo root is **`NewsroomFlow/`** (top folder renamed 2026-07-13; the
  earlier "still NewsFlow" note is void). Remote `git@github.com:vzwhaley/newsroom-flow.git`.
  **Push to `origin/main` after every commit.** Commit messages with double-quotes
  break PS 5.1 arg-passing — write to a scratch file and `git commit -F <file>`.
  Commit style: `Web:|Android:|iOS:|Apps:` prefix.
- **Cross-platform parity:** any Android UX change gets the iOS equivalent same pass.
- **Emulator/base URLs:** Android dev `http://10.0.2.2:8000`, iOS sim
  `http://localhost:8000`, release both `https://newsroomflow.app`.
- **Case-sensitivity gotcha (bit us on `/faq`):** Windows git is case-insensitive,
  but production Linux + the Vite manifest are case-sensitive. Keep `Inertia::render('X')`,
  the `Pages/X.vue` filename, and the git-tracked casing identical.

---

## 6. Locked decisions — do NOT re-litigate without explicit direction

- **Brand = "NewsroomFlow™"** (™ on the web; "by moon whale media, llc" lowercase
  Spantaran-font signature is deliberate). `APP_NAME=NewsroomFlow`.
- **Naming is now uniform — NOTHING reads "NewsFlow" or "newsflow" anymore.** Slug
  is `newsroom-flow` (folders/subdirs/repo) / `newsroomflow` (config/env/commands/
  domains). The ONLY surviving `newsflow` is a historical migration filename
  (`..._add_newsflow_columns_to_users_table.php`) — do NOT rename it (Laravel would
  re-run it → duplicate-column failure); its content and column names have no
  newsflow. (Also the `newsflow-project.md` memory filename — cosmetic.)
- **Tiers:** Free = **2 topics** + ads; Pro Monthly **$4.99** / Yearly **$49.99** /
  Lifetime **$149.99** = unlimited topics, no ads, TL;DR, watchlist, search, archive,
  saved, mutes, blocked sources, digest. Config in `config/billing.php`.
- **Pricing-page card order:** Free → Pro Lifetime → Pro Yearly → Pro Monthly
  (display only; Yearly stays the highlighted "Best Value").
- **Ads:** Free tier only — web AdSense (728×90 leaderboards) + mobile AdMob banner
  on the Feed. **Pro is 100% ad-free.** `/api/config` omits ad units for Pro.
  Release apps must never load Google's test ad unit (guarded).
- **Auth resilience (all clients):** only an explicit **401/403** from `/api/me`
  logs the user out; transport/5xx failures keep the session (offline launch must
  not sign out).
- **API email verification — NOT enforced for v1.** Web dashboard is behind
  `['auth','verified']`; the JSON API is `auth:sanctum` only. Apps get a non-blocking
  verify-email banner + `POST /api/auth/resend-verification`, never a hard gate.
  Revisit only if unverified-account abuse appears; build in-app verify screens
  FIRST, then add `verified` to API routes. Don't soft-gate.
- **SEO architecture (no SSR):** server-rendered site-level meta in `app.blade.php`
  for JS-less scrapers + per-page `<SeoHead>` via Inertia + JSON-LD injected via
  DOM. Server OG defaults are the homepage copy for no-JS scrapers on inner pages
  (accepted trade-off). Canonical/OG URLs are `https://newsroomflow.app`.
- **Digest emails** keep the signed one-click unsubscribe + RFC 8058 headers.
- **Feed ordering:** topic feeds by region — American, European, Asian; World News
  demo shows one article per publisher.
- **Archive** records articles for Pro users only (storage trade-off).
- **Feature tiers:** AI briefing + priority watchlist push = **Pro**; reading
  streaks + share cards = **Free** (marketing). Briefing ≤1 LLM call/user/day
  (cached); non-AI fallback labeled "Preview".
- **Local-area news:** separate from topics, both tiers. Free = **1 area, locked
  after a 24h grace** (`config('newsroomflow.areas.edit_grace_hours')`); Pro =
  unlimited. An area is a `kind='area'` topic OUTSIDE the topic limit — `User::topics()`
  is scoped to `kind='topic'`, areas via `User::areas()`. Precision ceiling is
  deliberate (metros excellent, small ZIPs degrade). `config/localnews.php` is a
  living asset — extend to improve precision.
- **Article sourcing:** free keyless **Google News RSS** is the default baseline;
  paid APIs are optional upgrades. Evaluated & REJECTED as extra free sources: Bing
  News RSS (dead) and GDELT (rate-limited) — don't re-add. Daily 4 AM ET global
  refresh + per-user hourly (both need `schedule:run` in prod).
- **Reading streak:** ANY article open records the day (keeps streaks alive when
  re-reading); `read_at` set only on first open; `total_reads` counts opens.
- **Dashboard/sidebar UX:** dark full-length slate sidebar; topics **alphabetical**
  while the middle column keeps newest-on-top (intentionally different orders);
  Local News first, areas grouped by **state** (collapsible); drag-and-drop
  reparenting + "Move under…" menu (one-level nesting); exactly one blue "active"
  highlight. Ad default **728×90**. **TL;DR per-article buttons removed** (needs an
  Anthropic key; `articles.summary` route intact but unsurfaced).

---

## 7. Key files & docs to read first

- `~/.claude/projects/...NewsroomFlow/memory/MEMORY.md` (auto-loads — index into
  `newsflow-project.md`, `brand-rename.md`, `project-slug-rename.md`).
- `newsroom-flow-web/README.md` — feature split, launch checklist.
- `newsroom-flow-android/README.md`, `newsroom-flow-ios/README.md` — per-app setup.
- `build-tools/README.md` — asset generators + pbxproj validator.
- `newsroom-flow-web/routes/api.php` — the mobile API contract (both apps mirror it).
- `newsroom-flow-web/config/{billing,adsense,admob,newsroomflow}.php` — env-gated switches.

---

## 8. Loose ends / notes

- **iOS build-unverified** since the last Mac session — compiles on paper; needs
  one real Xcode build (folds into item #1 of §3).
- **Herd dev site** is a directory **junction** (not a symlink — `herd link`
  self-elevates into System32 and links the wrong folder). If the top folder ever
  moves again, recreate it: delete the reparse point with
  `[System.IO.Directory]::Delete($link,$false)` (NON-recursive — never
  `Remove-Item -Recurse` a dir junction), then `New-Item -ItemType Junction -Path
  <Sites\newsroomflow> -Target <...\NewsroomFlow\newsroom-flow-web>`; `herd secure
  newsroomflow`. Gotcha detail in the `project-slug-rename` memory.
- **`newsroom-flow-web/.claude/launch.json`** and the root `.claude/launch.json`
  (preview on port 8137) are gitignored local tooling — leave untracked.
- Verification emails DO send on signup (`Registered` event) — Gmail SMTP creds
  configured and proven.
- **Scheduler:** `newsroomflow:refresh --due` hourly + a daily 4 AM ET global
  `newsroomflow:refresh`, `newsroomflow:digest --due` at :05, `newsroomflow:push
  --due` at :07, `newsroomflow:discover-sources --reverify --queue --limit=50` at
  03:20. Prod needs `schedule:run` cron **and a queue worker** (`php artisan
  queue:work`) for discovery jobs. None fire on this dev box — run
  `php artisan newsroomflow:refresh` manually to refresh feeds.
- **Email logo & `APP_URL`:** emails load the logo from `{APP_URL}/img/email-logo.png`.
  Dev `APP_URL=https://newsroomflow.test` (local-only) so it won't render in an
  external inbox — set `APP_URL=https://newsroomflow.app` in prod.
- **Test data:** `vincent@teamnormandy.com` (Vincent Z. Whaley) has **Pro Lifetime**
  in the dev DB for testing. Demo/seeder users: `free@newsroomflow.test` /
  `pro@newsroomflow.test` (password "password").
- **Media Kit** (`NewsroomFlow_Media_Kit.docx`/`.pdf` at repo root, **gitignored**):
  regenerable, not in git. Pipeline lived in the session scratchpad (does NOT
  survive) — recreate if rebuilding: a `_demoseed.php` seeds a fake **John Doe** Pro
  account (fake data only), a `puppeteer-core` node script screenshots all 17 pages
  and slices them, then a `docx_pdf_generator`-style PS script embeds the logo +
  slices. Delete John Doe + scratch `_demo*.php` after.

---

## 9. Suggested first message for the new session

- **Go live:** "Here are my keys: <news API / Stripe / AdSense / AdMob> — wire them
  into .env and walk me through the launch checklist."
- **Better articles:** "Here's my NewsData.io / TheNewsAPI key and my Anthropic key
  — wire them in for direct URLs, thumbnails, and AI briefings."
- **iOS build day (on the Mac):** "I'm on the Mac — walk me through the Xcode build,
  the GoogleMobileAds package add, and fixing anything that doesn't compile."
- **Deploy:** "Set up production deployment for newsroomflow.app on <host details>."
- **Mobile stats screens:** "Build the Reading-Stats screens on Android + iOS to
  match the web /stats heatmap (GET /api/stats is live)."
- **v1.1:** "Start v1.1 — offline reading / RSS export / widgets."
- **Just verify state:** "Read SESSION_HANDOFF.md and confirm everything is
  committed, pushed, and green."
