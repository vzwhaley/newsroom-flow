package com.newsroomflow.android.ui.screens

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
import com.newsroomflow.android.data.ArchivedItem
import com.newsroomflow.android.data.ServiceLocator
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.launch

class ArchiveViewModel : ViewModel() {
    data class State(
        val loading: Boolean = true,
        val locked: Boolean = false,
        val items: List<ArchivedItem> = emptyList(),
        val query: String = "",
    )

    private val _state = MutableStateFlow(State())
    val state: StateFlow<State> = _state

    init { load() }

    fun setQuery(q: String) { _state.value = _state.value.copy(query = q) }

    fun load() {
        viewModelScope.launch {
            _state.value = _state.value.copy(loading = true)
            val res = runCatching { ServiceLocator.api.archive(_state.value.query.trim()) }.getOrNull()
            val body = res?.body()
            _state.value = _state.value.copy(
                loading = false,
                locked = body?.locked ?: false,
                items = body?.articles ?: emptyList(),
            )
        }
    }
}

@Composable
fun ArchiveTab() {
    val vm: ArchiveViewModel = viewModel()
    val state by vm.state.collectAsState()
    val context = LocalContext.current

    fun open(url: String) {
        runCatching { context.startActivity(Intent(Intent.ACTION_VIEW, Uri.parse(url))) }
    }

    Column(Modifier.fillMaxSize().padding(16.dp)) {
        Row(verticalAlignment = Alignment.CenterVertically) {
            OutlinedTextField(
                value = state.query,
                onValueChange = { vm.setQuery(it) },
                label = { Text("Search your archive") },
                singleLine = true,
                keyboardOptions = KeyboardOptions(imeAction = ImeAction.Search),
                modifier = Modifier.weight(1f),
            )
            Spacer(Modifier.width(8.dp))
            Button(onClick = { vm.load() }) { Text("Go") }
        }

        Spacer(Modifier.height(12.dp))

        when {
            state.loading -> Column(Modifier.fillMaxSize(), Arrangement.Center, Alignment.CenterHorizontally) { CircularProgressIndicator() }

            state.locked -> Center("Archive is a Pro feature", "Upgrade to keep a browsable history of every story that rotates out of your feeds.")

            state.items.isEmpty() -> Center(
                if (state.query.isBlank()) "Nothing archived yet" else "No matches",
                if (state.query.isBlank()) "As your feeds refresh, older stories are kept here so you can always find them again." else "Try a different word or phrase.",
            )

            else -> LazyColumn(verticalArrangement = Arrangement.spacedBy(10.dp)) {
                items(state.items, key = { it.id }) { a ->
                    ArticleCard(
                        headline = a.headline,
                        source = a.source,
                        description = a.description,
                        topicLabel = a.topicName,
                        onOpen = { open(a.url) },
                    )
                }
            }
        }
    }
}

@Composable
private fun Center(title: String, body: String) {
    Column(Modifier.fillMaxSize().padding(24.dp), Arrangement.Center, Alignment.CenterHorizontally) {
        Text(title, fontSize = 18.sp, color = MaterialTheme.colorScheme.onBackground)
        Text(body, fontSize = 14.sp, color = MaterialTheme.colorScheme.onSurfaceVariant, textAlign = TextAlign.Center, modifier = Modifier.padding(top = 6.dp))
    }
}
