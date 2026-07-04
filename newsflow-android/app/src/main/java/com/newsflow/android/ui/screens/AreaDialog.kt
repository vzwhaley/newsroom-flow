package com.newsflow.android.ui.screens

import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.material3.AlertDialog
import androidx.compose.material3.DropdownMenu
import androidx.compose.material3.DropdownMenuItem
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.OutlinedButton
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.input.KeyboardType
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.compose.foundation.text.KeyboardOptions
import com.newsflow.android.data.Area
import com.newsflow.android.data.AreaRequest

/**
 * Country-aware add/edit form for a local area. USA → city + state (+ optional
 * ZIP); international → city + country.
 */
@Composable
fun AreaDialog(
    existing: Area?,
    onDismiss: () -> Unit,
    onSubmit: (AreaRequest) -> Unit,
) {
    var country by remember { mutableStateOf(existing?.countryCode ?: "US") }
    var city by remember { mutableStateOf(existing?.locality ?: "") }
    var state by remember { mutableStateOf(existing?.region ?: "") }
    var zip by remember { mutableStateOf(existing?.postalCode ?: "") }

    val isUs = country == "US"
    val valid = city.isNotBlank() && (!isUs || state.isNotBlank())

    AlertDialog(
        onDismissRequest = onDismiss,
        title = { Text(if (existing == null) "Add a local area" else "Edit local area") },
        text = {
            Column {
                LabeledPicker(
                    label = "Country",
                    value = GeoData.COUNTRIES.firstOrNull { it.first == country }?.second ?: country,
                    options = GeoData.COUNTRIES,
                    onSelect = { country = it },
                )
                Spacer(Modifier.height(10.dp))
                OutlinedTextField(
                    value = city,
                    onValueChange = { city = it },
                    label = { Text("City") },
                    singleLine = true,
                    modifier = Modifier.fillMaxWidth(),
                )
                if (isUs) {
                    Spacer(Modifier.height(10.dp))
                    LabeledPicker(
                        label = "State",
                        value = GeoData.US_STATES.firstOrNull { it.first == state }?.second ?: "Choose a state…",
                        options = GeoData.US_STATES,
                        onSelect = { state = it },
                    )
                    Spacer(Modifier.height(10.dp))
                    OutlinedTextField(
                        value = zip,
                        onValueChange = { if (it.length <= 5 && it.all(Char::isDigit)) zip = it },
                        label = { Text("ZIP (optional)") },
                        singleLine = true,
                        keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Number),
                        modifier = Modifier.fillMaxWidth(),
                    )
                }
            }
        },
        confirmButton = {
            TextButton(
                enabled = valid,
                onClick = {
                    onSubmit(
                        AreaRequest(
                            countryCode = country,
                            city = city.trim(),
                            state = if (isUs) state else null,
                            zip = if (isUs && zip.isNotBlank()) zip else null,
                        ),
                    )
                },
            ) { Text(if (existing == null) "Add" else "Save") }
        },
        dismissButton = { TextButton(onClick = onDismiss) { Text("Cancel") } },
    )
}

/** A dropdown-backed labeled field (country / state pickers). */
@Composable
private fun LabeledPicker(
    label: String,
    value: String,
    options: List<Pair<String, String>>,
    onSelect: (String) -> Unit,
) {
    var open by remember { mutableStateOf(false) }
    Column {
        Text(label, fontSize = 12.sp, fontWeight = FontWeight.Medium, color = MaterialTheme.colorScheme.onSurfaceVariant)
        Box {
            OutlinedButton(onClick = { open = true }, modifier = Modifier.fillMaxWidth()) {
                Text(value, modifier = Modifier.fillMaxWidth())
            }
            DropdownMenu(expanded = open, onDismissRequest = { open = false }) {
                options.forEach { (code, name) ->
                    DropdownMenuItem(text = { Text(name) }, onClick = { onSelect(code); open = false })
                }
            }
        }
    }
}
