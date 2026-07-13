package com.newsroomflow.android.data

import android.content.Context
import androidx.security.crypto.EncryptedSharedPreferences
import androidx.security.crypto.MasterKey

/**
 * Stores the Sanctum bearer token in EncryptedSharedPreferences (OS-managed
 * master key in the Android Keystore).
 */
class AuthStore(context: Context) {

    private val prefs = EncryptedSharedPreferences.create(
        context,
        "newsroomflow_secure_prefs",
        MasterKey.Builder(context).setKeyScheme(MasterKey.KeyScheme.AES256_GCM).build(),
        EncryptedSharedPreferences.PrefKeyEncryptionScheme.AES256_SIV,
        EncryptedSharedPreferences.PrefValueEncryptionScheme.AES256_GCM,
    )

    var token: String?
        get() = prefs.getString(KEY_TOKEN, null)
        set(value) {
            prefs.edit().apply {
                if (value.isNullOrEmpty()) remove(KEY_TOKEN) else putString(KEY_TOKEN, value)
            }.apply()
        }

    val isLoggedIn: Boolean get() = !token.isNullOrEmpty()

    fun clear() {
        prefs.edit().remove(KEY_TOKEN).apply()
    }

    private companion object {
        const val KEY_TOKEN = "auth_token"
    }
}
