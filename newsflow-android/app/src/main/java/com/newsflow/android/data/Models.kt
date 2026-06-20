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
data class TldrResponse(val tldr: String? = null, val cached: Boolean = false)

@Serializable
data class MessageResponse(val message: String? = null)
