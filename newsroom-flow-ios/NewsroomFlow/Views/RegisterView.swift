import SwiftUI
import UIKit

@MainActor
final class RegisterViewModel: ObservableObject {
    @Published var submitting = false
    @Published var error: String?

    private var api: NewsroomFlowAPI { ServiceLocator.shared.api }
    private var authStore: AuthStore { ServiceLocator.shared.authStore }

    func submit(name: String, email: String, password: String, onSuccess: @escaping () -> Void) {
        guard !name.isEmpty, !email.isEmpty, password.count >= 8 else {
            error = "Enter your name, email, and a password of at least 8 characters."
            return
        }
        Task {
            submitting = true
            error = nil
            do {
                let res = try await api.register(
                    RegisterRequest(
                        name: name.trimmingCharacters(in: .whitespacesAndNewlines),
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
                error = "That email may already be in use."
            } catch APIError.http(let code) {
                submitting = false
                error = "Sign-up failed (HTTP \(code))."
            } catch {
                submitting = false
                self.error = "Couldn't reach NewsroomFlow. Check your connection."
            }
        }
    }
}

struct RegisterView: View {
    let onAuthenticated: () -> Void
    let onSwitchToLogin: () -> Void

    @StateObject private var vm = RegisterViewModel()
    @State private var name = ""
    @State private var email = ""
    @State private var password = ""

    var body: some View {
        VStack(spacing: 0) {
            BrandHeader(subtitle: "Build Your Own Newsroom")
                .padding(.bottom, 28)

            TextField("Name", text: $name)
                .textContentType(.name)
                .textFieldStyle(.roundedBorder)

            TextField("Email", text: $email)
                .textContentType(.emailAddress)
                .keyboardType(.emailAddress)
                .textInputAutocapitalization(.never)
                .autocorrectionDisabled()
                .textFieldStyle(.roundedBorder)
                .padding(.top, 12)

            SecureField("Password", text: $password)
                .textContentType(.newPassword)
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
                vm.submit(name: name, email: email, password: password, onSuccess: onAuthenticated)
            } label: {
                if vm.submitting {
                    ProgressView().tint(.white)
                } else {
                    Text("Create account").fontWeight(.semibold)
                }
            }
            .frame(maxWidth: .infinity)
            .padding(.vertical, 12)
            .background(Brand.blue)
            .foregroundColor(.white)
            .clipShape(RoundedRectangle(cornerRadius: 10))
            .disabled(vm.submitting)
            .padding(.top, 20)

            Button("Already have an account? Sign in", action: onSwitchToLogin)
                .font(.system(size: 15))
                .tint(Brand.blue)
                .padding(.top, 8)
        }
        .frame(maxWidth: 480)
        .padding(24)
        .frame(maxWidth: .infinity, maxHeight: .infinity)
    }
}
