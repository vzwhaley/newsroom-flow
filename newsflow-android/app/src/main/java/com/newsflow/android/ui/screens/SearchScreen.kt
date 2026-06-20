package com.newsflow.android.ui.screens

import android.content.Intent
import android.net.Uri
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.width
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.text.KeyboardOptions
import androidx.compose.material3.Button
import androidx.compose.material3.CircularProgressIndicator
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.input.ImeAction
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import androidx.lifecycle.viewmodel.compose.viewModel
import com.newsflow.android.data.SearchItem
import com.newsflow.android.data.ServiceLocator
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.launch

class SearchViewModel : ViewModel() {
    data class State(
        val loading: Boolean = false,
        val locked: Boolean = false,
        val searched: Boolean = false,
        val feed: List<SearchItem> = emptyList(),
        val saved: List<SearchItem> = emptyList(),
    )

    private val _state = MutableStateFlow(State())
    val state: StateFlow<State> = _state

    fun search(q: String) {
        if (q.isBlank()) return
        viewModelScope.launch {
            _state.value = _state.value.copy(loading = true)
            val res = runCatching { ServiceLocator.api.search(q.trim()) }.getOrNull()
            val body = res?.body()
            _state.value = State(
                loading = false,
                locked = body?.locked ?: false,
                searched = true,
                feed = body?.feed ?: emptyList(),
                saved = body?.saved ?: emptyList(),
            )
        }
    }
}

@Composable
fun SearchTab() {
    val vm: SearchViewModel = viewModel()
    val state by vm.state.collectAsState()
    val context = LocalContext.current
    var query by remember { mutableStateOf("") }

    fun open(url: String) {
        runCatching { context.startActivity(Intent(Intent.ACTION_VIEW, Uri.parse(url))) }
    }

    Column(Modifier.fillMaxSize().padding(16.dp)) {
        Row(verticalAlignment = Alignment.CenterVertically) {
            OutlinedTextField(
                value = query,
                onValueChange = { query = it },
                label = { Text("Search your feeds & saved") },
                singleLine = true,
                keyboardOptions = KeyboardOptions(imeAction = ImeAction.Search),
                modifier = Modifier.weight(1f),
            )
            Spacer(Modifier.width(8.dp))
            Button(onClick = { vm.search(query) }, enabled = query.isNotBlank()) { Text("Go") }
        }

        Spacer(Modifier.height(12.dp))

        when {
            state.loading -> Column(Modifier.fillMaxSize(), Arrangement.Center, Alignment.CenterHorizontally) { CircularProgressIndicator() }

            state.locked -> Center("Search is a Pro feature", "Upgrade to search across all your topics and saved articles.")

            !state.searched -> Center("Search your news", "Find anything across every topic you follow and everything you've saved.")

            state.feed.isEmpty() && state.saved.isEmpty() -> Center("No matches", "Try a different word or phrase.")

            else -> LazyColumn(verticalArrangement = Arrangement.spacedBy(10.dp)) {
                if (state.feed.isNotEmpty()) {
                    item { SectionLabel("In your feeds (${state.feed.size})") }
                    items(state.feed, key = { "f" + it.id }) { a -> ResultRow(a) { open(a.url) } }
                }
                if (state.saved.isNotEmpty()) {
                    item { SectionLabel("In your saved (${state.saved.size})") }
                    items(state.saved, key = { "s" + it.id }) { a -> ResultRow(a) { open(a.url) } }
                }
            }
        }
    }
}

@Composable
private fun ResultRow(item: SearchItem, onOpen: () -> Unit) {
    ArticleCard(
        headline = item.headline,
        source = item.source,
        description = item.description,
        topicLabel = item.topicName,
        isRead = item.isRead,
        onOpen = onOpen,
    )
}

@Composable
private fun Center(title: String, body: String) {
    Column(Modifier.fillMaxSize().padding(24.dp), Arrangement.Center, Alignment.CenterHorizontally) {
        Text(title, fontSize = 18.sp, color = MaterialTheme.colorScheme.onBackground)
        Text(body, fontSize = 14.sp, color = MaterialTheme.colorScheme.onSurfaceVariant, textAlign = TextAlign.Center, modifier = Modifier.padding(top = 6.dp))
    }
}
