import SwiftUI

@MainActor
final class ArchiveViewModel: ObservableObject {
    @Published var loading = true
    @Published var locked = false
    @Published var items: [ArchivedItem] = []
    @Published var query = ""

    private var api: NewsroomFlowAPI { ServiceLocator.shared.api }

    func load() {
        Task {
            loading = true
            let res = try? await api.archive(query.trimmingCharacters(in: .whitespacesAndNewlines))
            locked = res?.locked ?? false
            items = res?.articles ?? []
            loading = false
        }
    }
}

struct ArchiveView: View {
    @StateObject private var vm = ArchiveViewModel()
    @State private var didLoad = false
    @Environment(\.openURL) private var openURL

    var body: some View {
        VStack(spacing: 12) {
            HStack(spacing: 8) {
                TextField("Search your archive", text: $vm.query)
                    .textFieldStyle(.roundedBorder)
                    .submitLabel(.search)
                    .onSubmit { vm.load() }
                Button("Go") { vm.load() }
                    .buttonStyle(.borderedProminent)
            }

            content
        }
        .padding(16)
        .frame(maxWidth: .infinity, maxHeight: .infinity, alignment: .top)
        .onAppear {
            if !didLoad { didLoad = true; vm.load() }
        }
    }

    @ViewBuilder
    private var content: some View {
        if vm.loading {
            ProgressView()
                .frame(maxWidth: .infinity, maxHeight: .infinity)
        } else if vm.locked {
            centered("Archive is a Pro feature", "Upgrade to keep a browsable history of every story that rotates out of your feeds.")
        } else if vm.items.isEmpty {
            centered(
                vm.query.isEmpty ? "Nothing archived yet" : "No matches",
                vm.query.isEmpty
                    ? "As your feeds refresh, older stories are kept here so you can always find them again."
                    : "Try a different word or phrase."
            )
        } else {
            ScrollView {
                LazyVStack(spacing: 10) {
                    ForEach(vm.items) { item in
                        ArticleCardView(
                            headline: item.headline,
                            source: item.source,
                            description: item.description,
                            topicLabel: item.topicName,
                            onOpen: { if let url = URL(string: item.url) { openURL(url) } }
                        )
                    }
                }
            }
        }
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
