package com.newsflow.android.data

import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow

/**
 * In-memory cache of `GET /api/config`. Refreshed on sign-in. AdBanner reads the
 * unit ID from `unitIdFor("feed_tab")` and falls back to the AdMob TEST banner
 * when the store is empty (offline / pre-fetch / unconfigured server). Memory-
 * only on purpose, so a server reconfigure isn't masked by a stale persisted ID.
 */
object AdConfigStore {

    private val _payload = MutableStateFlow<ConfigData?>(null)
    val payload: StateFlow<ConfigData?> = _payload.asStateFlow()

    /** Fetch /api/config and store it. Failures are silent (safe default). */
    suspend fun refresh(): Boolean = runCatching {
        val res = ServiceLocator.api.config()
        if (res.isSuccessful) {
            _payload.value = res.body()?.data
            true
        } else {
            false
        }
    }.getOrDefault(false)

    fun unitIdFor(placement: String): String? = _payload.value?.ads?.units?.get(placement)

    fun showAds(): Boolean = _payload.value?.ads?.show == true

    fun clear() {
        _payload.value = null
    }
}
