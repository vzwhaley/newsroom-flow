package com.newsflow.android.ui.screens

import android.content.Intent
import android.net.Uri
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.verticalScroll
import androidx.compose.material3.Button
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.Divider
import androidx.compose.material3.DropdownMenu
import androidx.compose.material3.DropdownMenuItem
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.OutlinedButton
import androidx.compose.material3.Switch
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
import androidx.compose.runtime.Composable
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import androidx.lifecycle.viewmodel.compose.viewModel
import com.newsflow.android.BuildConfig
import com.newsflow.android.data.PreferencesRequest
import com.newsflow.android.data.ServiceLocator
import com.newsflow.android.data.User
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.launch
import java.util.TimeZone

class AccountViewModel : ViewModel() {
    data class State(
        val user: User? = null,
        val refreshHour: Int = 6,
        val digestEnabled: Boolean = false,
        val digestNewOnly: Boolean = false,
        val watchKeywords: List<String> = emptyList(),
        val blockedSources: List<String> = emptyList(),
        val saving: Boolean = false,
        val saved: Boolean = false,
    )

    private val _state = MutableStateFlow(State())
    val state: StateFlow<State> = _state

    init {
        viewModelScope.launch {
            runCatching { ServiceLocator.api.me() }.getOrNull()?.body()?.user?.let { u ->
                _state.value = State(
                    user = u, refreshHour = u.refreshHour,
                    digestEnabled = u.digestEnabled, digestNewOnly = u.digestNewOnly,
                    watchKeywords = u.watchKeywords, blockedSources = u.blockedSources,
                )
            }
        }
    }

    fun setHour(h: Int) { _state.value = _state.value.copy(refreshHour = h, saved = false) }
    fun setDigest(b: Boolean) { _state.value = _state.value.copy(digestEnabled = b, saved = false) }
    fun setNewOnly(b: Boolean) { _state.value = _state.value.copy(digestNewOnly = b, saved = false) }

    fun addWatch(k: String) {
        if (k in _state.value.watchKeywords) return
        _state.value = _state.value.copy(watchKeywords = _state.value.watchKeywords + k, saved = false)
    }
    fun removeWatch(k: String) {
        _state.value = _state.value.copy(watchKeywords = _state.value.watchKeywords - k, saved = false)
    }
    fun addBlocked(s: String) {
        if (s in _state.value.blockedSources) return
        _state.value = _state.value.copy(blockedSources = _state.value.blockedSources + s, saved = false)
    }
    fun removeBlocked(s: String) {
        _state.value = _state.value.copy(blockedSources = _state.value.blockedSources - s, saved = false)
    }

    fun save() {
        val s = _state.value
        viewModelScope.launch {
            _state.value = s.copy(saving = true)
            runCatching {
                ServiceLocator.api.updatePreferences(
                    PreferencesRequest(
                        refreshHour = s.refreshHour,
                        timezone = TimeZone.getDefault().id,
                        digestEnabled = s.digestEnabled,
                        digestNewOnly = s.digestNewOnly,
                        watchKeywords = s.watchKeywords,
                        blockedSources = s.blockedSources,
                    ),
                )
            }
            _state.value = _state.value.copy(saving = false, saved = true)
        }
    }
}

private fun hourLabel(h: Int): String {
    val ampm = if (h < 12) "AM" else "PM"
    val hr = when { h % 12 == 0 -> 12; else -> h % 12 }
    return "$hr:00 $ampm"
}

@Composable
fun AccountTab(onSignOut: () -> Unit) {
    val vm: AccountViewModel = viewModel()
    val state by vm.state.collectAsState()
    val context = LocalContext.current
    val user = state.user

    val tierLabel = when {
        user == null -> ""
        user.isPro -> "Pro" + (user.tier?.let { " · ${it.replaceFirstChar { c -> c.uppercase() }}" } ?: "")
        else -> "Free"
    }

    Column(
        Modifier.fillMaxSize().verticalScroll(rememberScrollState()).padding(20.dp),
        verticalArrangement = Arrangement.spacedBy(16.dp),
    ) {
        // Identity
        Card(Modifier.fillMaxWidth(), shape = RoundedCornerShape(14.dp), colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surfaceVariant)) {
            Column(Modifier.padding(18.dp)) {
                Text(user?.name ?: "—", fontSize = 20.sp, fontWeight = FontWeight.Bold, color = MaterialTheme.colorScheme.onSurface)
                Text(user?.email ?: "", fontSize = 14.sp, color = MaterialTheme.colorScheme.onSurfaceVariant)
                Spacer(Modifier.height(10.dp))
                Text("Plan: $tierLabel", fontSize = 14.sp, fontWeight = FontWeight.SemiBold, color = BrandBlue)
            }
        }

        if (user != null && !user.isPro) {
            Button(onClick = { runCatching { context.startActivity(Intent(Intent.ACTION_VIEW, Uri.parse(BuildConfig.DEFAULT_API_BASE_URL + "/pricing"))) } }, modifier = Modifier.fillMaxWidth()) {
                Text("Upgrade to Pro")
            }
        }

        // Preferences
        Card(Modifier.fillMaxWidth(), shape = RoundedCornerShape(14.dp), colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface)) {
            Column(Modifier.padding(18.dp)) {
                Text("News preferences", fontSize = 16.sp, fontWeight = FontWeight.SemiBold, color = MaterialTheme.colorScheme.onSurface)

                Spacer(Modifier.height(12.dp))
                Row(verticalAlignment = Alignment.CenterVertically) {
                    Text("Daily refresh time", fontSize = 14.sp, modifier = Modifier.weight(1f), color = MaterialTheme.colorScheme.onSurface)
                    HourDropdown(state.refreshHour) { vm.setHour(it) }
                }

                Spacer(Modifier.height(8.dp))
                Row(verticalAlignment = Alignment.CenterVertically) {
                    Text("Email me a daily digest", fontSize = 14.sp, modifier = Modifier.weight(1f), color = MaterialTheme.colorScheme.onSurface)
                    Switch(checked = state.digestEnabled, onCheckedChange = { vm.setDigest(it) })
                }
                if (state.digestEnabled) {
                    Row(verticalAlignment = Alignment.CenterVertically) {
                        Text("Only new headlines", fontSize = 14.sp, modifier = Modifier.weight(1f), color = MaterialTheme.colorScheme.onSurface)
                        Switch(checked = state.digestNewOnly, onCheckedChange = { vm.setNewOnly(it) })
                    }
                }
            }
        }

        // Pro power features
        if (user?.isPro == true) {
            Card(Modifier.fillMaxWidth(), shape = RoundedCornerShape(14.dp), colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface)) {
                Column(Modifier.padding(18.dp)) {
                    Text("Pro power features", fontSize = 16.sp, fontWeight = FontWeight.SemiBold, color = MaterialTheme.colorScheme.onSurface)
                    Spacer(Modifier.height(14.dp))

                    KeywordEditor(
                        title = "Watchlist keywords",
                        placeholder = "e.g. Tesla",
                        items = state.watchKeywords,
                        onAdd = { vm.addWatch(it) },
                        onRemove = { vm.removeWatch(it) },
                    )
                    Spacer(Modifier.height(4.dp))
                    Text("Stories matching these are pinned to the top of your feed.", fontSize = 12.sp, color = MaterialTheme.colorScheme.onSurfaceVariant)

                    Spacer(Modifier.height(14.dp))
                    Divider()
                    Spacer(Modifier.height(14.dp))

                    KeywordEditor(
                        title = "Blocked publishers",
                        placeholder = "e.g. tabloid.com",
                        items = state.blockedSources,
                        onAdd = { vm.addBlocked(it) },
                        onRemove = { vm.removeBlocked(it) },
                        lowercased = true,
                    )
                    Spacer(Modifier.height(4.dp))
                    Text("Articles from these sources are hidden from every feed.", fontSize = 12.sp, color = MaterialTheme.colorScheme.onSurfaceVariant)
                }
            }
        }

        // Save (persists everything above)
        Row(verticalAlignment = Alignment.CenterVertically) {
            Button(onClick = { vm.save() }, enabled = !state.saving) {
                Text(if (state.saving) "Saving…" else "Save changes")
            }
            if (state.saved) {
                Text("  Saved.", fontSize = 13.sp, color = MaterialTheme.colorScheme.onSurfaceVariant)
            }
        }

        OutlinedButton(onClick = onSignOut, modifier = Modifier.fillMaxWidth()) { Text("Sign out") }

        Text("NewsFlow · by moon whale media, llc", fontSize = 12.sp, color = MaterialTheme.colorScheme.onSurfaceVariant)
    }
}

@Composable
private fun HourDropdown(hour: Int, onSelect: (Int) -> Unit) {
    var expanded by remember { mutableStateOf(false) }
    Box {
        TextButton(onClick = { expanded = true }) { Text(hourLabel(hour), color = BrandBlue) }
        DropdownMenu(expanded = expanded, onDismissRequest = { expanded = false }) {
            (0..23).forEach { h ->
                DropdownMenuItem(text = { Text(hourLabel(h)) }, onClick = { onSelect(h); expanded = false })
            }
        }
    }
}
