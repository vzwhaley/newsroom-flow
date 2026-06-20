package com.newsflow.android.ui.screens

import android.content.Intent
import android.net.Uri
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.Arrangement
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
import androidx.compose.material.icons.automirrored.filled.Logout
import androidx.compose.material.icons.filled.Delete
import androidx.compose.material.icons.filled.Refresh
import androidx.compose.material3.Button
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.CircularProgressIndicator
import androidx.compose.material3.ExperimentalMaterial3Api
import androidx.compose.material3.Icon
import androidx.compose.material3.IconButton
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.Scaffold
import androidx.compose.material3.Text
import androidx.compose.material3.TopAppBar
import androidx.compose.material3.TopAppBarDefaults
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
import androidx.compose.ui.text.style.TextOverflow
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import androidx.lifecycle.viewmodel.compose.viewModel
import com.newsflow.android.data.AddTopicRequest
import com.newsflow.android.data.Article
import com.newsflow.android.data.ServiceLocator
import com.newsflow.android.data.Topic
import com.newsflow.android.data.User
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.launch

data class FeedUiState(
    val loading: Boolean = true,
    val user: User? = null,
    val topics: List<Topic> = emptyList(),
    val watchlist: List<Article> = emptyList(),
    val readIds: Set<Long> = emptySet(),
    val busy: Boolean = false,
    val error: String? = null,
)

/** A flattened feed row: a topic plus an optional parent label. */
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
                user = me?.body()?.user,
                topics = body.topics,
                watchlist = body.watchlist,
                readIds = read,
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

    fun markRead(article: Article) {
        if (article.id in _state.value.readIds) return
        _state.value = _state.value.copy(readIds = _state.value.readIds + article.id)
        viewModelScope.launch { runCatching { ServiceLocator.api.markRead(article.id) } }
    }

    private fun collectArticles(t: Topic): List<Article> =
        t.articles + t.children.flatMap { collectArticles(it) }
}

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun FeedScreen(onSignOut: () -> Unit) {
    val vm: FeedViewModel = viewModel()
    val state by vm.state.collectAsState()
    val context = LocalContext.current
    var newTopic by remember { mutableStateOf("") }

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

    Scaffold(
        topBar = {
            TopAppBar(
                title = { BrandTitle() },
                actions = {
                    IconButton(onClick = onSignOut) {
                        Icon(Icons.AutoMirrored.Filled.Logout, contentDescription = "Sign out")
                    }
                },
                colors = TopAppBarDefaults.topAppBarColors(),
            )
        },
    ) { padding ->
        if (state.loading) {
            Column(Modifier.fillMaxSize().padding(padding), Arrangement.Center, Alignment.CenterHorizontally) {
                CircularProgressIndicator()
            }
            return@Scaffold
        }

        LazyColumn(
            modifier = Modifier.fillMaxSize().padding(padding),
            contentPadding = androidx.compose.foundation.layout.PaddingValues(16.dp),
            verticalArrangement = Arrangement.spacedBy(10.dp),
        ) {
            item {
                AddTopicBar(
                    value = newTopic,
                    onValueChange = { newTopic = it },
                    busy = state.busy,
                    onAdd = { vm.addTopic(newTopic); newTopic = "" },
                )
                if (state.error != null) {
                    Spacer(Modifier.height(6.dp))
                    Text(state.error!!, color = MaterialTheme.colorScheme.error, fontSize = 13.sp)
                }
            }

            if (state.watchlist.isNotEmpty()) {
                item { SectionLabel("On your watchlist") }
                items(state.watchlist, key = { "w" + it.id }) { a ->
                    ArticleCard(a, a.id in state.readIds, a.topicName) { open(a) }
                }
            }

            rows.forEach { row ->
                item(key = "t" + row.topic.id) {
                    Spacer(Modifier.height(6.dp))
                    TopicHeader(
                        topic = row.topic,
                        parentName = row.parentName,
                        onRefresh = { vm.refreshTopic(row.topic.id) },
                        onDelete = { vm.deleteTopic(row.topic.id) },
                    )
                }
                if (row.topic.articles.isEmpty()) {
                    item(key = "e" + row.topic.id) {
                        Text("No articles yet.", fontSize = 13.sp, color = MaterialTheme.colorScheme.onSurfaceVariant)
                    }
                } else {
                    items(row.topic.articles, key = { "a" + it.id }) { a ->
                        ArticleCard(a, a.id in state.readIds, null) { open(a) }
                    }
                }
            }

            if (rows.isEmpty()) {
                item {
                    Spacer(Modifier.height(24.dp))
                    Text(
                        "Add your first topic above — World News, your team, a company, a hobby — and we'll pull today's top stories.",
                        color = MaterialTheme.colorScheme.onSurfaceVariant,
                    )
                }
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

@Composable
private fun SectionLabel(text: String) {
    Text(text, fontSize = 13.sp, fontWeight = FontWeight.Bold, color = BrandBlue, modifier = Modifier.padding(top = 6.dp))
}

@Composable
private fun AddTopicBar(value: String, onValueChange: (String) -> Unit, busy: Boolean, onAdd: () -> Unit) {
    Row(verticalAlignment = Alignment.CenterVertically) {
        OutlinedTextField(
            value = value,
            onValueChange = onValueChange,
            label = { Text("Add a topic") },
            singleLine = true,
            modifier = Modifier.weight(1f),
        )
        Spacer(Modifier.width(8.dp))
        Button(onClick = onAdd, enabled = !busy && value.isNotBlank()) { Text("Add") }
    }
}

@Composable
private fun TopicHeader(topic: Topic, parentName: String?, onRefresh: () -> Unit, onDelete: () -> Unit) {
    Row(verticalAlignment = Alignment.CenterVertically, modifier = Modifier.fillMaxWidth()) {
        Column(Modifier.weight(1f)) {
            if (parentName != null) {
                Text(parentName.uppercase(), fontSize = 11.sp, fontWeight = FontWeight.Bold, color = BrandBlue)
            }
            Text(topic.name, fontSize = 20.sp, fontWeight = FontWeight.Bold, color = MaterialTheme.colorScheme.onBackground)
        }
        IconButton(onClick = onRefresh) { Icon(Icons.Filled.Refresh, contentDescription = "Refresh") }
        IconButton(onClick = onDelete) { Icon(Icons.Filled.Delete, contentDescription = "Remove topic") }
    }
}

@Composable
private fun ArticleCard(article: Article, isRead: Boolean, topicName: String?, onClick: () -> Unit) {
    Card(
        modifier = Modifier.fillMaxWidth().clickable(onClick = onClick),
        shape = RoundedCornerShape(12.dp),
        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
    ) {
        Column(Modifier.padding(14.dp)) {
            Row {
                if (topicName != null) {
                    Text(topicName + " · ", fontSize = 12.sp, color = BrandBlue)
                }
                Text(article.source ?: "", fontSize = 12.sp, color = MaterialTheme.colorScheme.onSurfaceVariant)
            }
            Spacer(Modifier.height(4.dp))
            Text(
                article.headline,
                fontSize = 17.sp,
                fontWeight = FontWeight.SemiBold,
                color = if (isRead) MaterialTheme.colorScheme.onSurfaceVariant else MaterialTheme.colorScheme.onSurface,
            )
            if (article.description.isNotBlank()) {
                Spacer(Modifier.height(4.dp))
                Text(
                    article.description,
                    fontSize = 14.sp,
                    color = MaterialTheme.colorScheme.onSurfaceVariant,
                    maxLines = 3,
                    overflow = TextOverflow.Ellipsis,
                )
            }
            Spacer(Modifier.height(6.dp))
            Text("Read more →", fontSize = 13.sp, fontWeight = FontWeight.SemiBold, color = BrandBlue)
        }
    }
}
