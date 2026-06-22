package com.newsflow.android

import android.app.Application
import android.app.NotificationChannel
import android.app.NotificationManager
import android.os.Build
import com.google.android.gms.ads.MobileAds
import com.newsflow.android.data.ServiceLocator

class NewsFlowApplication : Application() {
    override fun onCreate() {
        super.onCreate()
        ServiceLocator.init(this)
        createNotificationChannel()
        // Initialize AdMob (no-op cost for Pro users — banners are gated in UI).
        runCatching { MobileAds.initialize(this) }
    }

    /** Channel referenced by the FCM default-channel meta-data + our notifications. */
    private fun createNotificationChannel() {
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.O) {
            val channel = NotificationChannel(
                PUSH_CHANNEL_ID,
                "Daily headlines",
                NotificationManager.IMPORTANCE_DEFAULT,
            ).apply { description = "Your daily NewsFlow summary and watchlist alerts." }
            getSystemService(NotificationManager::class.java)?.createNotificationChannel(channel)
        }
    }

    companion object {
        const val PUSH_CHANNEL_ID = "newsflow_default"
    }
}
