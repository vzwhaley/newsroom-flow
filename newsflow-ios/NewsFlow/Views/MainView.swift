import SwiftUI

/// Bottom tab shell — Feed / Search / Saved / Archive / Account — the iOS
/// counterpart of the Android `MainScreen` with its `NavigationBar`.
struct MainView: View {
    let onSignOut: () -> Void

    var body: some View {
        TabView {
            tab(FeedView()) {
                Label("Feed", systemImage: "newspaper")
            }
            tab(SearchView()) {
                Label("Search", systemImage: "magnifyingglass")
            }
            tab(SavedView()) {
                Label("Saved", systemImage: "bookmark.fill")
            }
            tab(ArchiveView()) {
                Label("Archive", systemImage: "archivebox.fill")
            }
            tab(AccountView(onSignOut: onSignOut)) {
                Label("Account", systemImage: "person.fill")
            }
        }
        .onAppear { PushManager.shared.requestAuthorizationAndRegister() }
    }

    /// Wraps each tab in a NavigationStack carrying the brand wordmark title.
    private func tab<Content: View, L: View>(_ content: Content, @ViewBuilder label: () -> L) -> some View {
        NavigationStack {
            content
                .navigationBarTitleDisplayMode(.inline)
                .toolbar {
                    ToolbarItem(placement: .principal) { BrandTitle() }
                }
        }
        .tabItem { label() }
    }
}
