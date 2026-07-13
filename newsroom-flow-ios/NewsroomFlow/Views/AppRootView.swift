import SwiftUI

enum AppPhase {
    case loading
    case needsLogin
    case signedIn
}

@MainActor
final class AuthViewModel: ObservableObject {
    @Published var phase: AppPhase = .loading

    private var api: NewsroomFlowAPI { ServiceLocator.shared.api }
    private var authStore: AuthStore { ServiceLocator.shared.authStore }

    func refreshSession() {
        Task {
            guard authStore.isLoggedIn else {
                phase = .needsLogin
                return
            }
            // Validate the stored token against /me. Only an explicit
            // unauthorized response signs the user out — a network failure
            // (offline launch, flaky connection) keeps the session alive.
            do {
                _ = try await api.me()
                phase = .signedIn
                await AdConfigStore.shared.refresh()
            } catch APIError.http(401), APIError.http(403) {
                authStore.clear()
                phase = .needsLogin
            } catch {
                phase = .signedIn
            }
        }
    }

    func onAuthenticated() {
        phase = .signedIn
        Task { await AdConfigStore.shared.refresh() }
    }

    func signOut() {
        Task {
            await PushManager.shared.unregister()
            _ = try? await api.logout()
            authStore.clear()
            AdConfigStore.shared.clear()
            phase = .needsLogin
        }
    }
}

struct AppRootView: View {
    @StateObject private var auth = AuthViewModel()
    @State private var showRegister = false
    @Environment(\.scenePhase) private var scenePhase

    var body: some View {
        Group {
            switch auth.phase {
            case .loading:
                ProgressView()
                    .frame(maxWidth: .infinity, maxHeight: .infinity)

            case .needsLogin:
                if showRegister {
                    RegisterView(
                        onAuthenticated: { auth.onAuthenticated() },
                        onSwitchToLogin: { showRegister = false }
                    )
                } else {
                    LoginView(
                        onAuthenticated: { auth.onAuthenticated() },
                        onSwitchToRegister: { showRegister = true }
                    )
                }

            case .signedIn:
                MainView(onSignOut: { auth.signOut() })
            }
        }
        .onAppear { auth.refreshSession() }
        .onChange(of: scenePhase) { newPhase in
            // Re-validate the token when returning from the background so a
            // session revoked server-side is caught without a cold launch.
            if newPhase == .active && auth.phase == .signedIn {
                auth.refreshSession()
            }
        }
    }
}
