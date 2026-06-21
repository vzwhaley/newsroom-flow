import UIKit
import UserNotifications

/// Owns APNs registration and syncing the device token with the backend. The
/// AppDelegate forwards the system callbacks here. Everything is best-effort:
/// on the simulator or without the Push capability there's simply no token.
final class PushManager {
    static let shared = PushManager()

    private(set) var deviceToken: String?

    private var api: NewsFlowAPI { ServiceLocator.shared.api }
    private var authStore: AuthStore { ServiceLocator.shared.authStore }

    /// Ask for notification permission, then register with APNs to obtain a
    /// token. Safe to call repeatedly (e.g. on sign-in and when the user flips
    /// the Account toggle on).
    func requestAuthorizationAndRegister() {
        UNUserNotificationCenter.current().requestAuthorization(options: [.alert, .badge, .sound]) { _, _ in
            // Register regardless of the grant so the backend always has a
            // token; whether alerts display is governed by the grant.
            DispatchQueue.main.async {
                UIApplication.shared.registerForRemoteNotifications()
            }
        }
    }

    /// Called by the AppDelegate with the raw APNs token.
    func didRegister(tokenData: Data) {
        deviceToken = tokenData.map { String(format: "%02x", $0) }.joined()
        Task { await registerWithBackend() }
    }

    func registerWithBackend() async {
        guard let token = deviceToken, authStore.isLoggedIn else { return }
        _ = try? await api.registerDeviceToken(token)
    }

    func unregister() async {
        guard let token = deviceToken else { return }
        _ = try? await api.deleteDeviceToken(token)
    }
}

/// SwiftUI apps need a UIApplicationDelegate to receive the APNs token. Wired in
/// via `@UIApplicationDelegateAdaptor` in NewsFlowApp.
final class AppDelegate: NSObject, UIApplicationDelegate {
    func application(
        _ application: UIApplication,
        didRegisterForRemoteNotificationsWithDeviceToken deviceToken: Data
    ) {
        PushManager.shared.didRegister(tokenData: deviceToken)
    }

    func application(
        _ application: UIApplication,
        didFailToRegisterForRemoteNotificationsWithError error: Error
    ) {
        // No APNs token available (e.g. simulator). Nothing to do.
    }
}
