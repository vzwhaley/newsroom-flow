// Free-tier AdMob banner shown at the bottom of the Feed. Renders nothing for
// Pro users — Pro is 100% ad-free. Gating happens here AND server-side via
// /api/config, which omits ad units for Pro tiers, so even a tampered client
// has no unit ID to load against.
//
// SETUP REQUIRED IN XCODE (one-time, on a Mac):
//   1. File → Add Package Dependencies
//   2. Paste: https://github.com/googleads/swift-package-manager-google-mobile-ads.git
//   3. Add the GoogleMobileAds product to the NewsroomFlow target
//   4. Info.plist already has GADApplicationIdentifier (Google's TEST app ID
//      for dev safety — replace with the real one for App Store).
//
// Until the package is added this file compiles via the canImport guard and
// AdBanner simply renders nothing; once added, the real banner takes over.

#if canImport(GoogleMobileAds)
import GoogleMobileAds
#endif
import SwiftUI

struct AdBanner: View {
    var placement: String = "feed_tab"
    /// Google's OFFICIAL TEST banner unit — safe in dev / TestFlight.
    var fallbackUnitID: String = "ca-app-pub-3940256099942544/2934735716"
    let isPro: Bool

    @ObservedObject private var config = AdConfigStore.shared
    @Environment(\.openURL) private var openURL

    var body: some View {
        // Free tier only.
        if isPro { return AnyView(EmptyView()) }
        // Trust the server's explicit show=false once config has loaded.
        if config.payload != nil, !config.showAds { return AnyView(EmptyView()) }

        // Dev builds fall back to Google's official test unit; release builds
        // only ever load a real unit ID delivered by the server — never the
        // test one.
        #if DEBUG
        let unitID = config.unitId(for: placement) ?? fallbackUnitID
        #else
        guard let unitID = config.unitId(for: placement) else { return AnyView(EmptyView()) }
        #endif

        #if canImport(GoogleMobileAds)
        return AnyView(
            VStack(spacing: 2) {
                BannerViewContainer(adUnitID: unitID)
                    .frame(width: GADAdSizeBanner.size.width, height: GADAdSizeBanner.size.height)
                Button { openURL(AppConfig.pricingURL) } label: {
                    Text("Remove Ads — Upgrade To Pro").font(.footnote)
                }
            }
            .padding(.vertical, 8)
        )
        #else
        // SPM package not added yet — render nothing rather than failing the
        // build. Once Xcode resolves the package, the real banner takes over.
        return AnyView(EmptyView())
        #endif
    }
}

#if canImport(GoogleMobileAds)
/// UIViewRepresentable bridge for GADBannerView. Resolves the rootViewController
/// via the connected scenes so AdMob clickthroughs present cleanly.
private struct BannerViewContainer: UIViewRepresentable {
    let adUnitID: String

    func makeUIView(context: Context) -> GADBannerView {
        let banner = GADBannerView(adSize: GADAdSizeBanner)
        banner.adUnitID = adUnitID
        banner.rootViewController = topViewController()
        banner.load(GADRequest())
        return banner
    }

    func updateUIView(_ uiView: GADBannerView, context: Context) {}

    private func topViewController() -> UIViewController? {
        UIApplication.shared.connectedScenes
            .compactMap { ($0 as? UIWindowScene)?.keyWindow?.rootViewController }
            .first
    }
}
#endif
