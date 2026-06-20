package com.newsflow.android.ui.screens

import android.content.Intent
import android.net.Uri
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material3.Button
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.OutlinedButton
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.ui.Modifier
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import androidx.lifecycle.viewmodel.compose.viewModel
import com.newsflow.android.BuildConfig
import com.newsflow.android.data.ServiceLocator
import com.newsflow.android.data.User
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.launch

class AccountViewModel : ViewModel() {
    private val _user = MutableStateFlow<User?>(null)
    val user: StateFlow<User?> = _user

    init {
        viewModelScope.launch {
            runCatching { ServiceLocator.api.me() }.getOrNull()?.body()?.let { _user.value = it.user }
        }
    }
}

@Composable
fun AccountTab(onSignOut: () -> Unit) {
    val vm: AccountViewModel = viewModel()
    val user by vm.user.collectAsState()
    val context = LocalContext.current

    val tierLabel = when {
        user == null -> ""
        user!!.isPro -> "Pro" + (user!!.tier?.let { " · ${it.replaceFirstChar { c -> c.uppercase() }}" } ?: "")
        else -> "Free"
    }

    Column(Modifier.fillMaxSize().padding(20.dp), verticalArrangement = Arrangement.spacedBy(16.dp)) {
        Card(
            modifier = Modifier.fillMaxWidth(),
            shape = RoundedCornerShape(14.dp),
            colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surfaceVariant),
        ) {
            Column(Modifier.padding(18.dp)) {
                Text(user?.name ?: "—", fontSize = 20.sp, fontWeight = FontWeight.Bold, color = MaterialTheme.colorScheme.onSurface)
                Text(user?.email ?: "", fontSize = 14.sp, color = MaterialTheme.colorScheme.onSurfaceVariant)
                Spacer(Modifier.height(10.dp))
                Text("Plan: $tierLabel", fontSize = 14.sp, fontWeight = FontWeight.SemiBold, color = BrandBlue)
                if (user != null && !user!!.isPro) {
                    Text("Up to ${user!!.topicLimit ?: 2} topics", fontSize = 13.sp, color = MaterialTheme.colorScheme.onSurfaceVariant)
                }
            }
        }

        if (user != null && !user!!.isPro) {
            Button(
                onClick = {
                    runCatching { context.startActivity(Intent(Intent.ACTION_VIEW, Uri.parse(BuildConfig.DEFAULT_API_BASE_URL + "/pricing"))) }
                },
                modifier = Modifier.fillMaxWidth(),
            ) { Text("Upgrade to Pro") }
        }

        Spacer(Modifier.height(4.dp))
        OutlinedButton(onClick = onSignOut, modifier = Modifier.fillMaxWidth()) {
            Text("Sign out")
        }

        Spacer(Modifier.weight(1f))
        Text("NewsFlow · by moon whale media, llc", fontSize = 12.sp, color = MaterialTheme.colorScheme.onSurfaceVariant)
    }
}
