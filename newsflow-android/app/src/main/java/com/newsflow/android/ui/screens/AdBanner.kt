package com.newsflow.android.ui.screens

import android.content.Intent
import android.net.Uri
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.padding
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
import androidx.compose.runtime.Composable
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.compose.ui.viewinterop.AndroidView
import com.google.android.gms.ads.AdRequest
import com.google.android.gms.ads.AdSize
import com.google.android.gms.ads.AdView
import com.newsflow.android.BuildConfig
import com.newsflow.android.data.AdConfigStore

/** Google's OFFICIAL TEST banner unit — safe in dev builds. Production receives
 *  the real unit ID from `GET /api/config` → ads.units.feed_tab. */
const val TEST_BANNER_UNIT = "ca-app-pub-3940256099942544/6300978111"

/**
 * Free-tier AdMob banner shown at the bottom of the Feed. Renders nothing for
 * Pro users — Pro is 100% ad-free. Gating happens here (cheap skip) AND
 * server-side via /api/config, which omits ad units for Pro tiers, so even a
 * tampered client has no unit ID to load against.
 */
@Composable
fun AdBanner(isPro: Boolean, placement: String = "feed_tab", modifier: Modifier = Modifier) {
    if (isPro) return

    val payload by AdConfigStore.payload.collectAsState()
    // Trust the server's explicit show=false once config has loaded.
    if (payload != null && payload?.ads?.show == false) return

    val unitId = payload?.ads?.units?.get(placement) ?: TEST_BANNER_UNIT
    val context = LocalContext.current

    Column(
        modifier = modifier.fillMaxWidth().padding(vertical = 8.dp),
        horizontalAlignment = Alignment.CenterHorizontally,
        verticalArrangement = Arrangement.spacedBy(2.dp),
    ) {
        AndroidView(
            modifier = Modifier.fillMaxWidth(),
            factory = { ctx ->
                AdView(ctx).apply {
                    setAdSize(AdSize.BANNER)
                    adUnitId = unitId
                    loadAd(AdRequest.Builder().build())
                }
            },
            update = { view ->
                if (view.adUnitId != unitId) {
                    view.adUnitId = unitId
                    view.loadAd(AdRequest.Builder().build())
                }
            },
        )
        TextButton(onClick = {
            runCatching {
                context.startActivity(Intent(Intent.ACTION_VIEW, Uri.parse(BuildConfig.DEFAULT_API_BASE_URL + "/pricing")))
            }
        }) {
            Text("Remove Ads — Upgrade To Pro", fontSize = 12.sp)
        }
    }
}
