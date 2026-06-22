import Foundation

/// In-memory cache of `GET /api/config`. Refreshed on sign-in. AdBanner reads
/// the unit ID from `unitId(for:)` and falls back to the AdMob TEST banner when
/// empty (offline / pre-fetch / unconfigured server). Memory-only on purpose so
/// a server reconfigure isn't masked by a stale persisted ID.
@MainActor
final class AdConfigStore: ObservableObject {
    static let shared = AdConfigStore()

    @Published private(set) var payload: ConfigData?

    private var api: NewsFlowAPI { ServiceLocator.shared.api }

    /// True only once the server has explicitly confirmed ads should show.
    var showAds: Bool { payload?.ads.show == true }

    func unitId(for placement: String) -> String? {
        payload?.ads.units?[placement]
    }

    func refresh() async {
        payload = (try? await api.config())?.data
    }

    func clear() {
        payload = nil
    }
}
