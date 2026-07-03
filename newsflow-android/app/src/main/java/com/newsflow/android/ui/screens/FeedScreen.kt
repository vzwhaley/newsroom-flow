package com.newsflow.android.ui.screens

import android.content.Intent
import android.net.Uri
import androidx.compose.foundation.background
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
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.MoreVert
import androidx.compose.material.icons.filled.Refresh
import androidx.compose.material3.AlertDialog
import androidx.compose.material3.Button
import androidx.compose.material3.CircularProgressIndicator
import androidx.compose.material3.DropdownMenu
import androidx.compose.material3.DropdownMenuItem
import androidx.compose.material3.ExperimentalMaterial3Api
import androidx.compose.material3.Icon
import androidx.compose.material3.IconButton
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
import androidx.compose.material3.pulltorefresh.PullToRefreshBox
import androidx.compose.runtime.Composable
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import androidx.lifecycle.viewmodel.compose.viewModel
import com.newsflow.android.data.AddTopicRequest
import com.newsflow.android.data.Article
import com.newsflow.android.data.BriefingResponse
import com.newsflow.android.data.ReadingStats
import com.newsflow.android.data.SaveRequest
import com.newsflow.android.data.ServiceLocator
import com.newsflow.android.data.Topic
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.launch

data class FeedUiState(
    val loading: Boolean = true,
    val loadFailed: Boolean = false,
    val refreshing: Boolean = false,
    val isPro: Boolean = false,
    val emailVerified: Boolean = true,
    val verificationSent: Boolean = false,
    val topicLimit: Int? = null,   // null = unlimited (Pro) or unknown
    val topicCount: Int = 0,
    val topics: List<Topic> = emptyList(),
    val watchlist: List<Article> = emptyList(),
    val readIds: Set<Long> = emptySet(),
    val savedFps: Set<String> = emptySet(),
    val reading: ReadingStats = ReadingStats(),
    val briefing: BriefingResponse? = null,
    val busy: Boolean = false,
    val error: String? = null,
) {
    /** Free user sitting at their topic cap right now. */
    val atTopicLimit: Boolean get() = topicLimit != null && topicCount >= topicLimit
}

data class FeedRow(val topic: Topic, val parentName: String?)

class FeedViewModel : ViewModel() {
    private val _state = MutableStateFlow(FeedUiState())
    val state: StateFlow<FeedUiState> = _state

    init { load() }

    fun load() = viewModelScope.launch { doLoad(fullScreenSpinner = _state.value.topics.isEmpty()) }

    /** Pull-to-refresh reload — keeps the current list on screen while fetching. */
    fun refresh() = viewModelScope.launch { doLoad(fullScreenSpinner = false) }

    private suspend fun doLoad(fullScreenSpinner: Boolean) {
        _state.value = _state.value.copy(
            loading = fullScreenSpinner, refreshing = !fullScreenSpinner,
            error = null, loadFailed = false,
        )
        val me = runCatching { ServiceLocator.api.me() }.getOrNull()
        val feed = runCatching { ServiceLocator.api.feed() }.getOrNull()
        val body = feed?.takeIf { it.isSuccessful }?.body()
        if (body == null) {
            _state.value = _state.value.copy(
                loading = false, refreshing = false,
                loadFailed = _state.value.topics.isEmpty(),
                error = "Couldn't load your feed.",
            )
            return
        }
        val user = me?.body()?.user
        val read = body.topics.flatMap { collectArticles(it) }.filter { it.isRead }.map { it.id }.toSet()

        // Pro: today's AI briefing (server caches it, so this is cheap).
        val briefing = if (user?.isPro == true && body.topics.isNotEmpty()) {
            runCatching { ServiceLocator.api.briefing() }.getOrNull()
                ?.takeIf { it.isSuccessful }?.body()
        } else {
            null
        }

        _state.value = FeedUiState(
            loading = false,
            isPro = user?.isPro == true,
            emailVerified = user?.emailVerified ?: true,
            verificationSent = _state.value.verificationSent,
            topicLimit = user?.topicLimit,
            topicCount = user?.topicCount ?: body.topics.size,
            topics = body.topics,
            watchlist = body.watchlist,
            readIds = read,
            savedFps = body.savedFingerprints.toSet(),
            reading = user?.reading ?: ReadingStats(),
            briefing = briefing,
        )
    }

    fun resendVerification() = viewModelScope.launch {
        runCatching { ServiceLocator.api.resendVerification() }
        _state.value = _state.value.copy(verificationSent = true)
    }

    fun addTopic(name: String, parentId: Long? = null) {
        if (name.isBlank()) return
        viewModelScope.launch {
            _state.value = _state.value.copy(busy = true, error = null)
            val res = runCatching { ServiceLocator.api.addTopic(AddTopicRequest(name.trim(), parentId)) }.getOrNull()
            _state.value = _state.value.copy(busy = false)
            when {
                res != null && res.isSuccessful -> load()
                res?.code() == 422 -> _state.value = _state.value.copy(error = "Free accounts can follow up to ${_state.value.topicLimit ?: 2} topics. Upgrade to Pro for unlimited.")
                else -> _state.value = _state.value.copy(error = "Couldn't add that topic.")
            }
        }
    }

    /** Move a top-level topic up or down and persist the new order. */
    fun moveTopic(topic: Topic, up: Boolean) {
        if (topic.parentId != null) return
        val list = _state.value.topics.toMutableList()
        val i = list.indexOfFirst { it.id == topic.id }
        if (i < 0) return
        val j = if (up) i - 1 else i + 1
        if (j < 0 || j >= list.size) return
        val tmp = list[i]; list[i] = list[j]; list[j] = tmp
        _state.value = _state.value.copy(topics = list)
        viewModelScope.launch {
            val res = runCatching { ServiceLocator.api.reorderTopics(com.newsflow.android.data.ReorderRequest(list.map { it.id })) }.getOrNull()
            if (res == null || !res.isSuccessful) load()   // server rejected — resync the list
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

    fun toggleRead(article: Article) {
        val nowRead = article.id !in _state.value.readIds
        _state.value = _state.value.copy(
            readIds = if (nowRead) _state.value.readIds + article.id else _state.value.readIds - article.id,
        )
        viewModelScope.launch {
            runCatching {
                if (nowRead) ServiceLocator.api.markRead(article.id) else ServiceLocator.api.markUnread(article.id)
            }
        }
    }

    fun toggleDigest(topic: Topic) = viewModelScope.launch {
        runCatching { ServiceLocator.api.setDigest(topic.id, com.newsflow.android.data.DigestRequest(!topic.includeInDigest)) }
        load()
    }

    fun save(article: Article) {
        if (article.fingerprint in _state.value.savedFps) return
        _state.value = _state.value.copy(savedFps = _state.value.savedFps + article.fingerprint)
        viewModelScope.launch {
            val res = runCatching {
                ServiceLocator.api.save(
                    SaveRequest(
                        headline = article.headline, description = article.description, url = article.url,
                        source = article.source, imageUrl = article.imageUrl, topicName = article.topicName,
                    ),
                )
            }.getOrNull()
            if (res == null || !res.isSuccessful) {
                // Roll back the optimistic bookmark so the UI matches reality.
                _state.value = _state.value.copy(savedFps = _state.value.savedFps - article.fingerprint)
            }
        }
    }

    private fun collectArticles(t: Topic): List<Article> =
        t.articles + t.children.flatMap { collectArticles(it) }
}

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun FeedTab() {
    val vm: FeedViewModel = viewModel()
    val state by vm.state.collectAsState()
    val context = LocalContext.current
    var newTopic by remember { mutableStateOf("") }
    var muteTarget by remember { mutableStateOf<Topic?>(null) }
    var subtopicParent by remember { mutableStateOf<Topic?>(null) }

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

    if (state.loadFailed) {
        Column(Modifier.fillMaxSize(), Arrangement.Center, Alignment.CenterHorizontally) {
            Text("Couldn't load your feed.", color = MaterialTheme.colorScheme.onSurfaceVariant)
            Spacer(Modifier.height(12.dp))
            Button(onClick = { vm.load() }) { Text("Try again") }
        }
        return
    }

    PullToRefreshBox(
        isRefreshing = state.refreshing,
        onRefresh = { vm.refresh() },
        modifier = Modifier.fillMaxSize(),
    ) {
    LazyColumn(
        modifier = Modifier.fillMaxSize(),
        contentPadding = androidx.compose.foundation.layout.PaddingValues(16.dp),
        verticalArrangement = Arrangement.spacedBy(10.dp),
    ) {
        if (!state.emailVerified) {
            item {
                Column(
                    Modifier
                        .fillMaxWidth()
                        .background(MaterialTheme.colorScheme.primaryContainer, RoundedCornerShape(12.dp))
                        .padding(14.dp),
                ) {
                    Text("Please verify your email", fontSize = 14.sp, fontWeight = FontWeight.SemiBold, color = MaterialTheme.colorScheme.onPrimaryContainer)
                    Text(
                        "We sent a link to your inbox — verifying keeps your account recoverable.",
                        fontSize = 13.sp, color = MaterialTheme.colorScheme.onPrimaryContainer,
                    )
                    TextButton(onClick = { vm.resendVerification() }, enabled = !state.verificationSent) {
                        Text(if (state.verificationSent) "Sent — check your inbox" else "Resend email", fontSize = 13.sp)
                    }
                }
            }
        }

        if (state.reading.streak > 0) {
            item {
                Row(
                    verticalAlignment = Alignment.CenterVertically,
                    modifier = Modifier
                        .clip(RoundedCornerShape(50))
                        .background(Color(0xFFFFF7ED))
                        .padding(horizontal = 12.dp, vertical = 5.dp),
                ) {
                    Text(
                        "🔥 ${state.reading.streak}-day reading streak" +
                            if (state.reading.readToday) "" else " — read a story to keep it!",
                        fontSize = 12.sp, fontWeight = FontWeight.SemiBold, color = Color(0xFFC2410C),
                    )
                }
            }
        }

        state.briefing?.let { b ->
            item {
                Column(
                    Modifier
                        .fillMaxWidth()
                        .background(MaterialTheme.colorScheme.secondaryContainer, RoundedCornerShape(16.dp))
                        .padding(14.dp),
                ) {
                    Row(verticalAlignment = Alignment.CenterVertically) {
                        Text("Your Daily Briefing", fontSize = 14.sp, fontWeight = FontWeight.Bold, color = MaterialTheme.colorScheme.onSecondaryContainer)
                        if (!b.ai) {
                            Spacer(Modifier.width(6.dp))
                            Text("PREVIEW", fontSize = 9.sp, fontWeight = FontWeight.Bold, color = MaterialTheme.colorScheme.onSecondaryContainer.copy(alpha = 0.6f))
                        }
                    }
                    Spacer(Modifier.height(4.dp))
                    Text(b.briefing, fontSize = 13.sp, color = MaterialTheme.colorScheme.onSecondaryContainer)
                }
            }
        }

        item {
            Row(verticalAlignment = Alignment.CenterVertically) {
                OutlinedTextField(value = newTopic, onValueChange = { newTopic = it }, label = { Text("Add a topic") }, singleLine = true, modifier = Modifier.weight(1f))
                Spacer(Modifier.width(8.dp))
                Button(onClick = { vm.addTopic(newTopic); newTopic = "" }, enabled = !state.busy && newTopic.isNotBlank() && !state.atTopicLimit) { Text("Add") }
            }
            state.topicLimit?.let { limit ->
                Spacer(Modifier.height(4.dp))
                Text(
                    if (state.atTopicLimit) "You've used all $limit free topics — upgrade to Pro for unlimited."
                    else "${state.topicCount} of $limit topics used",
                    fontSize = 12.sp,
                    color = if (state.atTopicLimit) MaterialTheme.colorScheme.tertiary else MaterialTheme.colorScheme.onSurfaceVariant,
                )
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
                    onToggleDigest = { vm.toggleDigest(row.topic) },
                    onAddSubtopic = { subtopicParent = row.topic },
                    onMoveUp = { vm.moveTopic(row.topic, up = true) },
                    onMoveDown = { vm.moveTopic(row.topic, up = false) },
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
                        onOpen = { open(a) }, onToggleSave = { vm.save(a) }, onToggleRead = { vm.toggleRead(a) },
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

        // Free-tier ad banner (Pro removes it).
        item { AdBanner(isPro = state.isPro) }
    }

    muteTarget?.let { topic ->
        MuteDialog(
            topic = topic,
            onDismiss = { muteTarget = null },
            onSave = { keywords -> vm.setMutes(topic.id, keywords); muteTarget = null },
        )
    }

    subtopicParent?.let { parent ->
        SubtopicDialog(
            parent = parent,
            onDismiss = { subtopicParent = null },
            onAdd = { name -> vm.addTopic(name, parent.id); subtopicParent = null },
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
    onToggleDigest: () -> Unit,
    onAddSubtopic: () -> Unit,
    onMoveUp: () -> Unit,
    onMoveDown: () -> Unit,
    onDelete: () -> Unit,
) {
    var menuOpen by remember { mutableStateOf(false) }
    val isTopLevel = topic.parentId == null

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
                DropdownMenuItem(
                    text = { Text(if (topic.includeInDigest) "Remove from digest" else "Add to daily digest") },
                    onClick = { menuOpen = false; onToggleDigest() },
                )
                if (isTopLevel) {
                    DropdownMenuItem(text = { Text("Add subtopic…") }, onClick = { menuOpen = false; onAddSubtopic() })
                    DropdownMenuItem(text = { Text("Move up") }, onClick = { menuOpen = false; onMoveUp() })
                    DropdownMenuItem(text = { Text("Move down") }, onClick = { menuOpen = false; onMoveDown() })
                }
                DropdownMenuItem(text = { Text("Remove topic") }, onClick = { menuOpen = false; onDelete() })
            }
        }
    }
}

@Composable
private fun SubtopicDialog(parent: Topic, onDismiss: () -> Unit, onAdd: (String) -> Unit) {
    var name by remember { mutableStateOf("") }

    AlertDialog(
        onDismissRequest = onDismiss,
        title = { Text("Add subtopic") },
        text = {
            Column {
                Text("Nested under \"${parent.name}\".", fontSize = 13.sp, color = MaterialTheme.colorScheme.onSurfaceVariant)
                Spacer(Modifier.height(12.dp))
                OutlinedTextField(
                    value = name,
                    onValueChange = { name = it },
                    label = { Text("Subtopic name") },
                    singleLine = true,
                )
            }
        },
        confirmButton = { TextButton(onClick = { onAdd(name) }, enabled = name.isNotBlank()) { Text("Add") } },
        dismissButton = { TextButton(onClick = onDismiss) { Text("Cancel") } },
    )
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
