package com.newsflow.android.ui.screens

import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.widthIn
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp

val BrandBlue = Color(0xFF2563EB)

/** Form fields fill the width but cap on large screens. */
fun Modifier.fillMaxWidthField(): Modifier = this.fillMaxWidth().widthIn(max = 480.dp)

@Composable
fun BrandHeader(subtitle: String? = null) {
    Column(horizontalAlignment = Alignment.CenterHorizontally) {
        Row(verticalAlignment = Alignment.Top) {
            Text("News", fontSize = 30.sp, fontWeight = FontWeight.Bold, color = MaterialTheme.colorScheme.onBackground)
            Text("Flow", fontSize = 30.sp, fontWeight = FontWeight.Bold, color = BrandBlue)
            Text("™", fontSize = 13.sp, color = MaterialTheme.colorScheme.onBackground)
        }
        Text("by moon whale media, llc", fontSize = 12.sp, color = MaterialTheme.colorScheme.onSurfaceVariant)
        if (subtitle != null) {
            Spacer(Modifier.height(8.dp))
            Text(subtitle, fontSize = 14.sp, color = MaterialTheme.colorScheme.onSurfaceVariant, textAlign = TextAlign.Center)
        }
    }
}
