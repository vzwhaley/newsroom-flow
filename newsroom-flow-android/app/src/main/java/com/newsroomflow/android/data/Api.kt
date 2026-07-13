package com.newsroomflow.android.data

import com.jakewharton.retrofit2.converter.kotlinx.serialization.asConverterFactory
import com.newsroomflow.android.BuildConfig
import kotlinx.serialization.json.Json
import okhttp3.Interceptor
import okhttp3.MediaType.Companion.toMediaType
import okhttp3.OkHttpClient
import okhttp3.logging.HttpLoggingInterceptor
import retrofit2.Response
import retrofit2.Retrofit
import retrofit2.http.Body
import retrofit2.http.DELETE
import retrofit2.http.GET
import retrofit2.http.PATCH
import retrofit2.http.POST
import retrofit2.http.PUT
import retrofit2.http.Path
import retrofit2.http.Query
import java.util.concurrent.TimeUnit

interface NewsroomFlowApi {
    @POST("api/auth/register")
    suspend fun register(@Body body: RegisterRequest): Response<AuthResponse>

    @POST("api/auth/login")
    suspend fun login(@Body body: LoginRequest): Response<AuthResponse>

    @POST("api/auth/logout")
    suspend fun logout(): Response<MessageResponse>

    @POST("api/auth/resend-verification")
    suspend fun resendVerification(): Response<MessageResponse>

    @GET("api/me")
    suspend fun me(): Response<MeResponse>

    @GET("api/config")
    suspend fun config(): Response<ConfigResponse>

    @GET("api/feed")
    suspend fun feed(): Response<FeedResponse>

    @POST("api/topics")
    suspend fun addTopic(@Body body: AddTopicRequest): Response<TopicResponse>

    @POST("api/topics/{id}/refresh")
    suspend fun refreshTopic(@Path("id") id: Long): Response<TopicResponse>

    @PATCH("api/topics/{id}/mutes")
    suspend fun setMutes(@Path("id") id: Long, @Body body: MuteRequest): Response<TopicResponse>

    @PATCH("api/topics/{id}/digest")
    suspend fun setDigest(@Path("id") id: Long, @Body body: DigestRequest): Response<TopicResponse>

    @POST("api/topics/{id}/read-all")
    suspend fun markAllRead(@Path("id") id: Long): Response<MarkedResponse>

    @POST("api/topics/reorder")
    suspend fun reorderTopics(@Body body: ReorderRequest): Response<MessageResponse>

    @DELETE("api/topics/{id}")
    suspend fun deleteTopic(@Path("id") id: Long): Response<MessageResponse>

    @POST("api/areas")
    suspend fun addArea(@Body body: AreaRequest): Response<AreaResponse>

    @PATCH("api/areas/{id}")
    suspend fun updateArea(@Path("id") id: Long, @Body body: AreaRequest): Response<AreaResponse>

    @DELETE("api/areas/{id}")
    suspend fun deleteArea(@Path("id") id: Long): Response<MessageResponse>

    @POST("api/articles/{id}/read")
    suspend fun markRead(@Path("id") id: Long): Response<ReadResponse>

    @DELETE("api/articles/{id}/read")
    suspend fun markUnread(@Path("id") id: Long): Response<ReadResponse>

    @POST("api/articles/{id}/summary")
    suspend fun summary(@Path("id") id: Long): Response<TldrResponse>

    @POST("api/articles/{id}/share")
    suspend fun shareArticle(@Path("id") id: Long): Response<ShareResponse>

    @GET("api/briefing")
    suspend fun briefing(): Response<BriefingResponse>

    @GET("api/saved")
    suspend fun saved(): Response<SavedListResponse>

    @POST("api/saved")
    suspend fun save(@Body body: SaveRequest): Response<SaveResponse>

    @DELETE("api/saved/{id}")
    suspend fun unsave(@Path("id") id: Long): Response<MessageResponse>

    @GET("api/search")
    suspend fun search(@Query("q") q: String): Response<SearchResponse>

    @GET("api/archive")
    suspend fun archive(@Query("q") q: String): Response<ArchiveResponse>

    @PUT("api/preferences")
    suspend fun updatePreferences(@Body body: PreferencesRequest): Response<MeResponse>

    @POST("api/device-tokens")
    suspend fun registerDeviceToken(@Body body: DeviceTokenRequest): Response<MessageResponse>

    @DELETE("api/device-tokens")
    suspend fun deleteDeviceToken(@Query("token") token: String): Response<MessageResponse>
}

/** Attaches the bearer token (when present) + a JSON Accept header. */
class AuthInterceptor(private val tokenProvider: () -> String?) : Interceptor {
    override fun intercept(chain: Interceptor.Chain): okhttp3.Response {
        val builder = chain.request().newBuilder().header("Accept", "application/json")
        tokenProvider()?.takeIf { it.isNotEmpty() }?.let {
            builder.header("Authorization", "Bearer $it")
        }
        return chain.proceed(builder.build())
    }
}

/** Builds and holds the Retrofit-backed API. */
class ApiClient(
    baseUrl: String = BuildConfig.DEFAULT_API_BASE_URL,
    tokenProvider: () -> String?,
) {
    val api: NewsroomFlowApi

    init {
        val json = Json {
            ignoreUnknownKeys = true
            isLenient = true
            explicitNulls = false
        }
        val logging = HttpLoggingInterceptor().apply {
            level = if (BuildConfig.DEBUG) HttpLoggingInterceptor.Level.BASIC
            else HttpLoggingInterceptor.Level.NONE
        }
        val client = OkHttpClient.Builder()
            .addInterceptor(AuthInterceptor(tokenProvider))
            .addInterceptor(logging)
            .connectTimeout(15, TimeUnit.SECONDS)
            .readTimeout(30, TimeUnit.SECONDS)
            .build()

        val retrofit = Retrofit.Builder()
            .baseUrl(if (baseUrl.endsWith("/")) baseUrl else "$baseUrl/")
            .addConverterFactory(json.asConverterFactory("application/json".toMediaType()))
            .client(client)
            .build()

        api = retrofit.create(NewsroomFlowApi::class.java)
    }
}
