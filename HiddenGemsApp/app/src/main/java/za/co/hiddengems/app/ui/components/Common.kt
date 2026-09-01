package za.co.hiddengems.app.ui.components

import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.layout.width
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Bookmark
import androidx.compose.material.icons.filled.BookmarkBorder
import androidx.compose.material.icons.filled.Favorite
import androidx.compose.material.icons.filled.FavoriteBorder
import androidx.compose.material.icons.filled.Flag
import androidx.compose.material.icons.filled.LocationOn
import androidx.compose.material.icons.filled.Star
import androidx.compose.material3.AssistChip
import androidx.compose.material3.AssistChipDefaults
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.CircularProgressIndicator
import androidx.compose.material3.Icon
import androidx.compose.material3.IconButton
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Brush
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.layout.ContentScale
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextOverflow
import androidx.compose.ui.unit.dp
import coil3.compose.AsyncImage
import za.co.hiddengems.app.data.Gem
import za.co.hiddengems.app.data.Review
import za.co.hiddengems.app.ui.theme.Orange
import za.co.hiddengems.app.ui.theme.Peach

@Composable
fun LoadingOverlay(show: Boolean, content: @Composable () -> Unit) {
    Box {
        content()
        if (show) {
            Box(
                modifier = Modifier.matchParentSize().background(Color.Black.copy(alpha = .12f)),
                contentAlignment = Alignment.Center,
            ) {
                CircularProgressIndicator()
            }
        }
    }
}

@Composable
fun AppLogo(siteName: String, modifier: Modifier = Modifier, showName: Boolean = true) {
    Row(modifier = modifier, verticalAlignment = Alignment.CenterVertically) {
        Box(
            modifier = Modifier
                .size(38.dp)
                .clip(RoundedCornerShape(12.dp))
                .background(Brush.linearGradient(listOf(Orange, Peach))),
            contentAlignment = Alignment.Center,
        ) {
            Text("◆", color = Color.White, fontWeight = FontWeight.Black)
        }
        if (showName) {
            Spacer(Modifier.width(10.dp))
            Text(siteName, style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.ExtraBold)
        }
    }
}

@Composable
fun GemCard(
    gem: Gem,
    onClick: () -> Unit,
    onSave: () -> Unit,
    onLike: (() -> Unit)? = null,
    modifier: Modifier = Modifier,
) {
    Card(
        modifier = modifier.fillMaxWidth().clickable(onClick = onClick),
        shape = RoundedCornerShape(18.dp),
        colors = CardDefaults.cardColors(containerColor = Color.White),
        elevation = CardDefaults.cardElevation(defaultElevation = 3.dp),
    ) {
        Box {
            AsyncImage(
                model = gem.coverImage,
                contentDescription = gem.title,
                modifier = Modifier.fillMaxWidth().height(150.dp),
                contentScale = ContentScale.Crop,
            )
            AssistChip(
                onClick = {},
                label = { Text(gem.budgetLevel, style = MaterialTheme.typography.labelSmall) },
                modifier = Modifier.align(Alignment.TopEnd).padding(8.dp),
                colors = AssistChipDefaults.assistChipColors(containerColor = Color.White.copy(alpha = .93f)),
            )
        }
        Column(Modifier.padding(14.dp)) {
            Row(verticalAlignment = Alignment.Top, modifier = Modifier.fillMaxWidth()) {
                Column(Modifier.weight(1f)) {
                    Text(gem.title, fontWeight = FontWeight.Bold, maxLines = 1, overflow = TextOverflow.Ellipsis)
                    Row(verticalAlignment = Alignment.CenterVertically) {
                        Icon(Icons.Default.LocationOn, null, Modifier.size(15.dp), tint = MaterialTheme.colorScheme.primary)
                        Text(gem.location, style = MaterialTheme.typography.bodySmall, color = MaterialTheme.colorScheme.onSurfaceVariant, maxLines = 1, overflow = TextOverflow.Ellipsis)
                    }
                }
                Row {
                    if (onLike != null) {
                        IconButton(onClick = onLike, modifier = Modifier.size(38.dp)) {
                            Icon(if (gem.isLiked) Icons.Default.Favorite else Icons.Default.FavoriteBorder, "Like gem", tint = MaterialTheme.colorScheme.primary)
                        }
                    }
                    IconButton(onClick = onSave, modifier = Modifier.size(38.dp)) {
                        Icon(if (gem.isSaved) Icons.Default.Bookmark else Icons.Default.BookmarkBorder, "Save gem", tint = MaterialTheme.colorScheme.primary)
                    }
                }
            }
            Spacer(Modifier.height(8.dp))
            Row(horizontalArrangement = Arrangement.spacedBy(6.dp)) {
                gem.vibes.take(2).forEach { SmallTag(it) }
                SmallTag(gem.activityType)
            }
            Spacer(Modifier.height(10.dp))
            Row(verticalAlignment = Alignment.CenterVertically, horizontalArrangement = Arrangement.spacedBy(5.dp)) {
                Icon(Icons.Default.Star, null, Modifier.size(17.dp), tint = Color(0xFFF0A128))
                Text(String.format("%.1f", gem.averageRating), fontWeight = FontWeight.SemiBold)
                Text("(${gem.reviewCount})", style = MaterialTheme.typography.bodySmall, color = MaterialTheme.colorScheme.onSurfaceVariant)
                Spacer(Modifier.weight(1f))
                Icon(
                    if (gem.isLiked) Icons.Default.Favorite else Icons.Default.FavoriteBorder,
                    null,
                    Modifier.size(16.dp),
                    tint = if (gem.isLiked) MaterialTheme.colorScheme.primary else MaterialTheme.colorScheme.onSurfaceVariant
                )
                Text(gem.likeCount.toString(), style = MaterialTheme.typography.bodySmall, color = if (gem.isLiked) MaterialTheme.colorScheme.primary else MaterialTheme.colorScheme.onSurface)
            }
        }
    }
}

@Composable
fun SmallTag(text: String) {
    Text(
        text,
        modifier = Modifier.background(MaterialTheme.colorScheme.primaryContainer, RoundedCornerShape(50)).padding(horizontal = 9.dp, vertical = 4.dp),
        color = MaterialTheme.colorScheme.onPrimaryContainer,
        style = MaterialTheme.typography.labelSmall,
        maxLines = 1,
    )
}

@Composable
fun ReviewCard(review: Review, onLike: () -> Unit, onReport: (() -> Unit)? = null) {
    Card(
        modifier = Modifier.fillMaxWidth(),
        shape = RoundedCornerShape(16.dp),
        colors = CardDefaults.cardColors(containerColor = Color.White),
        border = BorderStroke(1.dp, MaterialTheme.colorScheme.outline),
    ) {
        Column(Modifier.padding(15.dp)) {
            Row(verticalAlignment = Alignment.CenterVertically) {
                Box(
                    Modifier.size(38.dp).background(MaterialTheme.colorScheme.primaryContainer, CircleShape),
                    contentAlignment = Alignment.Center,
                ) {
                    Text(review.reviewerName.take(1).uppercase(), fontWeight = FontWeight.Bold, color = MaterialTheme.colorScheme.primary)
                }
                Spacer(Modifier.width(10.dp))
                Column(Modifier.weight(1f)) {
                    Text(review.reviewerName, fontWeight = FontWeight.SemiBold)
                    Text(review.createdAt.take(10), style = MaterialTheme.typography.labelSmall, color = MaterialTheme.colorScheme.onSurfaceVariant)
                }
                Row(verticalAlignment = Alignment.CenterVertically) {
                    if (onReport != null) {
                        IconButton(onClick = onReport, modifier = Modifier.size(32.dp)) {
                            Icon(Icons.Default.Flag, "Report review", tint = MaterialTheme.colorScheme.outline, modifier = Modifier.size(18.dp))
                        }
                        Spacer(Modifier.width(4.dp))
                    }
                    repeat(5) { index ->
                        Icon(Icons.Default.Star, null, Modifier.size(14.dp), tint = if (index < review.rating) Color(0xFFF0A128) else Color(0xFFE0E0E0))
                    }
                }
            }
            if (review.comment.isNotBlank()) {
                Spacer(Modifier.height(10.dp))
                Text(review.comment, style = MaterialTheme.typography.bodyMedium)
            }
            Spacer(Modifier.height(8.dp))
            Row(verticalAlignment = Alignment.CenterVertically) {
                review.vibes.take(3).forEach { tag ->
                    SmallTag(tag)
                    Spacer(Modifier.width(5.dp))
                }
                Spacer(Modifier.weight(1f))
                IconButton(onClick = onLike, modifier = Modifier.size(36.dp)) {
                    Icon(if (review.isLiked) Icons.Default.Favorite else Icons.Default.FavoriteBorder, "Like review", tint = MaterialTheme.colorScheme.primary)
                }
                Text(review.likeCount.toString(), style = MaterialTheme.typography.labelMedium)
            }
        }
    }
}
