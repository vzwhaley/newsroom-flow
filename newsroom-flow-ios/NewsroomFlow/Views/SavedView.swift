import SwiftUI

@MainActor
final class SavedViewModel: ObservableObject {
    @Published var loading = true
    @Published var items: [SavedItem] = []

    private var api: NewsroomFlowAPI { ServiceLocator.shared.api }

    func load() {
        Task {
            loading = true
            let res = try? await api.saved()
            items = res?.saved ?? []
            loading = false
        }
    }

    func remove(_ id: Int) {
        items.removeAll { $0.id == id }
        Task { _ = try? await api.unsave(id) }
    }
}

struct SavedView: View {
    @StateObject private var vm = SavedViewModel()
    @State private var didLoad = false
    @Environment(\.openURL) private var openURL

    var body: some View {
        Group {
            if vm.loading {
                ProgressView().frame(maxWidth: .infinity, maxHeight: .infinity)
            } else if vm.items.isEmpty {
                VStack(spacing: 6) {
                    Text("Nothing saved yet")
                        .font(.system(size: 18))
                        .foregroundColor(Brand.ink)
                    Text("Tap the bookmark on any article to save it here for later.")
                        .font(.system(size: 14))
                        .foregroundColor(Brand.gray500)
                        .multilineTextAlignment(.center)
                }
                .padding(32)
                .frame(maxWidth: .infinity, maxHeight: .infinity)
            } else {
                ScrollView {
                    LazyVStack(spacing: 10) {
                        ForEach(vm.items) { item in
                            ArticleCardView(
                                headline: item.headline,
                                source: item.source,
                                description: item.description,
                                topicLabel: item.topicName,
                                isPro: true,
                                isSaved: true,
                                onOpen: { if let url = URL(string: item.url) { openURL(url) } },
                                onToggleSave: { vm.remove(item.id) }
                            )
                        }
                    }
                    .padding(16)
                }
            }
        }
        .onAppear {
            if !didLoad { didLoad = true; vm.load() }
        }
    }
}
