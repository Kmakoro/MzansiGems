package za.co.hiddengems.app.ui.screens

import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.PaddingValues
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.layout.width
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.LazyRow
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.ArrowBack
import androidx.compose.material.icons.filled.AccessTime
import androidx.compose.material.icons.filled.Bookmark
import androidx.compose.material.icons.filled.BookmarkBorder
import androidx.compose.material.icons.filled.Edit
import androidx.compose.material.icons.filled.Favorite
import androidx.compose.material.icons.filled.FavoriteBorder
import androidx.compose.material.icons.filled.Flag
import androidx.compose.material.icons.filled.LocationOn
import androidx.compose.material.icons.filled.Share
import androidx.compose.material.icons.filled.Star
import androidx.compose.material3.AlertDialog
import androidx.compose.material3.Button
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.ExperimentalMaterial3Api
import androidx.compose.material3.FilterChip
import androidx.compose.material3.Icon
import androidx.compose.material3.IconButton
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.OutlinedButton
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.Scaffold
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
import androidx.compose.material3.TopAppBar
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableIntStateOf
import androidx.compose.runtime.mutableStateListOf
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.layout.ContentScale
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.unit.dp
import coil3.compose.AsyncImage
import za.co.hiddengems.app.data.Gem
import za.co.hiddengems.app.data.Review
import za.co.hiddengems.app.ui.AppUiState
import za.co.hiddengems.app.ui.components.ReviewCard
import za.co.hiddengems.app.ui.components.SmallTag

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun GemDetailScreen(
    gemId: Int,
    state: AppUiState,
    onLoad: (Int) -> Unit,
    onBack: () -> Unit,
    onLike: (Int) -> Unit,
    onSave: (Int) -> Unit,
    onShare: (Gem) -> Unit,
    onOpenMaps: (String) -> Unit,
    onReview: (Int) -> Unit,
    onReviewLike: (Int) -> Unit,
    onReport: (Int, String, String) -> Unit,
    onReviewReport: (Int, String, String) -> Unit,
) {
    LaunchedEffect(gemId) { onLoad(gemId) }
    val gem = state.selectedGem?.takeIf { it.id == gemId }
    var showReport by remember { mutableStateOf(false) }
    var reportingReviewId by remember { mutableStateOf<Int?>(null) }

    Scaffold(
        topBar = {
            TopAppBar(
                title = { Text(gem?.title ?: state.siteName, maxLines = 1) },
                navigationIcon = { IconButton(onClick = onBack) { Icon(Icons.AutoMirrored.Filled.ArrowBack, "Back") } },
                actions = {
                    IconButton(onClick = { showReport = true }) { Icon(Icons.Default.Flag, "Report") }
                },
            )
        },
    ) { padding ->
        if (gem == null) {
            Box(Modifier.fillMaxSize().padding(padding), contentAlignment = Alignment.Center) {
                Text("Loading gem details…")
            }
        } else {
            LazyColumn(
                modifier = Modifier.fillMaxSize().padding(padding),
                contentPadding = PaddingValues(bottom = 28.dp),
            ) {
                item {
                    Box {
                        AsyncImage(
                            model = gem.coverImage,
                            contentDescription = gem.title,
                            modifier = Modifier.fillMaxWidth().height(280.dp),
                            contentScale = ContentScale.Crop,
                        )
                        Row(
                            Modifier.align(Alignment.BottomEnd).padding(14.dp),
                            horizontalArrangement = Arrangement.spacedBy(8.dp),
                        ) {
                            RoundAction(
                                selected = gem.isLiked,
                                selectedIcon = Icons.Default.Favorite,
                                normalIcon = Icons.Default.FavoriteBorder,
                                description = "Like",
                                onClick = { onLike(gem.id) },
                            )
                            RoundAction(
                                selected = gem.isSaved,
                                selectedIcon = Icons.Default.Bookmark,
                                normalIcon = Icons.Default.BookmarkBorder,
                                description = "Save",
                                onClick = { onSave(gem.id) },
                            )
                        }
                    }
                }
                item {
                    Column(Modifier.padding(18.dp)) {
                        Text(gem.title, style = MaterialTheme.typography.headlineSmall, fontWeight = FontWeight.ExtraBold)
                        Row(verticalAlignment = Alignment.CenterVertically) {
                            Icon(Icons.Default.LocationOn, null, Modifier.size(18.dp), tint = MaterialTheme.colorScheme.primary)
                            Text(gem.location, color = MaterialTheme.colorScheme.onSurfaceVariant)
                        }
                        Spacer(Modifier.height(14.dp))
                        Row(horizontalArrangement = Arrangement.spacedBy(8.dp), modifier = Modifier.fillMaxWidth()) {
                            MetricCard("${String.format("%.1f", gem.averageRating)} ★", "Rating", Modifier.weight(1f))
                            MetricCard(gem.reviewCount.toString(), "Reviews", Modifier.weight(1f))
                            MetricCard(gem.budgetLevel, "Budget", Modifier.weight(1f))
                            MetricCard(gem.operatingHours, "Hours", Modifier.weight(1f))
                        }
                        Spacer(Modifier.height(18.dp))
                        Text("Vibe & Activity", style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.Bold)
                        Spacer(Modifier.height(8.dp))
                        LazyRow(horizontalArrangement = Arrangement.spacedBy(7.dp)) {
                            items(gem.vibes + gem.activityType) { SmallTag(it) }
                        }
                        Spacer(Modifier.height(20.dp))
                        Text("About this place", style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.Bold)
                        Spacer(Modifier.height(6.dp))
                        Text(gem.description, color = MaterialTheme.colorScheme.onSurfaceVariant)
                        Spacer(Modifier.height(18.dp))
                        Card(
                            onClick = { onOpenMaps(gem.location) },
                            colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.secondaryContainer),
                            shape = RoundedCornerShape(18.dp),
                        ) {
                            Row(Modifier.fillMaxWidth().padding(16.dp), verticalAlignment = Alignment.CenterVertically) {
                                Icon(Icons.Default.LocationOn, null, tint = MaterialTheme.colorScheme.secondary)
                                Spacer(Modifier.width(10.dp))
                                Column(Modifier.weight(1f)) {
                                    Text("Location", fontWeight = FontWeight.Bold)
                                    Text("${gem.location}, ${gem.city}")
                                    Text("Open in Maps", style = MaterialTheme.typography.labelSmall, color = MaterialTheme.colorScheme.secondary, fontWeight = FontWeight.Bold)
                                }
                                Icon(Icons.Default.AccessTime, null, tint = MaterialTheme.colorScheme.secondary)
                            }
                        }
                    }
                }
                if (gem.images.isNotEmpty()) {
                    item {
                        Text("Photo gallery", style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.Bold, modifier = Modifier.padding(horizontal = 18.dp))
                        Spacer(Modifier.height(8.dp))
                        LazyRow(contentPadding = PaddingValues(horizontal = 18.dp), horizontalArrangement = Arrangement.spacedBy(10.dp)) {
                            items(gem.images) { image ->
                                AsyncImage(
                                    model = image,
                                    contentDescription = gem.title,
                                    modifier = Modifier.size(width = 190.dp, height = 125.dp).clip(RoundedCornerShape(16.dp)),
                                    contentScale = ContentScale.Crop,
                                )
                            }
                        }
                        Spacer(Modifier.height(20.dp))
                    }
                }
                item {
                    Row(Modifier.fillMaxWidth().padding(horizontal = 18.dp), verticalAlignment = Alignment.CenterVertically) {
                        Column(Modifier.weight(1f)) {
                            Text("Community reviews", style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.Bold)
                            Text("${gem.reviews.size} approved reviews", color = MaterialTheme.colorScheme.onSurfaceVariant)
                        }
                        Button(onClick = { onReview(gem.id) }) {
                            Icon(Icons.Default.Edit, null)
                            Spacer(Modifier.width(5.dp))
                            Text("Write a Review")
                        }
                    }
                    Spacer(Modifier.height(10.dp))
                }
                if (gem.reviews.isEmpty()) {
                    item {
                        Column(Modifier.fillMaxWidth().padding(28.dp), horizontalAlignment = Alignment.CenterHorizontally) {
                            Icon(Icons.Default.Star, null, modifier = Modifier.size(44.dp), tint = MaterialTheme.colorScheme.primary)
                            Text("No approved reviews yet", fontWeight = FontWeight.Bold)
                            Text("Be the first person to share an experience.", textAlign = TextAlign.Center)
                        }
                    }
                } else {
                    items(gem.reviews, key = { it.id }) { review ->
                        Box(Modifier.padding(horizontal = 18.dp, vertical = 5.dp)) {
                            ReviewCard(
                                review = review,
                                onLike = { onReviewLike(review.id) },
                                onReport = { reportingReviewId = review.id }
                            )
                        }
                    }
                }
            }
        }
    }

    if (showReport && gem != null) {
        ReportDialog(
            title = "Report this gem",
            onDismiss = { showReport = false },
            onSubmit = { type, details ->
                onReport(gem.id, type, details)
                showReport = false
            },
        )
    }
}

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun ReviewScreen(
    gemId: Int,
    gem: Gem?,
    onBack: () -> Unit,
    onSubmit: (Int, Int, String, List<String>) -> Unit,
    existingReview: Review? = null,
) {
    var rating by remember(existingReview?.id, existingReview?.rating) { mutableIntStateOf(existingReview?.rating ?: 0) }
    var comment by remember(existingReview?.id, existingReview?.comment) { mutableStateOf(existingReview?.comment.orEmpty()) }
    val selectedVibes = remember(existingReview?.id, existingReview?.vibes) { mutableStateListOf<String>().apply { addAll(existingReview?.vibes.orEmpty()) } }
    var error by remember { mutableStateOf("") }
    val vibes = listOf("Romantic", "Chill", "Food", "Date Night")

    Scaffold(
        topBar = {
            TopAppBar(
                title = { Text(if (existingReview == null) "Share your experience" else "Edit your review") },
                navigationIcon = { IconButton(onClick = onBack) { Icon(Icons.AutoMirrored.Filled.ArrowBack, "Back") } },
            )
        },
    ) { padding ->
        LazyColumn(
            modifier = Modifier.fillMaxSize().padding(padding),
            contentPadding = PaddingValues(20.dp),
            verticalArrangement = Arrangement.spacedBy(18.dp),
        ) {
            item {
                Text("${if (existingReview == null) "Review" else "Update review"} for ${gem?.title ?: existingReview?.gemTitle ?: "this hidden gem"}", style = MaterialTheme.typography.titleLarge, fontWeight = FontWeight.ExtraBold)
                Text("Your honest feedback helps the community discover confidently.", color = MaterialTheme.colorScheme.onSurfaceVariant)
            }
            item {
                Text("Your rating *", fontWeight = FontWeight.Bold)
                Spacer(Modifier.height(8.dp))
                Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                    repeat(5) { index ->
                        val value = index + 1
                        Icon(
                            imageVector = Icons.Default.Star,
                            contentDescription = "$value stars",
                            tint = if (value <= rating) Color(0xFFF0A128) else Color(0xFFD6D6D6),
                            modifier = Modifier.size(42.dp).clickable { rating = value; error = "" },
                        )
                    }
                }
                Text(if (rating == 0) "Select 1 to 5 stars" else "$rating out of 5 stars", color = MaterialTheme.colorScheme.onSurfaceVariant)
            }
            item {
                OutlinedTextField(
                    value = comment,
                    onValueChange = { if (it.length <= 300) comment = it },
                    label = { Text("Write your review") },
                    placeholder = { Text("What did you love? Any tips for others?") },
                    minLines = 5,
                    supportingText = { Text("${comment.length}/300 characters") },
                    modifier = Modifier.fillMaxWidth(),
                )
            }
            item {
                Text("Vibe & Activity (optional)", fontWeight = FontWeight.Bold)
                Spacer(Modifier.height(7.dp))
                LazyRow(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                    items(vibes) { vibe ->
                        FilterChip(
                            selected = vibe in selectedVibes,
                            onClick = { if (vibe in selectedVibes) selectedVibes.remove(vibe) else selectedVibes.add(vibe) },
                            label = { Text(vibe) },
                        )
                    }
                }
            }
            if (error.isNotBlank()) {
                item { Text(error, color = MaterialTheme.colorScheme.error) }
            }
            item {
                Button(
                    onClick = {
                        if (rating == 0) error = "Please select a rating between 1 and 5 stars."
                        else onSubmit(if (existingReview == null) gemId else existingReview.id, rating, comment.trim(), selectedVibes.toList())
                    },
                    modifier = Modifier.fillMaxWidth().height(52.dp),
                ) { Text(if (existingReview == null) "Post Review" else "Save Review Changes") }
            }
        }
    }
}

@Composable
private fun RoundAction(
    selected: Boolean,
    selectedIcon: androidx.compose.ui.graphics.vector.ImageVector,
    normalIcon: androidx.compose.ui.graphics.vector.ImageVector,
    description: String,
    onClick: () -> Unit,
) {
    Box(Modifier.size(48.dp).background(Color.White.copy(alpha = .94f), CircleShape), contentAlignment = Alignment.Center) {
        IconButton(onClick = onClick) {
            Icon(if (selected) selectedIcon else normalIcon, description, tint = MaterialTheme.colorScheme.primary)
        }
    }
}

@Composable
private fun MetricCard(value: String, label: String, modifier: Modifier = Modifier) {
    Card(modifier = modifier, shape = RoundedCornerShape(14.dp), colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surfaceVariant)) {
        Column(Modifier.fillMaxWidth().padding(horizontal = 6.dp, vertical = 12.dp), horizontalAlignment = Alignment.CenterHorizontally) {
            Text(value, fontWeight = FontWeight.ExtraBold, maxLines = 1)
            Text(label, style = MaterialTheme.typography.labelSmall, color = MaterialTheme.colorScheme.onSurfaceVariant)
        }
    }
}

@Composable
private fun ReportDialog(title: String, onDismiss: () -> Unit, onSubmit: (String, String) -> Unit) {
    var type by remember { mutableStateOf("Inappropriate content") }
    var details by remember { mutableStateOf("") }
    AlertDialog(
        onDismissRequest = onDismiss,
        title = { Text(title) },
        text = {
            Column(verticalArrangement = Arrangement.spacedBy(10.dp)) {
                Text("Choose a reason")
                listOf("Inappropriate content", "Incorrect information", "Spam or duplicate", "Safety concern").forEach { option ->
                    FilterChip(selected = type == option, onClick = { type = option }, label = { Text(option) })
                }
                OutlinedTextField(
                    value = details,
                    onValueChange = { if (it.length <= 400) details = it },
                    label = { Text("Additional details") },
                    modifier = Modifier.fillMaxWidth(),
                )
            }
        },
        confirmButton = { Button(onClick = { onSubmit(type, details) }) { Text("Submit report") } },
        dismissButton = { TextButton(onClick = onDismiss) { Text("Cancel") } },
    )
}
