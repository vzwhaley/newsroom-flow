import Foundation

/// Tiny manual DI container — the iOS counterpart of the Android
/// `ServiceLocator` object. Constructed once at app launch and shared
/// app-wide. The API client reads the bearer token lazily from the same
/// AuthStore instance, so a fresh login is picked up on the next request.
final class ServiceLocator {
    static let shared = ServiceLocator()

    let authStore: AuthStore
    let api: NewsroomFlowAPI

    private init() {
        let store = AuthStore()
        self.authStore = store
        self.api = NewsroomFlowAPI(tokenProvider: { [weak store] in store?.token })
    }
}
