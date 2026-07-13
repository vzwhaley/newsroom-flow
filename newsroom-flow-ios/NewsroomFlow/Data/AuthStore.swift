import Foundation
import Security

/// Stores the Sanctum bearer token in the iOS Keychain — the platform
/// equivalent of the Android app's EncryptedSharedPreferences. The value is
/// protected with `kSecAttrAccessibleAfterFirstUnlock` so it survives reboots
/// but is unreadable while the device is locked at boot.
final class AuthStore {

    private let service = "com.newsroomflow.ios.auth"
    private let account = "auth_token"

    private(set) var token: String? {
        didSet { /* in-memory mirror is the keychain read below */ }
    }

    init() {
        token = readToken()
    }

    var isLoggedIn: Bool { !(token ?? "").isEmpty }

    func setToken(_ value: String?) {
        if let value, !value.isEmpty {
            writeToken(value)
            token = value
        } else {
            clear()
        }
    }

    func clear() {
        let query: [String: Any] = [
            kSecClass as String: kSecClassGenericPassword,
            kSecAttrService as String: service,
            kSecAttrAccount as String: account,
        ]
        SecItemDelete(query as CFDictionary)
        token = nil
    }

    // MARK: - Keychain primitives

    private func readToken() -> String? {
        let query: [String: Any] = [
            kSecClass as String: kSecClassGenericPassword,
            kSecAttrService as String: service,
            kSecAttrAccount as String: account,
            kSecReturnData as String: true,
            kSecMatchLimit as String: kSecMatchLimitOne,
        ]
        var item: CFTypeRef?
        let status = SecItemCopyMatching(query as CFDictionary, &item)
        guard status == errSecSuccess,
              let data = item as? Data,
              let value = String(data: data, encoding: .utf8) else {
            return nil
        }
        return value
    }

    private func writeToken(_ value: String) {
        let data = Data(value.utf8)
        let base: [String: Any] = [
            kSecClass as String: kSecClassGenericPassword,
            kSecAttrService as String: service,
            kSecAttrAccount as String: account,
        ]
        // Upsert: delete any existing item, then add fresh.
        SecItemDelete(base as CFDictionary)
        var attributes = base
        attributes[kSecValueData as String] = data
        attributes[kSecAttrAccessible as String] = kSecAttrAccessibleAfterFirstUnlock
        SecItemAdd(attributes as CFDictionary, nil)
    }
}
