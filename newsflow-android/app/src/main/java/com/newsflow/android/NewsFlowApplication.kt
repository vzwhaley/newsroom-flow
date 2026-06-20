package com.newsflow.android

import android.app.Application
import com.newsflow.android.data.ServiceLocator

class NewsFlowApplication : Application() {
    override fun onCreate() {
        super.onCreate()
        ServiceLocator.init(this)
    }
}
