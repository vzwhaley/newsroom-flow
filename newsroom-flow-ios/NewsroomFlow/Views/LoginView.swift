import SwiftUI
import UIKit

@MainActor
final class LoginViewModel: ObservableObject {
    @Published var submitting = false
    @Published var error: String?

    private var api: NewsroomFlowAPI { ServiceLocator.shared.api }
    private var authStore: AuthStore { ServiceLocator.shared.authStore }

    func submit(email: String, password: String, onSuccess: @escaping () -> Void) {
        guard !email.isEmpty, !password.isEmpty else {
            error = "Email and password are required."
            return
        }
        Task {
            submitting = true
            error = nil
            do {
                let res = try await api.login(
                    LoginRequest(
                        email: email.trimmingCharacters(in: .whitespacesAndNewlines),
                        password: password,
                        deviceName: UIDevice.current.name
                    )
                )
                authStore.setToken(res.token)
                submitting = false
                onSuccess()
            } catch APIError.http(422) {
                submitting = false
                error = "Invalid email or password."
            } catch APIError.http(let code) {
                submitting = false
                error = "Sign-in failed (HTTP \(code))."
            } catch {
                submitting = false
                self.error = "Couldn't reach NewsroomFlow. Check your connection."
            }
        }
    }
}

struct LoginView: View {
    let onAuthenticated: () -> Void
    let onSwitchToRegister: () -> Void

    @StateObject private var vm = LoginViewModel()
    @State private var email = ""
    @State private var password = ""

    var body: some View {
        VStack(spacing: 0) {
            BrandHeader(subtitle: "Build Your Own Newsroom")
                .padding(.bottom, 28)

            TextField("Email", text: $email)
                .textContentType(.emailAddress)
                .keyboardType(.emailAddress)
                .textInputAutocapitalization(.never)
                .autocorrectionDisabled()
                .textFieldStyle(.roundedBorder)

            SecureField("Password", text: $password)
                .textContentType(.password)
                .textFieldStyle(.roundedBorder)
                .padding(.top, 12)

            if let error = vm.error {
                Text(error)
                    .font(.system(size: 14))
                    .foregroundColor(.red)
                    .frame(maxWidth: .infinity, alignment: .leading)
                    .padding(.top, 12)
            }

            Button {
                vm.submit(email: email, password: password, onSuccess: onAuthenticated)
            } label: {
                if vm.submitting {
                    ProgressView().tint(.white)
                } else {
                    Text("Sign in").fontWeight(.semibold)
                }
            }
            .frame(maxWidth: .infinity)
            .padding(.vertical, 12)
            .background(Brand.blue)
            .foregroundColor(.white)
            .clipShape(RoundedRectangle(cornerRadius: 10))
            .disabled(vm.submitting)
            .padding(.top, 20)

            Button("New to NewsroomFlow? Create an account", action: onSwitchToRegister)
                .font(.system(size: 15))
                .tint(Brand.blue)
                .padding(.top, 8)
        }
        .frame(maxWidth: 480)
        .padding(24)
        .frame(maxWidth: .infinity, maxHeight: .infinity)
    }
}
