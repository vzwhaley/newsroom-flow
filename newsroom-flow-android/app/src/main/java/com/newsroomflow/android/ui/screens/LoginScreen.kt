package com.newsroomflow.android.ui.screens

import android.os.Build
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.material3.Button
import androidx.compose.material3.CircularProgressIndicator
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
import androidx.compose.ui.text.input.KeyboardType
import androidx.compose.ui.text.input.PasswordVisualTransformation
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.compose.foundation.text.KeyboardOptions
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import androidx.lifecycle.viewmodel.compose.viewModel
import com.newsroomflow.android.data.LoginRequest
import com.newsroomflow.android.data.ServiceLocator
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.launch

class LoginViewModel : ViewModel() {
    private val _submitting = MutableStateFlow(false)
    val submitting: StateFlow<Boolean> = _submitting
    private val _error = MutableStateFlow<String?>(null)
    val error: StateFlow<String?> = _error

    fun submit(email: String, password: String, onSuccess: () -> Unit) {
        if (email.isBlank() || password.isBlank()) {
            _error.value = "Email and password are required."
            return
        }
        viewModelScope.launch {
            _submitting.value = true
            _error.value = null
            val res = runCatching {
                ServiceLocator.api.login(
                    LoginRequest(email = email.trim(), password = password, deviceName = "${Build.MANUFACTURER} ${Build.MODEL}"),
                )
            }.getOrNull()
            _submitting.value = false

            when {
                res == null -> _error.value = "Couldn't reach NewsroomFlow. Check your connection."
                res.isSuccessful -> {
                    ServiceLocator.authStore.token = res.body()!!.token
                    onSuccess()
                }
                res.code() == 422 -> _error.value = "Invalid email or password."
                else -> _error.value = "Sign-in failed (HTTP ${res.code()})."
            }
        }
    }
}

@Composable
fun LoginScreen(onAuthenticated: () -> Unit, onSwitchToRegister: () -> Unit) {
    val vm: LoginViewModel = viewModel()
    val submitting by vm.submitting.collectAsState()
    val error by vm.error.collectAsState()
    var email by remember { mutableStateOf("") }
    var password by remember { mutableStateOf("") }

    Column(
        modifier = Modifier.fillMaxSize().padding(24.dp),
        verticalArrangement = Arrangement.Center,
        horizontalAlignment = Alignment.CenterHorizontally,
    ) {
        BrandHeader(subtitle = "Build Your Own Newsroom")
        Spacer(Modifier.height(28.dp))

        OutlinedTextField(
            value = email,
            onValueChange = { email = it },
            label = { Text("Email") },
            singleLine = true,
            keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Email),
            modifier = Modifier.fillMaxWidthField(),
        )
        Spacer(Modifier.height(12.dp))
        OutlinedTextField(
            value = password,
            onValueChange = { password = it },
            label = { Text("Password") },
            singleLine = true,
            visualTransformation = PasswordVisualTransformation(),
            keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Password),
            modifier = Modifier.fillMaxWidthField(),
        )

        if (error != null) {
            Spacer(Modifier.height(12.dp))
            Text(error!!, color = MaterialTheme.colorScheme.error, fontSize = 14.sp)
        }

        Spacer(Modifier.height(20.dp))
        Button(
            onClick = { vm.submit(email, password, onAuthenticated) },
            enabled = !submitting,
            modifier = Modifier.fillMaxWidthField(),
        ) {
            if (submitting) CircularProgressIndicator(modifier = Modifier.height(18.dp), strokeWidth = 2.dp)
            else Text("Sign in")
        }

        Spacer(Modifier.height(8.dp))
        TextButton(onClick = onSwitchToRegister) {
            Text("New to NewsroomFlow? Create an account")
        }
    }
}
