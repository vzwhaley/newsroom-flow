import SwiftUI

/// A small reusable editor for a list of keywords/sources — a text field to
/// add, and removable rows for each entry. Used for the watchlist, blocked
/// sources, and per-topic mutes.
struct KeywordEditor: View {
    let title: String
    let placeholder: String
    @Binding var items: [String]
    /// Mutes/sources are stored lowercased server-side; lowercase on add to match.
    var lowercased: Bool = false

    @State private var draft = ""

    var body: some View {
        VStack(alignment: .leading, spacing: 8) {
            Text(title)
                .font(.system(size: 14, weight: .semibold))
                .foregroundColor(Brand.ink)

            HStack(spacing: 8) {
                TextField(placeholder, text: $draft)
                    .textFieldStyle(.roundedBorder)
                    .autocorrectionDisabled()
                    .textInputAutocapitalization(.never)
                    .onSubmit(add)
                Button("Add", action: add)
                    .buttonStyle(.bordered)
                    .disabled(draft.trimmingCharacters(in: .whitespaces).isEmpty)
            }

            if items.isEmpty {
                Text("None yet.")
                    .font(.system(size: 13))
                    .foregroundColor(Brand.gray500)
            } else {
                ForEach(items, id: \.self) { item in
                    HStack {
                        Text(item)
                            .font(.system(size: 14))
                            .foregroundColor(Brand.ink)
                        Spacer()
                        Button { remove(item) } label: {
                            Image(systemName: "xmark.circle.fill")
                                .foregroundColor(Brand.gray500)
                        }
                        .buttonStyle(.plain)
                    }
                    .padding(.horizontal, 12)
                    .padding(.vertical, 8)
                    .background(Brand.blueLight)
                    .clipShape(RoundedRectangle(cornerRadius: 8))
                }
            }
        }
    }

    private func add() {
        var value = draft.trimmingCharacters(in: .whitespacesAndNewlines)
        if lowercased { value = value.lowercased() }
        defer { draft = "" }
        guard !value.isEmpty, !items.contains(value) else { return }
        items.append(value)
    }

    private func remove(_ item: String) {
        items.removeAll { $0 == item }
    }
}
