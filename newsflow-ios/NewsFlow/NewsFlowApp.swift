import SwiftUI

@main
struct NewsFlowApp: App {
    @UIApplicationDelegateAdaptor(AppDelegate.self) private var appDelegate

    var body: some Scene {
        WindowGroup {
            AppRootView()
                .tint(Brand.blue)
        }
    }
}
