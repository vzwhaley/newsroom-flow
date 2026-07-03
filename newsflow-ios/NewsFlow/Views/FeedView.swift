import SwiftUI

struct FeedRow: Identifiable {
    let topic: Topic
    let parentName: String?
    var id: Int { topic.id }
}

@MainActor
final class FeedViewModel: ObservableObject {
    @Published var loading = true
    @Published var loadFailed = false
    @Published var isPro = false
    @Published var emailVerified = true
    @Published var verificationSent = false
    @Published var topicLimit: Int?   // nil = unlimited (Pro) or unknown
    @Published var topicCount = 0
    @Published var topics: [Topic] = []
    @Published var watchlist: [Article] = []
    @Published var readIds: Set<Int> = []
    @Published var savedFps: Set<String> = []
    @Published var reading = ReadingStats()
    @Published var briefing: BriefingResponse?
    @Published var busy = false
    @Published var error: String?

    /// Free user sitting at their topic cap right now.
    var atTopicLimit: Bool {
        guard let limit = topicLimit else { return false }
        return topicCount >= limit
    }

    private var api: NewsFlowAPI { ServiceLocator.shared.api }

    /// Flattened topic + sub-topic rows, in render order.
    var rows: [FeedRow] {
        var out: [FeedRow] = []
        for top in topics {
            out.append(FeedRow(topic: top, parentName: nil))
            for child in top.children {
                out.append(FeedRow(topic: child, parentName: top.name))
            }
        }
        return out
    }

    func load() {
        Task { await loadAsync() }
    }

    func loadAsync() async {
        // Only blank the screen on the initial load — pull-to-refresh and
        // background reloads keep the current list visible.
        if topics.isEmpty { loading = true }
        error = nil
        loadFailed = false
        let me = try? await api.me()
        guard let feed = try? await api.feed() else {
            loading = false
            loadFailed = topics.isEmpty
            error = "Couldn't load your feed."
            return
        }
        let read = feed.topics.flatMap { collectArticles($0) }.filter { $0.isRead }.map { $0.id }
        isPro = me?.user.isPro ?? false
        emailVerified = me?.user.emailVerified ?? true
        topicLimit = me?.user.topicLimit
        topicCount = me?.user.topicCount ?? feed.topics.count
        topics = feed.topics
        watchlist = feed.watchlist
        readIds = Set(read)
        savedFps = Set(feed.savedFingerprints)
        reading = me?.user.reading ?? ReadingStats()

        // Pro: today's AI briefing (server caches it per user per day).
        if isPro && !feed.topics.isEmpty {
            briefing = try? await api.briefing()
        } else {
            briefing = nil
        }

        loading = false
    }

    func resendVerification() {
        Task {
            _ = try? await api.resendVerification()
            verificationSent = true
        }
    }

    func addTopic(_ name: String, parentId: Int? = nil) {
        let trimmed = name.trimmingCharacters(in: .whitespacesAndNewlines)
        guard !trimmed.isEmpty else { return }
        Task {
            busy = true
            error = nil
            do {
                _ = try await api.addTopic(AddTopicRequest(name: trimmed, parentId: parentId))
                busy = false
                load()
            } catch APIError.http(422) {
                busy = false
                error = "Free accounts can follow up to \(topicLimit ?? 2) topics. Upgrade to Pro for unlimited."
            } catch {
                busy = false
                self.error = "Couldn't add that topic."
            }
        }
    }

    /// Move a top-level topic up or down and persist the new order.
    func moveTopic(_ topic: Topic, up: Bool) {
        guard topic.parentId == nil,
              let i = topics.firstIndex(where: { $0.id == topic.id }) else { return }
        let j = up ? i - 1 : i + 1
        guard j >= 0, j < topics.count else { return }
        topics.swapAt(i, j)
        let order = topics.map { $0.id }
        Task {
            do { _ = try await api.reorderTopics(order) }
            catch { await loadAsync() }   // server rejected — resync the list
        }
    }

    func deleteTopic(_ id: Int) {
        Task {
            _ = try? await api.deleteTopic(id)
            load()
        }
    }

    func refreshTopic(_ id: Int) {
        Task {
            busy = true
            _ = try? await api.refreshTopic(id)
            busy = false
            load()
        }
    }

    func markAllRead(_ topic: Topic) {
        readIds.formUnion(topic.articles.map { $0.id })
        Task { _ = try? await api.markAllRead(topic.id) }
    }

    func setMutes(_ topicId: Int, keywords: [String]) {
        Task {
            busy = true
            _ = try? await api.setMutes(topicId, keywords: keywords)
            busy = false
            load()
        }
    }

    func markRead(_ article: Article) {
        guard !readIds.contains(article.id) else { return }
        readIds.insert(article.id)
        Task { _ = try? await api.markRead(article.id) }
    }

    func toggleRead(_ article: Article) {
        let nowRead = !readIds.contains(article.id)
        if nowRead { readIds.insert(article.id) } else { readIds.remove(article.id) }
        Task {
            if nowRead { _ = try? await api.markRead(article.id) }
            else { _ = try? await api.markUnread(article.id) }
        }
    }

    func toggleDigest(_ topic: Topic) {
        Task {
            _ = try? await api.setDigestInclusion(topic.id, included: !topic.includeInDigest)
            load()
        }
    }

    func save(_ article: Article) {
        guard !savedFps.contains(article.fingerprint) else { return }
        savedFps.insert(article.fingerprint)
        Task {
            do {
                _ = try await api.save(
                    SaveRequest(
                        headline: article.headline,
                        description: article.description,
                        url: article.url,
                        source: article.source,
                        imageUrl: article.imageUrl,
                        topicName: article.topicName
                    )
                )
            } catch {
                // Roll back the optimistic bookmark so the UI matches reality.
                savedFps.remove(article.fingerprint)
            }
        }
    }

    private func collectArticles(_ t: Topic) -> [Article] {
        t.articles + t.children.flatMap { collectArticles($0) }
    }
}

struct FeedView: View {
    @StateObject private var vm = FeedViewModel()
    @State private var newTopic = ""
    @State private var didLoad = false
    @State private var muteTarget: Topic?
    @State private var subtopicParentId: Int?
    @State private var showSubtopicAlert = false
    @State private var subtopicName = ""
    @Environment(\.openURL) private var openURL

    var body: some View {
        Group {
            if vm.loading {
                ProgressView().frame(maxWidth: .infinity, maxHeight: .infinity)
            } else if vm.loadFailed {
                VStack(spacing: 12) {
                    Image(systemName: "wifi.exclamationmark")
                        .font(.system(size: 32))
                        .foregroundColor(Brand.gray500)
                    Text("Couldn't load your feed.")
                        .foregroundColor(Brand.gray500)
                    Button("Try Again") { vm.load() }
                        .buttonStyle(.borderedProminent)
                }
                .frame(maxWidth: .infinity, maxHeight: .infinity)
            } else {
                ScrollView {
                    LazyVStack(alignment: .leading, spacing: 10) {
                        if !vm.emailVerified {
                            verifyEmailBanner
                        }

                        if vm.reading.streak > 0 {
                            streakChip
                        }

                        if let briefing = vm.briefing {
                            briefingCard(briefing)
                        }

                        addTopicRow

                        if !vm.watchlist.isEmpty {
                            SectionLabel("On your watchlist").padding(.top, 6)
                            ForEach(vm.watchlist) { a in
                                card(for: a, topicLabel: a.topicName)
                            }
                        }

                        ForEach(vm.rows) { row in
                            topicHeader(row)
                                .padding(.top, 6)
                            if row.topic.articles.isEmpty {
                                Text("No articles yet.")
                                    .font(.system(size: 13))
                                    .foregroundColor(Brand.gray500)
                            } else {
                                ForEach(row.topic.articles) { a in
                                    card(for: a, topicLabel: nil)
                                }
                            }
                        }

                        if vm.rows.isEmpty {
                            Text("Add your first topic above — World News, your team, a company, a hobby — and we'll pull today's top stories.")
                                .foregroundColor(Brand.gray500)
                                .padding(.top, 24)
                        }

                        // Free-tier ad banner (Pro removes it).
                        AdBanner(isPro: vm.isPro)
                            .frame(maxWidth: .infinity)
                    }
                    .padding(16)
                }
                .refreshable { await vm.loadAsync() }
            }
        }
        .onAppear {
            if !didLoad { didLoad = true; vm.load() }
        }
        .sheet(item: $muteTarget) { topic in
            MuteSheet(topic: topic) { keywords in
                vm.setMutes(topic.id, keywords: keywords)
            }
        }
        .alert("Add subtopic", isPresented: $showSubtopicAlert) {
            TextField("Subtopic name", text: $subtopicName)
            Button("Add") {
                if let pid = subtopicParentId { vm.addTopic(subtopicName, parentId: pid) }
                subtopicName = ""
            }
            Button("Cancel", role: .cancel) { subtopicName = "" }
        } message: {
            Text("Add a topic nested under this category.")
        }
    }

    private var streakChip: some View {
        Text("🔥 \(vm.reading.streak)-day reading streak" +
             (vm.reading.readToday ? "" : " — read a story to keep it!"))
            .font(.system(size: 12, weight: .semibold))
            .foregroundColor(Color(red: 0.76, green: 0.25, blue: 0.05))
            .padding(.horizontal, 12)
            .padding(.vertical, 5)
            .background(Color(red: 1.0, green: 0.97, blue: 0.93))
            .clipShape(Capsule())
    }

    private func briefingCard(_ b: BriefingResponse) -> some View {
        VStack(alignment: .leading, spacing: 4) {
            HStack(spacing: 6) {
                Image(systemName: "bolt.fill")
                    .font(.system(size: 13))
                    .foregroundColor(Brand.blue)
                Text("Your Daily Briefing")
                    .font(.system(size: 14, weight: .bold))
                    .foregroundColor(Brand.ink)
                if !b.ai {
                    Text("PREVIEW")
                        .font(.system(size: 9, weight: .bold))
                        .foregroundColor(Brand.gray500)
                }
            }
            Text(b.briefing)
                .font(.system(size: 13))
                .foregroundColor(Brand.ink)
        }
        .padding(14)
        .frame(maxWidth: .infinity, alignment: .leading)
        .background(Brand.blueLight)
        .clipShape(RoundedRectangle(cornerRadius: 16))
    }

    private var verifyEmailBanner: some View {
        VStack(alignment: .leading, spacing: 4) {
            Text("Please verify your email")
                .font(.system(size: 14, weight: .semibold))
                .foregroundColor(Brand.ink)
            Text("We sent a link to your inbox — verifying keeps your account recoverable.")
                .font(.system(size: 13))
                .foregroundColor(Brand.gray500)
            Button(vm.verificationSent ? "Sent — check your inbox" : "Resend email") {
                vm.resendVerification()
            }
            .font(.system(size: 13, weight: .semibold))
            .disabled(vm.verificationSent)
            .padding(.top, 2)
        }
        .padding(14)
        .frame(maxWidth: .infinity, alignment: .leading)
        .background(Brand.blueLight)
        .clipShape(RoundedRectangle(cornerRadius: 12))
    }

    private var addTopicRow: some View {
        VStack(alignment: .leading, spacing: 6) {
            HStack(spacing: 8) {
                TextField("Add a topic", text: $newTopic)
                    .textFieldStyle(.roundedBorder)
                    .onSubmit(submitTopic)
                Button("Add", action: submitTopic)
                    .buttonStyle(.borderedProminent)
                    .disabled(vm.busy || newTopic.trimmingCharacters(in: .whitespaces).isEmpty || vm.atTopicLimit)
            }
            if let limit = vm.topicLimit {
                Text(vm.atTopicLimit
                     ? "You've used all \(limit) free topics — upgrade to Pro for unlimited."
                     : "\(vm.topicCount) of \(limit) topics used")
                    .font(.system(size: 12))
                    .foregroundColor(vm.atTopicLimit ? .orange : Brand.gray500)
            }
            if let error = vm.error {
                Text(error)
                    .font(.system(size: 13))
                    .foregroundColor(.red)
            }
        }
    }

    private func submitTopic() {
        vm.addTopic(newTopic)
        newTopic = ""
    }

    private func topicHeader(_ row: FeedRow) -> some View {
        HStack(alignment: .center) {
            VStack(alignment: .leading, spacing: 0) {
                if let parent = row.parentName {
                    Text(parent.uppercased())
                        .font(.system(size: 11, weight: .bold))
                        .foregroundColor(Brand.blue)
                }
                Text(row.topic.name)
                    .font(.system(size: 20, weight: .bold))
                    .foregroundColor(Brand.ink)
            }
            Spacer()
            if !row.topic.muteKeywords.isEmpty {
                Image(systemName: "speaker.slash.fill")
                    .font(.system(size: 12))
                    .foregroundColor(Brand.gray500)
            }
            Button { vm.refreshTopic(row.topic.id) } label: {
                Image(systemName: "arrow.clockwise").foregroundColor(Brand.gray500)
            }
            Menu {
                Button { vm.markAllRead(row.topic) } label: {
                    Label("Mark all read", systemImage: "checkmark.circle")
                }
                if vm.isPro {
                    Button { muteTarget = row.topic } label: {
                        Label("Mute keywords…", systemImage: "speaker.slash")
                    }
                }
                Button { vm.toggleDigest(row.topic) } label: {
                    Label(
                        row.topic.includeInDigest ? "Remove from digest" : "Add to daily digest",
                        systemImage: row.topic.includeInDigest ? "envelope.badge" : "envelope"
                    )
                }
                if row.topic.parentId == nil {
                    Button { subtopicParentId = row.topic.id; showSubtopicAlert = true } label: {
                        Label("Add subtopic…", systemImage: "plus")
                    }
                    Button { vm.moveTopic(row.topic, up: true) } label: {
                        Label("Move up", systemImage: "arrow.up")
                    }
                    Button { vm.moveTopic(row.topic, up: false) } label: {
                        Label("Move down", systemImage: "arrow.down")
                    }
                }
                Divider()
                Button(role: .destructive) { vm.deleteTopic(row.topic.id) } label: {
                    Label("Remove topic", systemImage: "trash")
                }
            } label: {
                Image(systemName: "ellipsis.circle").foregroundColor(Brand.gray500)
            }
        }
    }

    private func card(for a: Article, topicLabel: String?) -> some View {
        ArticleCardView(
            headline: a.headline,
            source: a.source,
            description: a.description,
            topicLabel: topicLabel,
            isRead: vm.readIds.contains(a.id),
            isPro: vm.isPro,
            isSaved: vm.savedFps.contains(a.fingerprint),
            articleId: a.id,
            onOpen: { open(a) },
            onToggleSave: { vm.save(a) },
            onToggleRead: { vm.toggleRead(a) }
        )
    }

    private func open(_ a: Article) {
        vm.markRead(a)
        if let url = URL(string: a.url) { openURL(url) }
    }
}

/// Modal editor for a topic's muted keywords (Pro).
struct MuteSheet: View {
    let topic: Topic
    let onSave: ([String]) -> Void

    @Environment(\.dismiss) private var dismiss
    @State private var keywords: [String]

    init(topic: Topic, onSave: @escaping ([String]) -> Void) {
        self.topic = topic
        self.onSave = onSave
        _keywords = State(initialValue: topic.muteKeywords)
    }

    var body: some View {
        NavigationStack {
            ScrollView {
                VStack(alignment: .leading, spacing: 12) {
                    Text("Hide stories in “\(topic.name)” that mention any of these words.")
                        .font(.system(size: 14))
                        .foregroundColor(Brand.gray500)
                    KeywordEditor(
                        title: "Muted keywords",
                        placeholder: "e.g. crypto",
                        items: $keywords,
                        lowercased: true
                    )
                }
                .padding(20)
            }
            .navigationTitle("Mute keywords")
            .navigationBarTitleDisplayMode(.inline)
            .toolbar {
                ToolbarItem(placement: .cancellationAction) {
                    Button("Cancel") { dismiss() }
                }
                ToolbarItem(placement: .confirmationAction) {
                    Button("Save") { onSave(keywords); dismiss() }
                }
            }
        }
    }
}
