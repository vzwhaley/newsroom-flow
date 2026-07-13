import SwiftUI

@MainActor
final class SearchViewModel: ObservableObject {
    @Published var loading = false
    @Published var locked = false
    @Published var searched = false
    @Published var feed: [SearchItem] = []
    @Published var saved: [SearchItem] = []

    private var api: NewsroomFlowAPI { ServiceLocator.shared.api }

    func search(_ q: String) {
        let trimmed = q.trimmingCharacters(in: .whitespacesAndNewlines)
        guard !trimmed.isEmpty else { return }
        Task {
            loading = true
            let res = try? await api.search(trimmed)
            locked = res?.locked ?? false
            feed = res?.feed ?? []
            saved = res?.saved ?? []
            searched = true
            loading = false
        }
    }
}

struct SearchView: View {
    @StateObject private var vm = SearchViewModel()
    @State private var query = ""
    @Environment(\.openURL) private var openURL

    var body: some View {
        VStack(spacing: 12) {
            HStack(spacing: 8) {
                TextField("Search your feeds & saved", text: $query)
                    .textFieldStyle(.roundedBorder)
                    .submitLabel(.search)
                    .onSubmit { vm.search(query) }
                Button("Go") { vm.search(query) }
                    .buttonStyle(.borderedProminent)
                    .disabled(query.trimmingCharacters(in: .whitespaces).isEmpty)
            }

            content
        }
        .padding(16)
        .frame(maxWidth: .infinity, maxHeight: .infinity, alignment: .top)
    }

    @ViewBuilder
    private var content: some View {
        if vm.loading {
            ProgressView()
                .frame(maxWidth: .infinity, maxHeight: .infinity)
        } else if vm.locked {
            centered("Search is a Pro feature", "Upgrade to search across all your topics and saved articles.")
        } else if !vm.searched {
            centered("Search your news", "Find anything across every topic you follow and everything you've saved.")
        } else if vm.feed.isEmpty && vm.saved.isEmpty {
            centered("No matches", "Try a different word or phrase.")
        } else {
            ScrollView {
                LazyVStack(alignment: .leading, spacing: 10) {
                    if !vm.feed.isEmpty {
                        SectionLabel("In your feeds (\(vm.feed.count))")
                        ForEach(vm.feed) { item in resultRow(item) }
                    }
                    if !vm.saved.isEmpty {
                        SectionLabel("In your saved (\(vm.saved.count))")
                        ForEach(vm.saved) { item in resultRow(item) }
                    }
                }
            }
        }
    }

    private func resultRow(_ item: SearchItem) -> some View {
        ArticleCardView(
            headline: item.headline,
            source: item.source,
            description: item.description,
            topicLabel: item.topicName,
            isRead: item.isRead,
            onOpen: { if let url = URL(string: item.url) { openURL(url) } }
        )
    }

    private func centered(_ title: String, _ body: String) -> some View {
        VStack(spacing: 6) {
            Text(title)
                .font(.system(size: 18))
                .foregroundColor(Brand.ink)
            Text(body)
                .font(.system(size: 14))
                .foregroundColor(Brand.gray500)
                .multilineTextAlignment(.center)
        }
        .padding(24)
        .frame(maxWidth: .infinity, maxHeight: .infinity)
    }
}
