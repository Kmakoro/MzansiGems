package za.co.hiddengems.app.ui.theme

import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.lightColorScheme
import androidx.compose.runtime.Composable
import androidx.compose.ui.graphics.Color

val Orange = Color(0xFFED744A)
val OrangeDark = Color(0xFFD95D35)
val Peach = Color(0xFFFFAB72)
val Cream = Color(0xFFFFFAF7)
val Soft = Color(0xFFF8F2EE)
val Ink = Color(0xFF1F2D31)
val Muted = Color(0xFF6F7B7E)
val Teal = Color(0xFF0C4B59)
val Success = Color(0xFF20A66A)
val Line = Color(0xFFEADFD8)

private val HiddenGemsColors = lightColorScheme(
    primary = Orange,
    onPrimary = Color.White,
    primaryContainer = Color(0xFFFFE3D5),
    onPrimaryContainer = OrangeDark,
    secondary = Teal,
    onSecondary = Color.White,
    secondaryContainer = Color(0xFFD5F1F6),
    onSecondaryContainer = Teal,
    background = Cream,
    onBackground = Ink,
    surface = Color.White,
    onSurface = Ink,
    surfaceVariant = Soft,
    onSurfaceVariant = Muted,
    outline = Line,
    error = Color(0xFFD94D54),
)

@Composable
fun HiddenGemsTheme(content: @Composable () -> Unit) {
    MaterialTheme(colorScheme = HiddenGemsColors, content = content)
}
