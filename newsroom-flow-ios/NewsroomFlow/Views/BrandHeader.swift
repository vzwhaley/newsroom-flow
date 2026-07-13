import SwiftUI

/// "NewsroomFlow™ · by moon whale media, llc" lockup with an optional subtitle —
/// the iOS twin of the Android `BrandHeader` composable.
struct BrandHeader: View {
    var subtitle: String? = nil

    var body: some View {
        VStack(spacing: 4) {
            HStack(alignment: .top, spacing: 0) {
                Text("News")
                    .font(.system(size: 30, weight: .bold))
                    .foregroundColor(Brand.ink)
                Text("Flow")
                    .font(.system(size: 30, weight: .bold))
                    .foregroundColor(Brand.blue)
                Text("™")
                    .font(.system(size: 13))
                    .foregroundColor(Brand.ink)
            }
            Text("by moon whale media, llc")
                .font(.system(size: 12))
                .foregroundColor(Brand.gray500)

            if let subtitle {
                Text(subtitle)
                    .font(.system(size: 16, weight: .semibold))
                    .foregroundColor(Brand.ink)
                    .multilineTextAlignment(.center)
                    .padding(.top, 10)
            }
        }
    }
}

/// Compact "News Flow" wordmark for the navigation bar title.
struct BrandTitle: View {
    var body: some View {
        HStack(spacing: 0) {
            Text("News").fontWeight(.bold).foregroundColor(Brand.ink)
            Text("Flow").fontWeight(.bold).foregroundColor(Brand.blue)
        }
    }
}

/// Small bold section label in brand blue (mirrors Android `SectionLabel`).
struct SectionLabel: View {
    let text: String
    init(_ text: String) { self.text = text }
    var body: some View {
        Text(text)
            .font(.system(size: 13, weight: .bold))
            .foregroundColor(Brand.blue)
            .frame(maxWidth: .infinity, alignment: .leading)
    }
}
