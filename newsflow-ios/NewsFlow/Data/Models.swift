import Foundation

// MARK: - Requests (encoded with .convertToSnakeCase)

struct RegisterRequest: Encodable {
    let name: String
    let email: String
    let password: String
    var deviceName: String = "iOS"
}

struct LoginRequest: Encodable {
    let email: String
    let password: String
    var deviceName: String = "iOS"
}

struct AddTopicRequest: Encodable {
    let name: String
    var parentId: Int? = nil
}

struct SaveRequest: Encodable {
    let headline: String
    let description: String?
    let url: String
    let source: String?
    let imageUrl: String?
    let topicName: String?
}

struct PreferencesRequest: Encodable {
    let refreshHour: Int
    let timezone: String
    let digestEnabled: Bool
    let digestNewOnly: Bool
    let pushEnabled: Bool
    let watchKeywords: [String]
    let blockedSources: [String]
}

struct DeviceTokenRequest: Encodable {
    let platform: String
    let token: String
}

struct MuteRequest: Encodable {
    let muteKeywords: [String]
}

struct ReorderRequest: Encodable {
    let order: [Int]
}

struct DigestRequest: Encodable {
    let includeInDigest: Bool
}

// MARK: - Responses (decoded with .convertFromSnakeCase)

struct AuthResponse: Decodable {
    let token: String
    let user: User
}

struct MeResponse: Decodable {
    let user: User
}

struct User: Decodable, Identifiable {
    let id: Int
    let name: String
    let email: String
    let emailVerified: Bool
    let plan: String
    let isPro: Bool
    let tier: String?
    let topicLimit: Int?
    let topicCount: Int
    let refreshHour: Int
    let timezone: String
    let digestEnabled: Bool
    let digestNewOnly: Bool
    let pushEnabled: Bool
    let watchKeywords: [String]
    let blockedSources: [String]

    init(from decoder: Decoder) throws {
        let c = try decoder.container(keyedBy: CodingKeys.self)
        id = try c.decode(Int.self, forKey: .id)
        name = try c.decode(String.self, forKey: .name)
        email = try c.decode(String.self, forKey: .email)
        emailVerified = try c.decodeIfPresent(Bool.self, forKey: .emailVerified) ?? false
        plan = try c.decodeIfPresent(String.self, forKey: .plan) ?? "free"
        isPro = try c.decodeIfPresent(Bool.self, forKey: .isPro) ?? false
        tier = try c.decodeIfPresent(String.self, forKey: .tier)
        topicLimit = try c.decodeIfPresent(Int.self, forKey: .topicLimit)
        topicCount = try c.decodeIfPresent(Int.self, forKey: .topicCount) ?? 0
        refreshHour = try c.decodeIfPresent(Int.self, forKey: .refreshHour) ?? 6
        timezone = try c.decodeIfPresent(String.self, forKey: .timezone) ?? "UTC"
        digestEnabled = try c.decodeIfPresent(Bool.self, forKey: .digestEnabled) ?? false
        digestNewOnly = try c.decodeIfPresent(Bool.self, forKey: .digestNewOnly) ?? false
        pushEnabled = try c.decodeIfPresent(Bool.self, forKey: .pushEnabled) ?? false
        watchKeywords = try c.decodeIfPresent([String].self, forKey: .watchKeywords) ?? []
        blockedSources = try c.decodeIfPresent([String].self, forKey: .blockedSources) ?? []
    }
}

struct Article: Decodable, Identifiable {
    let id: Int
    let headline: String
    let description: String
    let url: String
    let source: String?
    let imageUrl: String?
    let fingerprint: String
    let publishedAt: String?
    let isRead: Bool
    let tldr: String?
    // Only present on watchlist hits:
    let topicName: String?
    let matches: [String]

    init(from decoder: Decoder) throws {
        let c = try decoder.container(keyedBy: CodingKeys.self)
        id = try c.decode(Int.self, forKey: .id)
        headline = try c.decode(String.self, forKey: .headline)
        description = try c.decodeIfPresent(String.self, forKey: .description) ?? ""
        url = try c.decode(String.self, forKey: .url)
        source = try c.decodeIfPresent(String.self, forKey: .source)
        imageUrl = try c.decodeIfPresent(String.self, forKey: .imageUrl)
        fingerprint = try c.decodeIfPresent(String.self, forKey: .fingerprint) ?? ""
        publishedAt = try c.decodeIfPresent(String.self, forKey: .publishedAt)
        isRead = try c.decodeIfPresent(Bool.self, forKey: .isRead) ?? false
        tldr = try c.decodeIfPresent(String.self, forKey: .tldr)
        topicName = try c.decodeIfPresent(String.self, forKey: .topicName)
        matches = try c.decodeIfPresent([String].self, forKey: .matches) ?? []
    }
}

struct Topic: Decodable, Identifiable {
    let id: Int
    let name: String
    let parentId: Int?
    let muteKeywords: [String]
    let includeInDigest: Bool
    let lastRefreshedAt: String?
    let articles: [Article]
    let children: [Topic]

    init(from decoder: Decoder) throws {
        let c = try decoder.container(keyedBy: CodingKeys.self)
        id = try c.decode(Int.self, forKey: .id)
        name = try c.decode(String.self, forKey: .name)
        parentId = try c.decodeIfPresent(Int.self, forKey: .parentId)
        muteKeywords = try c.decodeIfPresent([String].self, forKey: .muteKeywords) ?? []
        includeInDigest = try c.decodeIfPresent(Bool.self, forKey: .includeInDigest) ?? false
        lastRefreshedAt = try c.decodeIfPresent(String.self, forKey: .lastRefreshedAt)
        articles = try c.decodeIfPresent([Article].self, forKey: .articles) ?? []
        children = try c.decodeIfPresent([Topic].self, forKey: .children) ?? []
    }
}

struct FeedResponse: Decodable {
    let topics: [Topic]
    let savedFingerprints: [String]
    let watchlist: [Article]
    let watchKeywords: [String]

    init(from decoder: Decoder) throws {
        let c = try decoder.container(keyedBy: CodingKeys.self)
        topics = try c.decodeIfPresent([Topic].self, forKey: .topics) ?? []
        savedFingerprints = try c.decodeIfPresent([String].self, forKey: .savedFingerprints) ?? []
        watchlist = try c.decodeIfPresent([Article].self, forKey: .watchlist) ?? []
        watchKeywords = try c.decodeIfPresent([String].self, forKey: .watchKeywords) ?? []
    }
}

struct TopicResponse: Decodable {
    let topic: Topic
}

struct ReadResponse: Decodable {
    let isRead: Bool
}

struct MarkedResponse: Decodable {
    let marked: Int
}

struct TldrResponse: Decodable {
    let tldr: String?
    let cached: Bool

    init(from decoder: Decoder) throws {
        let c = try decoder.container(keyedBy: CodingKeys.self)
        tldr = try c.decodeIfPresent(String.self, forKey: .tldr)
        cached = try c.decodeIfPresent(Bool.self, forKey: .cached) ?? false
    }
}

struct MessageResponse: Decodable {
    let message: String?
}

struct SavedItem: Decodable, Identifiable {
    let id: Int
    let headline: String
    let description: String
    let url: String
    let source: String?
    let imageUrl: String?
    let topicName: String?
    let savedAt: String?

    init(from decoder: Decoder) throws {
        let c = try decoder.container(keyedBy: CodingKeys.self)
        id = try c.decode(Int.self, forKey: .id)
        headline = try c.decode(String.self, forKey: .headline)
        description = try c.decodeIfPresent(String.self, forKey: .description) ?? ""
        url = try c.decode(String.self, forKey: .url)
        source = try c.decodeIfPresent(String.self, forKey: .source)
        imageUrl = try c.decodeIfPresent(String.self, forKey: .imageUrl)
        topicName = try c.decodeIfPresent(String.self, forKey: .topicName)
        savedAt = try c.decodeIfPresent(String.self, forKey: .savedAt)
    }
}

struct SavedListResponse: Decodable {
    let saved: [SavedItem]

    init(from decoder: Decoder) throws {
        let c = try decoder.container(keyedBy: CodingKeys.self)
        saved = try c.decodeIfPresent([SavedItem].self, forKey: .saved) ?? []
    }
}

struct SaveResponse: Decodable {
    let saved: SavedItem
}

struct SearchItem: Decodable, Identifiable {
    let id: Int
    let headline: String
    let description: String
    let url: String
    let source: String?
    let topicName: String?
    let isRead: Bool

    init(from decoder: Decoder) throws {
        let c = try decoder.container(keyedBy: CodingKeys.self)
        id = try c.decode(Int.self, forKey: .id)
        headline = try c.decode(String.self, forKey: .headline)
        description = try c.decodeIfPresent(String.self, forKey: .description) ?? ""
        url = try c.decode(String.self, forKey: .url)
        source = try c.decodeIfPresent(String.self, forKey: .source)
        topicName = try c.decodeIfPresent(String.self, forKey: .topicName)
        isRead = try c.decodeIfPresent(Bool.self, forKey: .isRead) ?? false
    }
}

struct SearchResponse: Decodable {
    let locked: Bool
    let q: String
    let feed: [SearchItem]
    let saved: [SearchItem]

    init(from decoder: Decoder) throws {
        let c = try decoder.container(keyedBy: CodingKeys.self)
        locked = try c.decodeIfPresent(Bool.self, forKey: .locked) ?? false
        q = try c.decodeIfPresent(String.self, forKey: .q) ?? ""
        feed = try c.decodeIfPresent([SearchItem].self, forKey: .feed) ?? []
        saved = try c.decodeIfPresent([SearchItem].self, forKey: .saved) ?? []
    }
}

struct ArchivedItem: Decodable, Identifiable {
    let id: Int
    let headline: String
    let description: String
    let url: String
    let source: String?
    let topicName: String?
    let archivedAt: String?

    init(from decoder: Decoder) throws {
        let c = try decoder.container(keyedBy: CodingKeys.self)
        id = try c.decode(Int.self, forKey: .id)
        headline = try c.decode(String.self, forKey: .headline)
        description = try c.decodeIfPresent(String.self, forKey: .description) ?? ""
        url = try c.decode(String.self, forKey: .url)
        source = try c.decodeIfPresent(String.self, forKey: .source)
        topicName = try c.decodeIfPresent(String.self, forKey: .topicName)
        archivedAt = try c.decodeIfPresent(String.self, forKey: .archivedAt)
    }
}

struct ArchiveResponse: Decodable {
    let locked: Bool
    let q: String
    let articles: [ArchivedItem]

    init(from decoder: Decoder) throws {
        let c = try decoder.container(keyedBy: CodingKeys.self)
        locked = try c.decodeIfPresent(Bool.self, forKey: .locked) ?? false
        q = try c.decodeIfPresent(String.self, forKey: .q) ?? ""
        articles = try c.decodeIfPresent([ArchivedItem].self, forKey: .articles) ?? []
    }
}
