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
import androidx.compose.foundation.layout.width
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.MoreVert
import androidx.compose.material.icons.filled.Refresh
import androidx.compose.material3.AlertDialog
import androidx.compose.material3.Button
import androidx.compose.material3.CircularProgressIndicator
import androidx.compose.material3.DropdownMenu
import androidx.compose.material3.DropdownMenuItem
import androidx.compose.material3.Icon
import androidx.compose.material3.IconButton
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.OutlinedTextField
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
import com.newsflow.android.data.AddTopicRequest
import com.newsflow.android.data.Article
import com.newsflow.android.data.SaveRequest
import com.newsflow.android.data.ServiceLocator
import com.newsflow.android.data.Topic
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.launch

data class FeedUiState(
    val loading: Boolean = true,
    val isPro: Boolean = false,
    val topics: List<Topic> = emptyList(),
    val watchlist: List<Article> = emptyList(),
    val readIds: Set<Long> = emptySet(),
    val savedFps: Set<String> = emptySet(),
    val busy: Boolean = false,
    val error: String? = null,
)

data class FeedRow(val topic: Topic, val parentName: String?)

class FeedViewModel : ViewModel() {
    private val _state = MutableStateFlow(FeedUiState())
    val state: StateFlow<FeedUiState> = _state

    init { load() }

    fun load() {
        viewModelScope.launch {
            _state.value = _state.value.copy(loading = true, error = null)
            val me = runCatching { ServiceLocator.api.me() }.getOrNull()
            val feed = runCatching { ServiceLocator.api.feed() }.getOrNull()
            if (feed == null || !feed.isSuccessful) {
                _state.value = _state.value.copy(loading = false, error = "Couldn't load your feed.")
                return@launch
            }
            val body = feed.body()!!
            val read = body.topics.flatMap { collectArticles(it) }.filter { it.isRead }.map { it.id }.toSet()
            _state.value = FeedUiState(
                loading = false,
                isPro = me?.body()?.user?.isPro == true,
                topics = body.topics,
                watchlist = body.watchlist,
                readIds = read,
                savedFps = body.savedFingerprints.toSet(),
            )
        }
    }

    fun addTopic(name: String) {
        if (name.isBlank()) return
        viewModelScope.launch {
            _state.value = _state.value.copy(busy = true, error = null)
            val res = runCatching { ServiceLocator.api.addTopic(AddTopicRequest(name.trim())) }.getOrNull()
            _state.value = _state.value.copy(busy = false)
            when {
                res != null && res.isSuccessful -> load()
                res?.code() == 422 -> _state.value = _state.value.copy(error = "Free accounts can follow up to 2 topics. Upgrade to Pro for unlimited.")
                else -> _state.value = _state.value.copy(error = "Couldn't add that topic.")
            }
        }
    }

    fun deleteTopic(id: Long) = viewModelScope.launch {
        runCatching { ServiceLocator.api.deleteTopic(id) }
        load()
    }

    fun refreshTopic(id: Long) = viewModelScope.launch {
        _state.value = _state.value.copy(busy = true)
        runCatching { ServiceLocator.api.refreshTopic(id) }
        _state.value = _state.value.copy(busy = false)
        load()
    }

    fun markAllRead(topic: Topic) {
        _state.value = _state.value.copy(readIds = _state.value.readIds + topic.articles.map { it.id })
        viewModelScope.launch { runCatching { ServiceLocator.api.markAllRead(topic.id) } }
    }

    fun setMutes(id: Long, keywords: List<String>) = viewModelScope.launch {
        _state.value = _state.value.copy(busy = true)
        runCatching { ServiceLocator.api.setMutes(id, com.newsflow.android.data.MuteRequest(keywords)) }
        _state.value = _state.value.copy(busy = false)
        load()
    }

    fun markRead(article: Article) {
        if (article.id in _state.value.readIds) return
        _state.value = _state.value.copy(readIds = _state.value.readIds + article.id)
        viewModelScope.launch { runCatching { ServiceLocator.api.markRead(article.id) } }
    }

    fun save(article: Article) {
        if (article.fingerprint in _state.value.savedFps) return
        _state.value = _state.value.copy(savedFps = _state.value.savedFps + article.fingerprint)
        viewModelScope.launch {
            runCatching {
                ServiceLocator.api.save(
                    SaveRequest(
                        headline = article.headline, description = article.description, url = article.url,
                        source = article.source, imageUrl = article.imageUrl, topicName = article.topicName,
                    ),
                )
            }
        }
    }

    private fun collectArticles(t: Topic): List<Article> =
        t.articles + t.children.flatMap { collectArticles(it) }
}

@Composable
fun FeedTab() {
    val vm: FeedViewModel = viewModel()
    val state by vm.state.collectAsState()
    val context = LocalContext.current
    var newTopic by remember { mutableStateOf("") }
    var muteTarget by remember { mutableStateOf<Topic?>(null) }

    fun open(article: Article) {
        vm.markRead(article)
        runCatching { context.startActivity(Intent(Intent.ACTION_VIEW, Uri.parse(article.url))) }
    }

    val rows = remember(state.topics) {
        buildList {
            state.topics.forEach { top ->
                add(FeedRow(top, null))
                top.children.forEach { child -> add(FeedRow(child, top.name)) }
            }
        }
    }

    if (state.loading) {
        Column(Modifier.fillMaxSize(), Arrangement.Center, Alignment.CenterHorizontally) { CircularProgressIndicator() }
        return
    }

    Box(Modifier.fillMaxSize()) {
    LazyColumn(
        modifier = Modifier.fillMaxSize(),
        contentPadding = androidx.compose.foundation.layout.PaddingValues(16.dp),
        verticalArrangement = Arrangement.spacedBy(10.dp),
    ) {
        item {
            Row(verticalAlignment = Alignment.CenterVertically) {
                OutlinedTextField(value = newTopic, onValueChange = { newTopic = it }, label = { Text("Add a topic") }, singleLine = true, modifier = Modifier.weight(1f))
                Spacer(Modifier.width(8.dp))
                Button(onClick = { vm.addTopic(newTopic); newTopic = "" }, enabled = !state.busy && newTopic.isNotBlank()) { Text("Add") }
            }
            if (state.error != null) {
                Spacer(Modifier.height(6.dp))
                Text(state.error!!, color = MaterialTheme.colorScheme.error, fontSize = 13.sp)
            }
        }

        if (state.watchlist.isNotEmpty()) {
            item { SectionLabel("On your watchlist") }
            items(state.watchlist, key = { "w" + it.id }) { a ->
                ArticleCard(
                    headline = a.headline, source = a.source, description = a.description,
                    topicLabel = a.topicName, isRead = a.id in state.readIds, isPro = state.isPro,
                    isSaved = a.fingerprint in state.savedFps, articleId = a.id,
                    onOpen = { open(a) }, onToggleSave = { vm.save(a) },
                )
            }
        }

        rows.forEach { row ->
            item(key = "t" + row.topic.id) {
                Spacer(Modifier.height(6.dp))
                TopicHeader(
                    topic = row.topic,
                    parentName = row.parentName,
                    isPro = state.isPro,
                    onRefresh = { vm.refreshTopic(row.topic.id) },
                    onMarkAllRead = { vm.markAllRead(row.topic) },
                    onMute = { muteTarget = row.topic },
                    onDelete = { vm.deleteTopic(row.topic.id) },
                )
            }
            if (row.topic.articles.isEmpty()) {
                item(key = "e" + row.topic.id) {
                    Text("No articles yet.", fontSize = 13.sp, color = MaterialTheme.colorScheme.onSurfaceVariant)
                }
            } else {
                items(row.topic.articles, key = { "a" + it.id }) { a ->
                    ArticleCard(
                        headline = a.headline, source = a.source, description = a.description,
                        isRead = a.id in state.readIds, isPro = state.isPro,
                        isSaved = a.fingerprint in state.savedFps, articleId = a.id,
                        onOpen = { open(a) }, onToggleSave = { vm.save(a) },
                    )
                }
            }
        }

        if (rows.isEmpty()) {
            item {
                Spacer(Modifier.height(24.dp))
                Text("Add your first topic above — World News, your team, a company, a hobby — and we'll pull today's top stories.", color = MaterialTheme.colorScheme.onSurfaceVariant)
            }
        }
    }

    muteTarget?.let { topic ->
        MuteDialog(
            topic = topic,
            onDismiss = { muteTarget = null },
            onSave = { keywords -> vm.setMutes(topic.id, keywords); muteTarget = null },
        )
    }
    }
}

@Composable
fun SectionLabel(text: String) {
    Text(text, fontSize = 13.sp, fontWeight = FontWeight.Bold, color = BrandBlue, modifier = Modifier.padding(top = 6.dp))
}

@Composable
private fun TopicHeader(
    topic: Topic,
    parentName: String?,
    isPro: Boolean,
    onRefresh: () -> Unit,
    onMarkAllRead: () -> Unit,
    onMute: () -> Unit,
    onDelete: () -> Unit,
) {
    var menuOpen by remember { mutableStateOf(false) }

    Row(verticalAlignment = Alignment.CenterVertically, modifier = Modifier.fillMaxWidth()) {
        Column(Modifier.weight(1f)) {
            if (parentName != null) {
                Text(parentName.uppercase(), fontSize = 11.sp, fontWeight = FontWeight.Bold, color = BrandBlue)
            }
            Text(topic.name, fontSize = 20.sp, fontWeight = FontWeight.Bold, color = MaterialTheme.colorScheme.onBackground)
        }
        if (topic.muteKeywords.isNotEmpty()) {
            Text("muted", fontSize = 11.sp, color = MaterialTheme.colorScheme.onSurfaceVariant)
            Spacer(Modifier.width(6.dp))
        }
        IconButton(onClick = onRefresh) { Icon(Icons.Filled.Refresh, contentDescription = "Refresh") }
        Box {
            IconButton(onClick = { menuOpen = true }) { Icon(Icons.Filled.MoreVert, contentDescription = "Topic options") }
            DropdownMenu(expanded = menuOpen, onDismissRequest = { menuOpen = false }) {
                DropdownMenuItem(text = { Text("Mark all read") }, onClick = { menuOpen = false; onMarkAllRead() })
                if (isPro) {
                    DropdownMenuItem(text = { Text("Mute keywords…") }, onClick = { menuOpen = false; onMute() })
                }
                DropdownMenuItem(text = { Text("Remove topic") }, onClick = { menuOpen = false; onDelete() })
            }
        }
    }
}

@Composable
private fun MuteDialog(topic: Topic, onDismiss: () -> Unit, onSave: (List<String>) -> Unit) {
    var keywords by remember(topic.id) { mutableStateOf(topic.muteKeywords) }

    AlertDialog(
        onDismissRequest = onDismiss,
        title = { Text("Mute keywords") },
        text = {
            Column {
                Text(
                    "Hide stories in \"${topic.name}\" that mention any of these words.",
                    fontSize = 13.sp, color = MaterialTheme.colorScheme.onSurfaceVariant,
                )
                Spacer(Modifier.height(12.dp))
                KeywordEditor(
                    title = "Muted keywords",
                    placeholder = "e.g. crypto",
                    items = keywords,
                    onAdd = { if (it !in keywords) keywords = keywords + it },
                    onRemove = { keywords = keywords - it },
                    lowercased = true,
                )
            }
        },
        confirmButton = { TextButton(onClick = { onSave(keywords) }) { Text("Save") } },
        dismissButton = { TextButton(onClick = onDismiss) { Text("Cancel") } },
    )
}
