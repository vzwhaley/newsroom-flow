import SwiftUI

enum AppPhase {
    case loading
    case needsLogin
    case signedIn
}

@MainActor
final class AuthViewModel: ObservableObject {
    @Published var phase: AppPhase = .loading

    private var api: NewsFlowAPI { ServiceLocator.shared.api }
    private var authStore: AuthStore { ServiceLocator.shared.authStore }

    func refreshSession() {
        Task {
            guard authStore.isLoggedIn else {
                phase = .needsLogin
                return
            }
            // Validate the stored token against /me.
            let ok = (try? await api.me()) != nil
            phase = ok ? .signedIn : .needsLogin
        }
    }

    func onAuthenticated() {
        phase = .signedIn
    }

    func signOut() {
        Task {
            await PushManager.shared.unregister()
            _ = try? await api.logout()
            authStore.clear()
            phase = .needsLogin
        }
    }
}

struct AppRootView: View {
    @StateObject private var auth = AuthViewModel()
    @State private var showRegister = false

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
    }
}
