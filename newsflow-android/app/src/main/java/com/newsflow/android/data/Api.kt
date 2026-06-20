package com.newsflow.android.data

import com.jakewharton.retrofit2.converter.kotlinx.serialization.asConverterFactory
import com.newsflow.android.BuildConfig
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
import retrofit2.http.POST
import retrofit2.http.Path
import java.util.concurrent.TimeUnit

interface NewsFlowApi {
    @POST("api/auth/register")
    suspend fun register(@Body body: RegisterRequest): Response<AuthResponse>

    @POST("api/auth/login")
    suspend fun login(@Body body: LoginRequest): Response<AuthResponse>

    @POST("api/auth/logout")
    suspend fun logout(): Response<MessageResponse>

    @GET("api/me")
    suspend fun me(): Response<MeResponse>

    @GET("api/feed")
    suspend fun feed(): Response<FeedResponse>

    @POST("api/topics")
    suspend fun addTopic(@Body body: AddTopicRequest): Response<TopicResponse>

    @POST("api/topics/{id}/refresh")
    suspend fun refreshTopic(@Path("id") id: Long): Response<TopicResponse>

    @DELETE("api/topics/{id}")
    suspend fun deleteTopic(@Path("id") id: Long): Response<MessageResponse>

    @POST("api/articles/{id}/read")
    suspend fun markRead(@Path("id") id: Long): Response<ReadResponse>

    @DELETE("api/articles/{id}/read")
    suspend fun markUnread(@Path("id") id: Long): Response<ReadResponse>

    @POST("api/articles/{id}/summary")
    suspend fun summary(@Path("id") id: Long): Response<TldrResponse>

    @GET("api/saved")
    suspend fun saved(): Response<SavedListResponse>

    @POST("api/saved")
    suspend fun save(@Body body: SaveRequest): Response<SaveResponse>

    @DELETE("api/saved/{id}")
    suspend fun unsave(@Path("id") id: Long): Response<MessageResponse>
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
    val api: NewsFlowApi

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

        api = retrofit.create(NewsFlowApi::class.java)
    }
}
