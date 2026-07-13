package com.newsroomflow.android.ui.screens

import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.padding
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.Article
import androidx.compose.material.icons.filled.Archive
import androidx.compose.material.icons.filled.Bookmark
import androidx.compose.material.icons.filled.Person
import androidx.compose.material.icons.filled.Search
import androidx.compose.material3.ExperimentalMaterial3Api
import androidx.compose.material3.Icon
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.NavigationBar
import androidx.compose.material3.NavigationBarItem
import androidx.compose.material3.Scaffold
import androidx.compose.material3.Text
import androidx.compose.material3.TopAppBar
import android.Manifest
import android.os.Build
import androidx.activity.compose.rememberLauncherForActivityResult
import androidx.activity.result.contract.ActivityResultContracts
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.vector.ImageVector
import androidx.compose.ui.text.font.FontWeight
import com.newsroomflow.android.push.PushRegistrar

private enum class MainTab(val label: String, val icon: ImageVector) {
    Feed("Feed", Icons.AutoMirrored.Filled.Article),
    Search("Search", Icons.Filled.Search),
    Saved("Saved", Icons.Filled.Bookmark),
    Archive("Archive", Icons.Filled.Archive),
    Account("Account", Icons.Filled.Person),
}

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun MainScreen(onSignOut: () -> Unit) {
    var tab by remember { mutableStateOf(MainTab.Feed) }

    // Register this device for push on first entry; request the Android 13+
    // notification permission so alerts can be shown.
    val permissionLauncher = rememberLauncherForActivityResult(
        ActivityResultContracts.RequestPermission(),
    ) { /* token registration is independent of display permission */ }
    LaunchedEffect(Unit) {
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.TIRAMISU) {
            permissionLauncher.launch(Manifest.permission.POST_NOTIFICATIONS)
        }
        PushRegistrar.register()
    }

    Scaffold(
        topBar = { TopAppBar(title = { BrandTitle() }) },
        bottomBar = {
            NavigationBar {
                MainTab.entries.forEach { t ->
                    NavigationBarItem(
                        selected = tab == t,
                        onClick = { tab = t },
                        icon = { Icon(t.icon, contentDescription = t.label) },
                        label = { Text(t.label) },
                    )
                }
            }
        },
    ) { padding ->
        Box(Modifier.fillMaxSize().padding(padding)) {
            when (tab) {
                MainTab.Feed -> FeedTab()
                MainTab.Search -> SearchTab()
                MainTab.Saved -> SavedTab()
                MainTab.Archive -> ArchiveTab()
                MainTab.Account -> AccountTab(onSignOut)
            }
        }
    }
}

@Composable
private fun BrandTitle() {
    Row(verticalAlignment = Alignment.CenterVertically) {
        Text("News", fontWeight = FontWeight.Bold, color = MaterialTheme.colorScheme.onBackground)
        Text("Flow", fontWeight = FontWeight.Bold, color = BrandBlue)
    }
}
