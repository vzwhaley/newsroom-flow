import Foundation

enum AppConfig {
    /// Base URL for the NewsroomFlow JSON API.
    ///
    /// In DEBUG we point at a locally-running `php artisan serve`. The iOS
    /// *simulator* shares the host's network, so `localhost` reaches the Mac
    /// directly (unlike the Android emulator, which needs 10.0.2.2). On a
    /// physical device, swap this for the Mac's LAN IP (e.g. http://192.168.x.x:8000).
    ///
    /// In RELEASE we hit production. `NSAllowsLocalNetworking` in Info.plist
    /// permits the cleartext localhost call during development only.
    static let apiBaseURL: URL = {
        #if DEBUG
        return URL(string: "http://localhost:8000")!
        #else
        return URL(string: "https://newsroomflow.app")!
        #endif
    }()

    /// Shown on the upgrade button; the website handles checkout.
    static var pricingURL: URL { apiBaseURL.appendingPathComponent("pricing") }
}
