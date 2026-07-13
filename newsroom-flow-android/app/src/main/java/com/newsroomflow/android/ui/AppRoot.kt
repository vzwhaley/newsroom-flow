package com.newsroomflow.android.ui

import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.material3.CircularProgressIndicator
import androidx.compose.material3.Surface
import androidx.compose.runtime.Composable
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import androidx.lifecycle.viewmodel.compose.viewModel
import com.newsroomflow.android.data.ServiceLocator
import com.newsroomflow.android.ui.screens.LoginScreen
import com.newsroomflow.android.ui.screens.MainScreen
import com.newsroomflow.android.ui.screens.RegisterScreen
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.launch

enum class AppPhase { Loading, NeedsLogin, SignedIn }

class AuthViewModel : ViewModel() {
    private val _phase = MutableStateFlow(AppPhase.Loading)
    val phase: StateFlow<AppPhase> = _phase

    init {
        refreshSession()
    }

    fun refreshSession() {
        viewModelScope.launch {
            if (!ServiceLocator.authStore.isLoggedIn) {
                _phase.value = AppPhase.NeedsLogin
                return@launch
            }
            // Validate the stored token.
            val ok = runCatching { ServiceLocator.api.me().isSuccessful }.getOrDefault(false)
            _phase.value = if (ok) AppPhase.SignedIn else AppPhase.NeedsLogin
            if (ok) com.newsroomflow.android.data.AdConfigStore.refresh()
        }
    }

    fun onAuthenticated() {
        _phase.value = AppPhase.SignedIn
        viewModelScope.launch { com.newsroomflow.android.data.AdConfigStore.refresh() }
    }

    fun signOut() {
        viewModelScope.launch {
            com.newsroomflow.android.push.PushRegistrar.unregister()
            runCatching { ServiceLocator.api.logout() }
            ServiceLocator.authStore.clear()
            com.newsroomflow.android.data.AdConfigStore.clear()
            _phase.value = AppPhase.NeedsLogin
        }
    }
}

@Composable
fun AppRoot() {
    val authVm: AuthViewModel = viewModel()
    val phase by authVm.phase.collectAsState()
    var showRegister by remember { mutableStateOf(false) }

    Surface(modifier = Modifier.fillMaxSize()) {
        when (phase) {
            AppPhase.Loading -> Box(Modifier.fillMaxSize(), Alignment.Center) {
                CircularProgressIndicator()
            }

            AppPhase.NeedsLogin -> {
                if (showRegister) {
                    RegisterScreen(
                        onAuthenticated = { authVm.onAuthenticated() },
                        onSwitchToLogin = { showRegister = false },
                    )
                } else {
                    LoginScreen(
                        onAuthenticated = { authVm.onAuthenticated() },
                        onSwitchToRegister = { showRegister = true },
                    )
                }
            }

            AppPhase.SignedIn -> MainScreen(onSignOut = { authVm.signOut() })
        }
    }
}
