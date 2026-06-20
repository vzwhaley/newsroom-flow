package com.newsflow.android.ui.screens

import android.content.Intent
import android.net.Uri
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.material3.CircularProgressIndicator
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import androidx.lifecycle.viewmodel.compose.viewModel
import com.newsflow.android.data.SavedItem
import com.newsflow.android.data.ServiceLocator
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.launch

class SavedViewModel : ViewModel() {
    data class State(val loading: Boolean = true, val items: List<SavedItem> = emptyList())

    private val _state = MutableStateFlow(State())
    val state: StateFlow<State> = _state

    init { load() }

    fun load() = viewModelScope.launch {
        _state.value = _state.value.copy(loading = true)
        val res = runCatching { ServiceLocator.api.saved() }.getOrNull()
        _state.value = State(loading = false, items = res?.body()?.saved ?: emptyList())
    }

    fun remove(id: Long) {
        _state.value = _state.value.copy(items = _state.value.items.filterNot { it.id == id })
        viewModelScope.launch { runCatching { ServiceLocator.api.unsave(id) } }
    }
}

@Composable
fun SavedTab() {
    val vm: SavedViewModel = viewModel()
    val state by vm.state.collectAsState()
    val context = LocalContext.current

    if (state.loading) {
        Column(Modifier.fillMaxSize(), Arrangement.Center, Alignment.CenterHorizontally) { CircularProgressIndicator() }
        return
    }

    if (state.items.isEmpty()) {
        Column(Modifier.fillMaxSize().padding(32.dp), Arrangement.Center, Alignment.CenterHorizontally) {
            Text("Nothing saved yet", fontSize = 18.sp, color = MaterialTheme.colorScheme.onBackground)
            Text(
                "Tap the bookmark on any article to save it here for later.",
                fontSize = 14.sp, color = MaterialTheme.colorScheme.onSurfaceVariant, textAlign = TextAlign.Center,
                modifier = Modifier.padding(top = 6.dp),
            )
        }
        return
    }

    LazyColumn(
        modifier = Modifier.fillMaxSize(),
        contentPadding = androidx.compose.foundation.layout.PaddingValues(16.dp),
        verticalArrangement = Arrangement.spacedBy(10.dp),
    ) {
        items(state.items, key = { it.id }) { item ->
            ArticleCard(
                headline = item.headline,
                source = item.source,
                description = item.description,
                topicLabel = item.topicName,
                isPro = true,
                isSaved = true,
                onOpen = { runCatching { context.startActivity(Intent(Intent.ACTION_VIEW, Uri.parse(item.url))) } },
                onToggleSave = { vm.remove(item.id) },
            )
        }
    }
}
