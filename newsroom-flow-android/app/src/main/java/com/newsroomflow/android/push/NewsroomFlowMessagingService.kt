package com.newsroomflow.android.push

import android.app.NotificationManager
import android.app.PendingIntent
import android.content.Intent
import android.net.Uri
import androidx.core.app.NotificationCompat
import com.google.firebase.messaging.FirebaseMessagingService
import com.google.firebase.messaging.RemoteMessage
import com.newsroomflow.android.NewsroomFlowApplication
import com.newsroomflow.android.R
import com.newsroomflow.android.data.DeviceTokenRequest
import com.newsroomflow.android.data.ServiceLocator
import kotlinx.coroutines.CoroutineScope
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.launch

/**
 * Receives FCM token refreshes and incoming push messages. Only active once
 * google-services.json is added to the app module; otherwise Firebase never
 * initializes and these callbacks simply don't fire.
 */
class NewsroomFlowMessagingService : FirebaseMessagingService() {

    /** FCM rotated the registration token — re-register it if we're signed in. */
    override fun onNewToken(token: String) {
        CoroutineScope(Dispatchers.IO).launch {
            runCatching {
                if (ServiceLocator.authStore.isLoggedIn) {
                    ServiceLocator.api.registerDeviceToken(DeviceTokenRequest("android", token))
                }
            }
        }
    }

    override fun onMessageReceived(message: RemoteMessage) {
        // Foreground delivery: build our own notification. (When the app is
        // backgrounded, FCM displays the notification block automatically.)
        val title = message.notification?.title ?: "NewsroomFlow"
        val body = message.notification?.body ?: return
        val url = message.data["url"]

        val intent = if (url != null) {
            Intent(Intent.ACTION_VIEW, Uri.parse(url))
        } else {
            packageManager.getLaunchIntentForPackage(packageName) ?: return
        }
        val pending = PendingIntent.getActivity(
            this, 0, intent,
            PendingIntent.FLAG_IMMUTABLE or PendingIntent.FLAG_UPDATE_CURRENT,
        )

        val notification = NotificationCompat.Builder(this, NewsroomFlowApplication.PUSH_CHANNEL_ID)
            .setSmallIcon(R.mipmap.ic_launcher)
            .setContentTitle(title)
            .setContentText(body)
            .setStyle(NotificationCompat.BigTextStyle().bigText(body))
            .setAutoCancel(true)
            .setContentIntent(pending)
            .build()

        // Stable per-message ID so two pushes arriving in the same millisecond
        // don't overwrite each other (and repeats of one message collapse).
        val id = (message.messageId ?: (url ?: "") + body).hashCode()
        getSystemService(NotificationManager::class.java)
            ?.notify(id, notification)
    }
}
