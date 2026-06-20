package com.newsflow.android.data

import android.content.Context

/**
 * Tiny manual DI container — initialised once from NewsFlowApplication.
 */
object ServiceLocator {
    lateinit var authStore: AuthStore
        private set
    lateinit var apiClient: ApiClient
        private set

    fun init(context: Context) {
        authStore = AuthStore(context.applicationContext)
        apiClient = ApiClient(tokenProvider = { authStore.token })
    }

    val api: NewsFlowApi get() = apiClient.api
}
