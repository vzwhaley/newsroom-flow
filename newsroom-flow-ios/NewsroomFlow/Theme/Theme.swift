import SwiftUI
import UIKit

/// Brand palette mirroring the Android `Theme.kt` / Tailwind tokens so the two
/// native apps look identical. Every token is adaptive: the light values match
/// Android's `LightColors` and the dark values its `DarkColors`, so dark mode
/// stays readable (fixed ink-on-black was illegible before).
enum Brand {
    static let blue      = Color(light: 0x2563EB, dark: 0x60A5FA)   // primary
    static let blueDark  = Color(light: 0x1D4ED8, dark: 0x3B82F6)
    static let blueLight = Color(light: 0xEFF6FF, dark: 0x1E3A8A)   // tinted panels
    static let indigo    = Color(light: 0x4F46E5, dark: 0x818CF8)
    static let ink       = Color(light: 0x0F172A, dark: 0xF8FAFC)   // primary text
    static let gray500   = Color(light: 0x64748B, dark: 0x94A3B8)   // secondary text
    static let gray100   = Color(light: 0xE2E8F0, dark: 0x1F2937)   // borders/surfaces

    /// Gradient used on the "Read more" pill, matching the web/Android button.
    static let pill = LinearGradient(
        colors: [blue, indigo],
        startPoint: .leading,
        endPoint: .trailing
    )

    /// Small brand→indigo gradient for accent dots/badges.
    static let dot = LinearGradient(
        colors: [blue, indigo],
        startPoint: .topLeading,
        endPoint: .bottomTrailing
    )
}

extension Color {
    /// Build a Color from a 0xRRGGBB literal.
    init(hex: UInt32, alpha: Double = 1.0) {
        let r = Double((hex >> 16) & 0xFF) / 255.0
        let g = Double((hex >> 8) & 0xFF) / 255.0
        let b = Double(hex & 0xFF) / 255.0
        self.init(.sRGB, red: r, green: g, blue: b, opacity: alpha)
    }

    /// Adaptive color that resolves per the current light/dark trait.
    init(light: UInt32, dark: UInt32) {
        self.init(UIColor { trait in
            let hex = trait.userInterfaceStyle == .dark ? dark : light
            return UIColor(
                red: CGFloat((hex >> 16) & 0xFF) / 255.0,
                green: CGFloat((hex >> 8) & 0xFF) / 255.0,
                blue: CGFloat(hex & 0xFF) / 255.0,
                alpha: 1.0
            )
        })
    }
}
