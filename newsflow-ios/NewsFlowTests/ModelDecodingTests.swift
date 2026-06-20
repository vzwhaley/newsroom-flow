import XCTest
@testable import NewsFlow

/// Verifies that the API's snake_case JSON decodes into the Swift models the
/// same way the real `NewsFlowAPI` decoder does (`.convertFromSnakeCase`),
/// including the `decodeIfPresent` defaults for missing fields.
final class ModelDecodingTests: XCTestCase {

    private func makeDecoder() -> JSONDecoder {
        let d = JSONDecoder()
        d.keyDecodingStrategy = .convertFromSnakeCase
        return d
    }

    func testUserDecodesSnakeCaseAndDefaults() throws {
        let json = """
        {
          "id": 7,
          "name": "Ada",
          "email": "ada@example.com",
          "is_pro": true,
          "tier": "lifetime",
          "topic_limit": null,
          "topic_count": 5,
          "refresh_hour": 8,
          "digest_enabled": true,
          "digest_new_only": false
        }
        """.data(using: .utf8)!

        let user = try makeDecoder().decode(User.self, from: json)
        XCTAssertEqual(user.id, 7)
        XCTAssertEqual(user.name, "Ada")
        XCTAssertTrue(user.isPro)
        XCTAssertEqual(user.tier, "lifetime")
        XCTAssertNil(user.topicLimit)
        XCTAssertEqual(user.topicCount, 5)
        XCTAssertEqual(user.refreshHour, 8)
        XCTAssertTrue(user.digestEnabled)
        XCTAssertFalse(user.digestNewOnly)
        // Defaults for omitted keys:
        XCTAssertFalse(user.emailVerified)
        XCTAssertEqual(user.plan, "free")
        XCTAssertEqual(user.timezone, "UTC")
    }

    func testUserPowerFeatureListsDecode() throws {
        let json = """
        {
          "id": 1, "name": "Ada", "email": "a@b.com", "is_pro": true,
          "watch_keywords": ["Tesla", "SpaceX"],
          "blocked_sources": ["tabloid.com"]
        }
        """.data(using: .utf8)!
        let user = try makeDecoder().decode(User.self, from: json)
        XCTAssertEqual(user.watchKeywords, ["Tesla", "SpaceX"])
        XCTAssertEqual(user.blockedSources, ["tabloid.com"])

        // Absent lists default to empty (free users / older payloads).
        let bare = try makeDecoder().decode(User.self, from: """
        { "id": 2, "name": "B", "email": "b@c.com" }
        """.data(using: .utf8)!)
        XCTAssertTrue(bare.watchKeywords.isEmpty)
        XCTAssertTrue(bare.blockedSources.isEmpty)
    }

    func testTopicMuteKeywordsDecode() throws {
        let json = """
        {
          "id": 1, "name": "Tech", "mute_keywords": ["crypto"],
          "include_in_digest": true, "articles": []
        }
        """.data(using: .utf8)!
        let topic = try makeDecoder().decode(Topic.self, from: json)
        XCTAssertEqual(topic.muteKeywords, ["crypto"])
        XCTAssertTrue(topic.includeInDigest)
    }

    func testArticleDecodesAndDefaults() throws {
        let json = """
        {
          "id": 12,
          "headline": "Markets steady",
          "url": "https://example.com/a",
          "source": "Global Wire",
          "image_url": "https://example.com/i.jpg",
          "is_read": true,
          "published_at": "2026-06-20T06:00:00Z"
        }
        """.data(using: .utf8)!

        let a = try makeDecoder().decode(Article.self, from: json)
        XCTAssertEqual(a.id, 12)
        XCTAssertEqual(a.headline, "Markets steady")
        XCTAssertEqual(a.imageUrl, "https://example.com/i.jpg")
        XCTAssertTrue(a.isRead)
        XCTAssertEqual(a.publishedAt, "2026-06-20T06:00:00Z")
        // Defaults:
        XCTAssertEqual(a.description, "")
        XCTAssertEqual(a.fingerprint, "")
        XCTAssertNil(a.tldr)
        XCTAssertTrue(a.matches.isEmpty)
    }

    func testFeedResponseWithNestedTopicsAndChildren() throws {
        let json = """
        {
          "topics": [
            {
              "id": 1,
              "name": "World News",
              "articles": [
                { "id": 100, "headline": "A", "url": "https://x/a" }
              ],
              "children": [
                { "id": 2, "name": "Europe", "parent_id": 1, "articles": [] }
              ]
            }
          ],
          "saved_fingerprints": ["fp1", "fp2"],
          "watchlist": [
            { "id": 200, "headline": "Watch", "url": "https://x/w", "topic_name": "Tesla" }
          ],
          "watch_keywords": ["tesla"]
        }
        """.data(using: .utf8)!

        let feed = try makeDecoder().decode(FeedResponse.self, from: json)
        XCTAssertEqual(feed.topics.count, 1)
        let top = feed.topics[0]
        XCTAssertEqual(top.name, "World News")
        XCTAssertEqual(top.articles.count, 1)
        XCTAssertEqual(top.children.count, 1)
        XCTAssertEqual(top.children[0].name, "Europe")
        XCTAssertEqual(top.children[0].parentId, 1)
        XCTAssertEqual(feed.savedFingerprints, ["fp1", "fp2"])
        XCTAssertEqual(feed.watchlist.first?.topicName, "Tesla")
        XCTAssertEqual(feed.watchKeywords, ["tesla"])
    }

    func testSearchResponseDefaults() throws {
        let json = """
        { "q": "climate", "feed": [ { "id": 1, "headline": "H", "url": "https://x" } ] }
        """.data(using: .utf8)!

        let res = try makeDecoder().decode(SearchResponse.self, from: json)
        XCTAssertEqual(res.q, "climate")
        XCTAssertFalse(res.locked)         // default
        XCTAssertEqual(res.feed.count, 1)
        XCTAssertTrue(res.saved.isEmpty)   // default
    }

    func testAuthResponseDecodes() throws {
        let json = """
        { "token": "abc123", "user": { "id": 1, "name": "Ada", "email": "a@b.com" } }
        """.data(using: .utf8)!

        let auth = try makeDecoder().decode(AuthResponse.self, from: json)
        XCTAssertEqual(auth.token, "abc123")
        XCTAssertEqual(auth.user.id, 1)
        XCTAssertEqual(auth.user.plan, "free")  // default applied
    }
}
