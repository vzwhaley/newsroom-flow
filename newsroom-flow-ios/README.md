# NewsroomFlow for iOS

Native SwiftUI client for NewsroomFlow, mirroring the Android app
([`../newsroom-flow-android`](../newsroom-flow-android)) feature-for-feature against the
same Sanctum-authenticated JSON API exposed by the web app
([`../newsroom-flow-web/routes/api.php`](../newsroom-flow-web/routes/api.php)).

- **UI:** SwiftUI (iOS 16+), Material-equivalent brand styling
- **Networking:** `URLSession` + async/await, `Codable`
- **Auth:** Sanctum bearer token stored in the iOS **Keychain**
- **DI:** a tiny `ServiceLocator` singleton (same pattern as Android)
- **Architecture:** MVVM — one `ObservableObject` view model per screen

## Requirements

- **macOS with Xcode 16 or newer.** The committed `NewsroomFlow.xcodeproj` uses
  file-system-synchronized groups (an Xcode 16 feature), so every `.swift`
  file under `NewsroomFlow/` is compiled automatically — no need to register files
  in the project. On **Xcode 15**, regenerate the project with XcodeGen
  instead (see below).

> iOS apps can only be **compiled and run on macOS**. This project was authored
> on Windows, so the Swift sources and project are complete and ready, but the
> final build/run/archive must happen on a Mac.

## Open & run

```bash
open NewsroomFlow.xcodeproj
```

Pick an iOS Simulator (e.g. iPhone 16) and press **⌘R**.

### Pointing at the API

[`NewsroomFlow/Config/AppConfig.swift`](NewsroomFlow/Config/AppConfig.swift) selects the
base URL by build configuration:

| Build   | Base URL                  | Notes                                                            |
|---------|---------------------------|-----------------------------------------------------------------|
| Debug   | `http://localhost:8000`   | The iOS **simulator** shares the Mac's network, so `localhost` reaches `php artisan serve` directly (unlike Android's `10.0.2.2`). |
| Release | `https://newsroomflow.app`    | Production.                                                      |

For a **physical device** in Debug, change `localhost` to the Mac's LAN IP
(e.g. `http://192.168.1.20:8000`). Cleartext localhost is permitted in Debug via
`NSAllowsLocalNetworking` in [`Info.plist`](Info.plist); production is HTTPS.

To test against the local web app:

```bash
cd ../newsroom-flow-web
php artisan serve --host=0.0.0.0 --port=8000
```

## Regenerate the project (Xcode 15 / XcodeGen)

```bash
brew install xcodegen
xcodegen generate      # reads project.yml, writes NewsroomFlow.xcodeproj
```

## Project layout

```
NewsroomFlow/
  NewsroomFlowApp.swift          @main entry point
  Config/AppConfig.swift     API base URL per build config
  Theme/Theme.swift          Brand palette + Color(hex:)
  Data/
    Models.swift             Codable request/response types (mirror Models.kt)
    NewsroomFlowAPI.swift         URLSession API client (mirror Api.kt/Retrofit)
    AuthStore.swift          Keychain token storage (mirror Storage.kt)
    ServiceLocator.swift     Manual DI singleton
  Views/
    AppRootView.swift        Auth phase gate (loading/login/signed-in)
    LoginView.swift          Sign in
    RegisterView.swift       Create account
    MainView.swift           Bottom tab bar (Feed/Search/Saved/Account)
    FeedView.swift           Topics, watchlist, add/refresh/delete, read/save
    SearchView.swift         Pro search across feeds + saved
    SavedView.swift          Saved-for-later list
    AccountView.swift        Plan, refresh-time, digest prefs, sign out
    ArticleCardView.swift    Shared article tile + gradient "Read more" + TL;DR
    BrandHeader.swift        Wordmark / section labels
Info.plist                   ATS local-networking exception, orientations
project.yml                  XcodeGen spec (Xcode 15 fallback)
```

## API surface consumed

All endpoints under `auth:sanctum` (plus the public register/login):

`POST /api/auth/register`, `POST /api/auth/login`, `POST /api/auth/logout`,
`GET /api/me`, `GET /api/config`, `GET /api/feed`, `GET /api/search?q=`,
`GET /api/archive?q=`, `PUT /api/preferences`
(incl. watch_keywords + blocked_sources), `POST /api/topics`,
`POST /api/topics/reorder`, `POST /api/topics/{id}/refresh`,
`PATCH /api/topics/{id}/mutes`, `POST /api/topics/{id}/read-all`,
`DELETE /api/topics/{id}`, `POST /api/articles/{id}/read`,
`DELETE /api/articles/{id}/read`, `POST /api/articles/{id}/summary`,
`GET /api/saved`, `POST /api/saved`, `DELETE /api/saved/{id}`,
`POST /api/device-tokens`, `DELETE /api/device-tokens?token=`.

## Push notifications (APNs)

The app registers for remote notifications on sign-in and posts its APNs token
to `POST /api/device-tokens`; the Account screen has a "Push notifications"
toggle. To actually deliver pushes you need an Apple Developer account:

1. Enable the **Push Notifications** capability for the `com.newsroomflow.ios` App
   ID. `NewsroomFlow.entitlements` already declares `aps-environment`.
2. Create an **APNs Auth Key (.p8)** and configure the backend (`APNS_KEY_ID`,
   `APNS_TEAM_ID`, `APNS_BUNDLE_ID`, `APNS_KEY_PATH`) — see the web README.

A real device is required to obtain an APNs token (the simulator can't).

## Tests

A `NewsroomFlowTests` unit-test target (wired into the project + scheme) covers the
highest-risk area for code authored without a compiler in the loop — the
`Codable` layer:

- `ModelDecodingTests` — snake_case → model decoding and `decodeIfPresent`
  defaults for `User`, `Article`, nested `Topic`/children, `FeedResponse`,
  `SearchResponse`, `AuthResponse`.
- `RequestEncodingTests` — request bodies serialize to the snake_case keys the
  Laravel API expects (`device_name`, `parent_id` omitted when nil, `refresh_hour`,
  `image_url`, `topic_name`, …).

Run with **⌘U** in Xcode, or:

```bash
xcodebuild test -scheme NewsroomFlow -destination 'platform=iOS Simulator,name=iPhone 16'
```

## Ads (AdMob — Free tier; Pro removes them)

A banner runs at the bottom of the Feed for Free users; Pro never sees it (the
server omits the ad unit from `/api/config` for Pro, and `AdBanner` skips
rendering). `AdBanner.swift` is wrapped in `#if canImport(GoogleMobileAds)`, so
the app **builds without the SDK** (the banner just renders nothing). To enable
real ads on a Mac:

1. **File → Add Package Dependencies** →
   `https://github.com/googleads/swift-package-manager-google-mobile-ads.git`,
   add the `GoogleMobileAds` product to the NewsroomFlow target.
2. `Info.plist` already has `GADApplicationIdentifier` (Google's TEST app ID —
   replace with the real `ca-app-pub-…~…` for the App Store).
3. Configure the backend: `ADMOB_APP_ID_IOS` + `ADMOB_UNIT_FEED_TAB`.

## Not yet included

- Push notifications / universal links
- UI (XCUITest) target — only logic unit tests so far
- Stripe in-app purchase (upgrade opens the website, matching Android)
- A designed app icon — current `AppIcon` is a generated brand-blue "NF" monogram
