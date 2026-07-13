import SwiftUI

@main
struct NewsroomFlowApp: App {
    @UIApplicationDelegateAdaptor(AppDelegate.self) private var appDelegate

    var body: some Scene {
        WindowGroup {
            AppRootView()
                .tint(Brand.blue)
        }
    }
}
