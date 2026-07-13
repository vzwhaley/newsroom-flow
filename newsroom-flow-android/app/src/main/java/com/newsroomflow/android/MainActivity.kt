package com.newsroomflow.android

import android.os.Bundle
import androidx.activity.ComponentActivity
import androidx.activity.compose.setContent
import androidx.activity.enableEdgeToEdge
import com.newsroomflow.android.ui.AppRoot
import com.newsroomflow.android.ui.theme.NewsroomFlowTheme

class MainActivity : ComponentActivity() {
    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        enableEdgeToEdge()
        setContent {
            NewsroomFlowTheme {
                AppRoot()
            }
        }
    }
}
