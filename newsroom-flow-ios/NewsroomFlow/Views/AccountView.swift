import SwiftUI

@MainActor
final class AccountViewModel: ObservableObject {
    @Published var user: User?
    @Published var refreshHour = 6
    @Published var digestEnabled = false
    @Published var digestNewOnly = false
    @Published var pushEnabled = false
    @Published var watchlistPushEnabled = true
    @Published var watchKeywords: [String] = []
    @Published var blockedSources: [String] = []
    @Published var saving = false
    @Published var saved = false
    @Published var saveError: String?

    private var api: NewsroomFlowAPI { ServiceLocator.shared.api }
    private var savedClearTask: Task<Void, Never>?

    func load() {
        Task {
            if let u = try? await api.me().user {
                user = u
                refreshHour = u.refreshHour
                digestEnabled = u.digestEnabled
                digestNewOnly = u.digestNewOnly
                pushEnabled = u.pushEnabled
                watchlistPushEnabled = u.watchlistPushEnabled
                watchKeywords = u.watchKeywords
                blockedSources = u.blockedSources
            }
        }
    }

    /// Re-fetch just the identity/plan (e.g. returning to the tab after an
    /// upgrade) without clobbering in-progress edits to the form fields.
    func refreshUser() {
        Task {
            if let u = try? await api.me().user { user = u }
        }
    }

    func save() {
        Task {
            saving = true
            saveError = nil
            do {
                _ = try await api.updatePreferences(
                    PreferencesRequest(
                        refreshHour: refreshHour,
                        timezone: TimeZone.current.identifier,
                        digestEnabled: digestEnabled,
                        digestNewOnly: digestNewOnly,
                        pushEnabled: pushEnabled,
                        watchlistPushEnabled: watchlistPushEnabled,
                        watchKeywords: watchKeywords,
                        blockedSources: blockedSources
                    )
                )
                // Keep the backend token registry in sync with the toggle.
                if pushEnabled {
                    await PushManager.shared.registerWithBackend()
                } else {
                    await PushManager.shared.unregister()
                }
                saved = true
                savedClearTask?.cancel()
                savedClearTask = Task {
                    try? await Task.sleep(nanoseconds: 2_500_000_000)
                    if !Task.isCancelled { saved = false }
                }
            } catch {
                saveError = "Couldn't save your changes. Please try again."
            }
            saving = false
        }
    }
}

struct AccountView: View {
    let onSignOut: () -> Void

    @StateObject private var vm = AccountViewModel()
    @State private var didLoad = false
    @Environment(\.openURL) private var openURL

    private var tierLabel: String {
        guard let user = vm.user else { return "" }
        if user.isPro {
            if let tier = user.tier, !tier.isEmpty {
                return "Pro · \(tier.prefix(1).uppercased() + tier.dropFirst())"
            }
            return "Pro"
        }
        return "Free"
    }

    var body: some View {
        ScrollView {
            VStack(spacing: 16) {
                identityCard
                if let user = vm.user, !user.isPro {
                    Button {
                        openURL(AppConfig.pricingURL)
                    } label: {
                        Text("Upgrade to Pro")
                            .fontWeight(.semibold)
                            .frame(maxWidth: .infinity)
                    }
                    .buttonStyle(.borderedProminent)
                }
                preferencesCard

                if vm.user?.isPro == true {
                    powerFeaturesCard
                }

                saveRow

                Button(action: onSignOut) {
                    Text("Sign out").frame(maxWidth: .infinity)
                }
                .buttonStyle(.bordered)

                Text("NewsroomFlow · by moon whale media, llc")
                    .font(.system(size: 12))
                    .foregroundColor(Brand.gray500)
                    .frame(maxWidth: .infinity, alignment: .leading)
            }
            .padding(20)
        }
        .onAppear {
            if !didLoad { didLoad = true; vm.load() }
            else { vm.refreshUser() }
        }
    }

    private var identityCard: some View {
        VStack(alignment: .leading, spacing: 0) {
            Text(vm.user?.name ?? "—")
                .font(.system(size: 20, weight: .bold))
                .foregroundColor(Brand.ink)
            Text(vm.user?.email ?? "")
                .font(.system(size: 14))
                .foregroundColor(Brand.gray500)
            Text("Plan: \(tierLabel)")
                .font(.system(size: 14, weight: .semibold))
                .foregroundColor(Brand.blue)
                .padding(.top, 10)
        }
        .padding(18)
        .frame(maxWidth: .infinity, alignment: .leading)
        .background(Brand.gray100.opacity(0.6))
        .clipShape(RoundedRectangle(cornerRadius: 14))
    }

    private var preferencesCard: some View {
        VStack(alignment: .leading, spacing: 12) {
            Text("News preferences")
                .font(.system(size: 16, weight: .semibold))
                .foregroundColor(Brand.ink)

            HStack {
                Text("Daily refresh time")
                    .font(.system(size: 14))
                    .foregroundColor(Brand.ink)
                Spacer()
                Picker("", selection: $vm.refreshHour) {
                    ForEach(0..<24, id: \.self) { h in
                        Text(hourLabel(h)).tag(h)
                    }
                }
                .pickerStyle(.menu)
                .tint(Brand.blue)
            }

            Toggle(isOn: $vm.digestEnabled) {
                Text("Email me a daily digest")
                    .font(.system(size: 14))
                    .foregroundColor(Brand.ink)
            }

            if vm.digestEnabled {
                Toggle(isOn: $vm.digestNewOnly) {
                    Text("Only new headlines")
                        .font(.system(size: 14))
                        .foregroundColor(Brand.ink)
                }
            }

            Toggle(isOn: $vm.pushEnabled) {
                Text("Push notifications")
                    .font(.system(size: 14))
                    .foregroundColor(Brand.ink)
            }

            if vm.user?.isPro == true && vm.pushEnabled {
                Toggle(isOn: $vm.watchlistPushEnabled) {
                    VStack(alignment: .leading, spacing: 2) {
                        Text("Priority watchlist push")
                            .font(.system(size: 14))
                            .foregroundColor(Brand.ink)
                        Text("Push the moment a fresh story matches a watch keyword.")
                            .font(.system(size: 12))
                            .foregroundColor(Brand.gray500)
                    }
                }
            }
        }
        .padding(18)
        .frame(maxWidth: .infinity, alignment: .leading)
        .cardSurface()
        .onChange(of: vm.refreshHour) { _ in vm.saved = false }
        .onChange(of: vm.digestEnabled) { _ in vm.saved = false }
        .onChange(of: vm.digestNewOnly) { _ in vm.saved = false }
        .onChange(of: vm.pushEnabled) { enabled in
            vm.saved = false
            if enabled { PushManager.shared.requestAuthorizationAndRegister() }
        }
        .onChange(of: vm.watchlistPushEnabled) { _ in vm.saved = false }
    }

    private var powerFeaturesCard: some View {
        VStack(alignment: .leading, spacing: 16) {
            Text("Pro power features")
                .font(.system(size: 16, weight: .semibold))
                .foregroundColor(Brand.ink)

            KeywordEditor(
                title: "Watchlist keywords",
                placeholder: "e.g. Tesla",
                items: $vm.watchKeywords
            )
            Text("Stories matching these are pinned to the top of your feed.")
                .font(.system(size: 12))
                .foregroundColor(Brand.gray500)

            Divider()

            KeywordEditor(
                title: "Blocked publishers",
                placeholder: "e.g. tabloid.com",
                items: $vm.blockedSources,
                lowercased: true
            )
            Text("Articles from these sources are hidden from every feed.")
                .font(.system(size: 12))
                .foregroundColor(Brand.gray500)
        }
        .padding(18)
        .frame(maxWidth: .infinity, alignment: .leading)
        .cardSurface()
        .onChange(of: vm.watchKeywords) { _ in vm.saved = false }
        .onChange(of: vm.blockedSources) { _ in vm.saved = false }
    }

    private var saveRow: some View {
        HStack {
            Button { vm.save() } label: {
                Text(vm.saving ? "Saving…" : "Save changes").fontWeight(.semibold)
            }
            .buttonStyle(.borderedProminent)
            .disabled(vm.saving)
            if vm.saved {
                Text("Saved.")
                    .font(.system(size: 13))
                    .foregroundColor(Brand.gray500)
            }
            if let saveError = vm.saveError {
                Text(saveError)
                    .font(.system(size: 13))
                    .foregroundColor(.red)
            }
            Spacer()
        }
    }

    private func hourLabel(_ h: Int) -> String {
        let ampm = h < 12 ? "AM" : "PM"
        let hr = h % 12 == 0 ? 12 : h % 12
        return "\(hr):00 \(ampm)"
    }
}

extension View {
    /// White card surface with the standard border used across the app.
    func cardSurface() -> some View {
        self
            .background(Color(.systemBackground))
            .clipShape(RoundedRectangle(cornerRadius: 14))
            .overlay(
                RoundedRectangle(cornerRadius: 14).stroke(Brand.gray100, lineWidth: 1)
            )
    }
}
