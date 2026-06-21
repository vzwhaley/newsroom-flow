import XCTest
@testable import NewsFlow

/// Verifies request bodies serialize to the snake_case keys the Laravel API
/// expects, matching the real `NewsFlowAPI` encoder (`.convertToSnakeCase`).
final class RequestEncodingTests: XCTestCase {

    private func makeEncoder() -> JSONEncoder {
        let e = JSONEncoder()
        e.keyEncodingStrategy = .convertToSnakeCase
        return e
    }

    private func object(_ value: some Encodable) throws -> [String: Any] {
        let data = try makeEncoder().encode(value)
        let obj = try JSONSerialization.jsonObject(with: data)
        return obj as! [String: Any]
    }

    func testRegisterRequestUsesDeviceNameKey() throws {
        let dict = try object(
            RegisterRequest(name: "Ada", email: "a@b.com", password: "secret12", deviceName: "iPhone 16")
        )
        XCTAssertEqual(dict["name"] as? String, "Ada")
        XCTAssertEqual(dict["email"] as? String, "a@b.com")
        XCTAssertEqual(dict["password"] as? String, "secret12")
        XCTAssertEqual(dict["device_name"] as? String, "iPhone 16")
        XCTAssertNil(dict["deviceName"])
    }

    func testAddTopicOmitsNilParentId() throws {
        let dict = try object(AddTopicRequest(name: "World News"))
        XCTAssertEqual(dict["name"] as? String, "World News")
        // A nil Optional is omitted (not encoded as null) by JSONEncoder.
        XCTAssertNil(dict["parent_id"])
        XCTAssertNil(dict["parentId"])
    }

    func testAddTopicEncodesParentId() throws {
        let dict = try object(AddTopicRequest(name: "Europe", parentId: 3))
        XCTAssertEqual(dict["parent_id"] as? Int, 3)
    }

    func testPreferencesRequestSnakeCase() throws {
        let dict = try object(
            PreferencesRequest(
                refreshHour: 6,
                timezone: "America/New_York",
                digestEnabled: true,
                digestNewOnly: false,
                pushEnabled: true,
                watchKeywords: ["Tesla"],
                blockedSources: ["tabloid.com"]
            )
        )
        XCTAssertEqual(dict["refresh_hour"] as? Int, 6)
        XCTAssertEqual(dict["timezone"] as? String, "America/New_York")
        XCTAssertEqual(dict["digest_enabled"] as? Bool, true)
        XCTAssertEqual(dict["digest_new_only"] as? Bool, false)
        XCTAssertEqual(dict["push_enabled"] as? Bool, true)
        XCTAssertEqual(dict["watch_keywords"] as? [String], ["Tesla"])
        XCTAssertEqual(dict["blocked_sources"] as? [String], ["tabloid.com"])
    }

    func testDeviceTokenRequestSnakeCase() throws {
        let dict = try object(DeviceTokenRequest(platform: "ios", token: "apns-abc"))
        XCTAssertEqual(dict["platform"] as? String, "ios")
        XCTAssertEqual(dict["token"] as? String, "apns-abc")
    }

    func testMuteAndReorderRequestsSnakeCase() throws {
        let mute = try object(MuteRequest(muteKeywords: ["crypto", "nft"]))
        XCTAssertEqual(mute["mute_keywords"] as? [String], ["crypto", "nft"])

        let reorder = try object(ReorderRequest(order: [3, 1, 2]))
        XCTAssertEqual(reorder["order"] as? [Int], [3, 1, 2])

        let digest = try object(DigestRequest(includeInDigest: false))
        XCTAssertEqual(digest["include_in_digest"] as? Bool, false)
    }

    func testSaveRequestSnakeCase() throws {
        let dict = try object(
            SaveRequest(headline: "H", description: "D", url: "https://x", source: "Wire", imageUrl: "https://x/i.jpg", topicName: "World News")
        )
        XCTAssertEqual(dict["headline"] as? String, "H")
        XCTAssertEqual(dict["image_url"] as? String, "https://x/i.jpg")
        XCTAssertEqual(dict["topic_name"] as? String, "World News")
    }
}
