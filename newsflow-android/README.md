# NewsFlow for Android

Native Android client for NewsFlow — Kotlin + Jetpack Compose, talking to the
`newsflow-web` JSON API with Sanctum bearer tokens. Mirrors the FileFlow
Android architecture.

## Stack

- **Kotlin 2.2** + **Jetpack Compose** (Material 3)
- **Retrofit 2** + **OkHttp** + **kotlinx.serialization** for the API
- **EncryptedSharedPreferences** for the bearer token, **DataStore** for prefs
- Manual DI via `ServiceLocator`
- minSdk 26 · target/compileSdk 35 · package `com.newsflow.android`

## Architecture

```
com.newsflow.android/
├── NewsFlowApplication.kt     # ServiceLocator.init()
├── MainActivity.kt            # Compose host → AppRoot
├── data/
│   ├── Models.kt              # @Serializable API models
│   ├── Api.kt                 # Retrofit interface + AuthInterceptor + ApiClient
│   ├── Storage.kt             # AuthStore (encrypted token)
│   └── ServiceLocator.kt      # singletons
└── ui/
    ├── AppRoot.kt             # AuthViewModel: Loading / NeedsLogin / SignedIn
    ├── theme/Theme.kt         # NewsFlow brand colors (Material 3)
    └── screens/
        ├── LoginScreen.kt
        ├── RegisterScreen.kt
        └── FeedScreen.kt      # the reader: topics, articles, add/refresh/delete, watchlist
```

## Connecting to the backend

The app reads `BuildConfig.DEFAULT_API_BASE_URL`:

- **Debug:** `http://10.0.2.2:8000` — the Android emulator's loopback to the
  host machine. Run the web app with:
  ```
  cd ../newsflow-web && php artisan serve
  ```
  (Cleartext to `10.0.2.2` is allowed via `network_security_config.xml`; a
  physical device needs the host's LAN IP instead.)
- **Release:** `https://newsflow.app` (set to the production domain).

The API it consumes lives in `newsflow-web/routes/api.php`
(`/api/auth/*`, `/api/me`, `/api/feed`, `/api/topics`, `/api/articles/*`).

## Build

Open the `newsflow-android/` folder in Android Studio and Run, or from the CLI
(JDK 17 + Android SDK required):

```
./gradlew :app:assembleDebug      # build the debug APK
./gradlew :app:installDebug       # install to a running emulator/device
```

The debug APK lands in `app/build/outputs/apk/debug/`.

## What's implemented (v0.1)

Bottom-nav shell with three tabs:

- **My NewsFlow** — the feed: top-level topics + nested subtopics with their
  articles; add a topic (free 2-topic cap enforced by the server), refresh,
  remove; tap to open in the browser (marks read); **Save** (bookmark) and
  **TL;DR this** (Pro AI summary) on each card; Pro keyword watchlist on top.
- **Saved** — your bookmarked articles; open or remove.
- **Account** — name, email, plan badge (Free/Pro · tier), Upgrade to Pro
  (free users), and Sign out.

Auth: register / login / logout with a Sanctum token stored in
EncryptedSharedPreferences (persists across restarts).

Planned next: search, settings (refresh time / digest), sign-in-with-Google,
push notifications.
