package com.newsflow.android.push

import com.google.firebase.messaging.FirebaseMessaging
import com.newsflow.android.data.DeviceTokenRequest
import com.newsflow.android.data.ServiceLocator
import kotlinx.coroutines.CoroutineScope
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.launch

/**
 * Registers/unregisters this device's FCM token with the backend. Every call is
 * wrapped so it no-ops cleanly when Firebase isn't configured (no
 * google-services.json), keeping the app fully functional without push.
 */
object PushRegistrar {

    fun register() {
        runCatching {
            FirebaseMessaging.getInstance().token.addOnSuccessListener { token ->
                CoroutineScope(Dispatchers.IO).launch {
                    runCatching {
                        if (ServiceLocator.authStore.isLoggedIn) {
                            ServiceLocator.api.registerDeviceToken(DeviceTokenRequest("android", token))
                        }
                    }
                }
            }
        }
    }

    fun unregister() {
        runCatching {
            FirebaseMessaging.getInstance().token.addOnSuccessListener { token ->
                CoroutineScope(Dispatchers.IO).launch {
                    runCatching { ServiceLocator.api.deleteDeviceToken(token) }
                }
            }
        }
    }
}
