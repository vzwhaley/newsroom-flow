package com.newsflow.android.ui.theme

import androidx.compose.foundation.isSystemInDarkTheme
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.darkColorScheme
import androidx.compose.material3.lightColorScheme
import androidx.compose.runtime.Composable
import androidx.compose.ui.graphics.Color

private val Brand = Color(0xFF2563EB)
private val BrandDark = Color(0xFF1D4ED8)
private val BrandLight = Color(0xFFEFF6FF)
private val Ink = Color(0xFF0F172A)
private val Gray500 = Color(0xFF64748B)
private val Gray100 = Color(0xFFE2E8F0)
private val White = Color(0xFFFFFFFF)
private val Red = Color(0xFFB91C1C)

private val LightColors = lightColorScheme(
    primary = Brand,
    onPrimary = White,
    primaryContainer = BrandLight,
    onPrimaryContainer = BrandDark,
    secondary = Gray500,
    onSecondary = White,
    background = White,
    onBackground = Ink,
    surface = White,
    onSurface = Ink,
    surfaceVariant = Gray100,
    onSurfaceVariant = Gray500,
    error = Red,
    onError = White,
)

private val DarkColors = darkColorScheme(
    primary = Color(0xFF60A5FA),
    onPrimary = Color(0xFF0B1220),
    primaryContainer = Color(0xFF1E3A8A),
    onPrimaryContainer = BrandLight,
    secondary = Gray500,
    background = Color(0xFF0B1220),
    onBackground = White,
    surface = Color(0xFF111827),
    onSurface = White,
    surfaceVariant = Color(0xFF1F2937),
    onSurfaceVariant = Color(0xFF94A3B8),
    error = Color(0xFFF87171),
)

@Composable
fun NewsFlowTheme(
    darkTheme: Boolean = isSystemInDarkTheme(),
    content: @Composable () -> Unit,
) {
    MaterialTheme(
        colorScheme = if (darkTheme) DarkColors else LightColors,
        content = content,
    )
}
