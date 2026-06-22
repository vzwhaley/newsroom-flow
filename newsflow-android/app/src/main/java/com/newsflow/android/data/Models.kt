package com.newsflow.android.data

import kotlinx.serialization.SerialName
import kotlinx.serialization.Serializable

@Serializable
data class RegisterRequest(
    val name: String,
    val email: String,
    val password: String,
    @SerialName("device_name") val deviceName: String = "Android",
)

@Serializable
data class LoginRequest(
    val email: String,
    val password: String,
    @SerialName("device_name") val deviceName: String = "Android",
)

@Serializable
data class AuthResponse(
    val token: String,
    val user: User,
)

@Serializable
data class User(
    val id: Long,
    val name: String,
    val email: String,
    @SerialName("email_verified") val emailVerified: Boolean = false,
    val plan: String = "free",
    @SerialName("is_pro") val isPro: Boolean = false,
    val tier: String? = null,
    @SerialName("topic_limit") val topicLimit: Int? = null,
    @SerialName("topic_count") val topicCount: Int = 0,
    @SerialName("refresh_hour") val refreshHour: Int = 6,
    val timezone: String = "UTC",
    @SerialName("digest_enabled") val digestEnabled: Boolean = false,
    @SerialName("digest_new_only") val digestNewOnly: Boolean = false,
    @SerialName("push_enabled") val pushEnabled: Boolean = false,
    @SerialName("watch_keywords") val watchKeywords: List<String> = emptyList(),
    @SerialName("blocked_sources") val blockedSources: List<String> = emptyList(),
)

@Serializable
data class MeResponse(val user: User)

@Serializable
data class Article(
    val id: Long,
    val headline: String,
    val description: String = "",
    val url: String,
    val source: String? = null,
    @SerialName("image_url") val imageUrl: String? = null,
    val fingerprint: String = "",
    @SerialName("published_at") val publishedAt: String? = null,
    @SerialName("is_read") val isRead: Boolean = false,
    val tldr: String? = null,
    // Only present on watchlist hits:
    @SerialName("topic_name") val topicName: String? = null,
    val matches: List<String> = emptyList(),
)

@Serializable
data class Topic(
    val id: Long,
    val name: String,
    @SerialName("parent_id") val parentId: Long? = null,
    @SerialName("mute_keywords") val muteKeywords: List<String> = emptyList(),
    @SerialName("include_in_digest") val includeInDigest: Boolean = false,
    @SerialName("last_refreshed_at") val lastRefreshedAt: String? = null,
    val articles: List<Article> = emptyList(),
    val children: List<Topic> = emptyList(),
)

@Serializable
data class FeedResponse(
    val topics: List<Topic> = emptyList(),
    @SerialName("saved_fingerprints") val savedFingerprints: List<String> = emptyList(),
    val watchlist: List<Article> = emptyList(),
    @SerialName("watch_keywords") val watchKeywords: List<String> = emptyList(),
)

@Serializable
data class AddTopicRequest(
    val name: String,
    @SerialName("parent_id") val parentId: Long? = null,
)

@Serializable
data class TopicResponse(val topic: Topic)

@Serializable
data class ReadResponse(@SerialName("is_read") val isRead: Boolean)

@Serializable
data class MarkedResponse(val marked: Int = 0)

@Serializable
data class MuteRequest(@SerialName("mute_keywords") val muteKeywords: List<String>)

@Serializable
data class ReorderRequest(val order: List<Long>)

@Serializable
data class DigestRequest(@SerialName("include_in_digest") val includeInDigest: Boolean)

@Serializable
data class TldrResponse(val tldr: String? = null, val cached: Boolean = false)

@Serializable
data class MessageResponse(val message: String? = null)

@Serializable
data class SavedItem(
    val id: Long,
    val headline: String,
    val description: String = "",
    val url: String,
    val source: String? = null,
    @SerialName("image_url") val imageUrl: String? = null,
    @SerialName("topic_name") val topicName: String? = null,
    @SerialName("saved_at") val savedAt: String? = null,
)

@Serializable
data class SavedListResponse(val saved: List<SavedItem> = emptyList())

@Serializable
data class SaveRequest(
    val headline: String,
    val description: String? = null,
    val url: String,
    val source: String? = null,
    @SerialName("image_url") val imageUrl: String? = null,
    @SerialName("topic_name") val topicName: String? = null,
)

@Serializable
data class SaveResponse(val saved: SavedItem)

@Serializable
data class SearchItem(
    val id: Long,
    val headline: String,
    val description: String = "",
    val url: String,
    val source: String? = null,
    @SerialName("topic_name") val topicName: String? = null,
    @SerialName("is_read") val isRead: Boolean = false,
)

@Serializable
data class SearchResponse(
    val locked: Boolean = false,
    val q: String = "",
    val feed: List<SearchItem> = emptyList(),
    val saved: List<SearchItem> = emptyList(),
)

@Serializable
data class ArchivedItem(
    val id: Long,
    val headline: String,
    val description: String = "",
    val url: String,
    val source: String? = null,
    @SerialName("topic_name") val topicName: String? = null,
    @SerialName("archived_at") val archivedAt: String? = null,
)

@Serializable
data class ArchiveResponse(
    val locked: Boolean = false,
    val q: String = "",
    val articles: List<ArchivedItem> = emptyList(),
)

@Serializable
data class PreferencesRequest(
    @SerialName("refresh_hour") val refreshHour: Int,
    val timezone: String,
    @SerialName("digest_enabled") val digestEnabled: Boolean,
    @SerialName("digest_new_only") val digestNewOnly: Boolean,
    @SerialName("push_enabled") val pushEnabled: Boolean = false,
    @SerialName("watch_keywords") val watchKeywords: List<String> = emptyList(),
    @SerialName("blocked_sources") val blockedSources: List<String> = emptyList(),
)

@Serializable
data class DeviceTokenRequest(val platform: String, val token: String)

@Serializable
data class ConfigResponse(val data: ConfigData)

@Serializable
data class ConfigData(
    val plan: String = "free",
    @SerialName("subscription_tier") val subscriptionTier: String? = null,
    val ads: AdsConfig = AdsConfig(),
)

@Serializable
data class AdsConfig(
    val show: Boolean = false,
    val units: Map<String, String>? = null,
    @SerialName("app_id") val appId: Map<String, String?>? = null,
)
