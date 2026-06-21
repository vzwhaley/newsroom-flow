import Foundation

/// Errors surfaced to view models so they can branch on HTTP status the way
/// the Android app inspects `response.code()`.
enum APIError: Error {
    case http(Int)        // non-2xx response
    case transport        // couldn't reach the server / network failure
    case decoding         // malformed body

    var statusCode: Int? {
        if case let .http(code) = self { return code }
        return nil
    }
}

/// URLSession-backed client for the NewsFlow JSON API. Mirrors the Android
/// Retrofit `NewsFlowApi` interface method-for-method. Every authenticated
/// request carries the bearer token pulled fresh from the AuthStore, plus an
/// `Accept: application/json` header (the Retrofit AuthInterceptor equivalent).
final class NewsFlowAPI {

    private let baseURL: URL
    private let tokenProvider: () -> String?
    private let session: URLSession

    private let decoder: JSONDecoder = {
        let d = JSONDecoder()
        d.keyDecodingStrategy = .convertFromSnakeCase
        return d
    }()

    private let encoder: JSONEncoder = {
        let e = JSONEncoder()
        e.keyEncodingStrategy = .convertToSnakeCase
        return e
    }()

    init(baseURL: URL = AppConfig.apiBaseURL, tokenProvider: @escaping () -> String?) {
        self.baseURL = baseURL
        self.tokenProvider = tokenProvider
        let config = URLSessionConfiguration.default
        config.timeoutIntervalForRequest = 30
        config.waitsForConnectivity = true
        self.session = URLSession(configuration: config)
    }

    // MARK: - Auth

    func register(_ body: RegisterRequest) async throws -> AuthResponse {
        try await send("api/auth/register", method: "POST", body: body)
    }

    func login(_ body: LoginRequest) async throws -> AuthResponse {
        try await send("api/auth/login", method: "POST", body: body)
    }

    @discardableResult
    func logout() async throws -> MessageResponse {
        try await send("api/auth/logout", method: "POST")
    }

    // MARK: - Profile & feed

    func me() async throws -> MeResponse {
        try await send("api/me", method: "GET")
    }

    func feed() async throws -> FeedResponse {
        try await send("api/feed", method: "GET")
    }

    func search(_ q: String) async throws -> SearchResponse {
        let escaped = q.addingPercentEncoding(withAllowedCharacters: .urlQueryAllowed) ?? q
        return try await send("api/search?q=\(escaped)", method: "GET")
    }

    func archive(_ q: String) async throws -> ArchiveResponse {
        let escaped = q.addingPercentEncoding(withAllowedCharacters: .urlQueryAllowed) ?? q
        return try await send("api/archive?q=\(escaped)", method: "GET")
    }

    func updatePreferences(_ body: PreferencesRequest) async throws -> MeResponse {
        try await send("api/preferences", method: "PUT", body: body)
    }

    @discardableResult
    func registerDeviceToken(_ token: String) async throws -> MessageResponse {
        try await send("api/device-tokens", method: "POST", body: DeviceTokenRequest(platform: "ios", token: token))
    }

    @discardableResult
    func deleteDeviceToken(_ token: String) async throws -> MessageResponse {
        let escaped = token.addingPercentEncoding(withAllowedCharacters: .urlQueryAllowed) ?? token
        return try await send("api/device-tokens?token=\(escaped)", method: "DELETE")
    }

    // MARK: - Topics

    func addTopic(_ body: AddTopicRequest) async throws -> TopicResponse {
        try await send("api/topics", method: "POST", body: body)
    }

    func refreshTopic(_ id: Int) async throws -> TopicResponse {
        try await send("api/topics/\(id)/refresh", method: "POST")
    }

    func setMutes(_ id: Int, keywords: [String]) async throws -> TopicResponse {
        try await send("api/topics/\(id)/mutes", method: "PATCH", body: MuteRequest(muteKeywords: keywords))
    }

    func setDigestInclusion(_ id: Int, included: Bool) async throws -> TopicResponse {
        try await send("api/topics/\(id)/digest", method: "PATCH", body: DigestRequest(includeInDigest: included))
    }

    @discardableResult
    func markAllRead(_ id: Int) async throws -> MarkedResponse {
        try await send("api/topics/\(id)/read-all", method: "POST")
    }

    @discardableResult
    func reorderTopics(_ order: [Int]) async throws -> MessageResponse {
        try await send("api/topics/reorder", method: "POST", body: ReorderRequest(order: order))
    }

    @discardableResult
    func deleteTopic(_ id: Int) async throws -> MessageResponse {
        try await send("api/topics/\(id)", method: "DELETE")
    }

    // MARK: - Article actions

    @discardableResult
    func markRead(_ id: Int) async throws -> ReadResponse {
        try await send("api/articles/\(id)/read", method: "POST")
    }

    @discardableResult
    func markUnread(_ id: Int) async throws -> ReadResponse {
        try await send("api/articles/\(id)/read", method: "DELETE")
    }

    func summary(_ id: Int) async throws -> TldrResponse {
        try await send("api/articles/\(id)/summary", method: "POST")
    }

    // MARK: - Saved

    func saved() async throws -> SavedListResponse {
        try await send("api/saved", method: "GET")
    }

    func save(_ body: SaveRequest) async throws -> SaveResponse {
        try await send("api/saved", method: "POST", body: body)
    }

    @discardableResult
    func unsave(_ id: Int) async throws -> MessageResponse {
        try await send("api/saved/\(id)", method: "DELETE")
    }

    // MARK: - Core request pipeline

    private func send<T: Decodable>(_ path: String, method: String) async throws -> T {
        try await perform(path, method: method, bodyData: nil)
    }

    private func send<T: Decodable, B: Encodable>(_ path: String, method: String, body: B) async throws -> T {
        let data = try encoder.encode(body)
        return try await perform(path, method: method, bodyData: data)
    }

    private func perform<T: Decodable>(_ path: String, method: String, bodyData: Data?) async throws -> T {
        guard let url = URL(string: path, relativeTo: baseURL)?.absoluteURL else { throw APIError.transport }
        var request = URLRequest(url: url)
        request.httpMethod = method
        request.setValue("application/json", forHTTPHeaderField: "Accept")
        if let bodyData {
            request.setValue("application/json", forHTTPHeaderField: "Content-Type")
            request.httpBody = bodyData
        }
        if let token = tokenProvider(), !token.isEmpty {
            request.setValue("Bearer \(token)", forHTTPHeaderField: "Authorization")
        }

        let data: Data
        let response: URLResponse
        do {
            (data, response) = try await session.data(for: request)
        } catch {
            throw APIError.transport
        }

        guard let http = response as? HTTPURLResponse else { throw APIError.transport }
        guard (200...299).contains(http.statusCode) else { throw APIError.http(http.statusCode) }

        // Endpoints like logout/delete can return an empty 200/204 body.
        if data.isEmpty {
            if let type = T.self as? EmptyDecodable.Type { return type.empty as! T }
            throw APIError.decoding
        }
        do {
            return try decoder.decode(T.self, from: data)
        } catch {
            throw APIError.decoding
        }
    }
}

/// A response type that has a sensible value for an empty success body.
protocol EmptyDecodable: Decodable {
    static var empty: Self { get }
}

extension MessageResponse: EmptyDecodable {
    static var empty: MessageResponse { MessageResponse(message: nil) }
}
