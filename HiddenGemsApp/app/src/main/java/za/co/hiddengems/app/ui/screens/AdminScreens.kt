package za.co.hiddengems.app.ui.screens

import androidx.compose.foundation.background
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
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.ArrowBack
import androidx.compose.material.icons.filled.AdminPanelSettings
import androidx.compose.material.icons.filled.Analytics
import androidx.compose.material.icons.filled.CheckCircle
import androidx.compose.material.icons.filled.ContentPasteSearch
import androidx.compose.material.icons.filled.Groups
import androidx.compose.material.icons.filled.PendingActions
import androidx.compose.material.icons.filled.Report
import androidx.compose.material.icons.filled.Save
import androidx.compose.material.icons.filled.Settings
import androidx.compose.material.icons.filled.Star
import androidx.compose.material3.AlertDialog
import androidx.compose.material3.Button
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.ExperimentalMaterial3Api
import androidx.compose.material3.FilterChip
import androidx.compose.material3.Icon
import androidx.compose.material3.IconButton
import androidx.compose.material3.LinearProgressIndicator
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.OutlinedButton
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.Scaffold
import androidx.compose.material3.Switch
import androidx.compose.material3.Tab
import androidx.compose.material3.TabRow
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
import androidx.compose.material3.TopAppBar
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableIntStateOf
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextOverflow
import androidx.compose.ui.unit.dp
import za.co.hiddengems.app.data.ContentReport
import za.co.hiddengems.app.data.Gem
import za.co.hiddengems.app.data.Review
import za.co.hiddengems.app.data.User
import za.co.hiddengems.app.ui.AppUiState

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun AdminScreen(
    state: AppUiState,
    onBack: () -> Unit,
    onLoad: () -> Unit,
    onModerateGem: (Int, String, String) -> Unit,
    onModerateReview: (Int, String) -> Unit,
    onResolveReport: (Int, String, String) -> Unit,
    onUpdateUser: (Int, String, String) -> Unit,
    onSaveSettings: (Map<String, String>) -> Unit,
) {
    LaunchedEffect(Unit) { onLoad() }
    var tab by remember { mutableIntStateOf(0) }
    val tabs = listOf("Overview", "Moderation", "Users", "Analytics", "Settings")

    Scaffold(
        topBar = {
            TopAppBar(
                title = { Text("Admin Control Centre") },
                navigationIcon = { IconButton(onClick = onBack) { Icon(Icons.AutoMirrored.Filled.ArrowBack, "Back") } },
            )
        },
    ) { padding ->
        Column(Modifier.fillMaxSize().padding(padding)) {
            TabRow(selectedTabIndex = tab) {
                tabs.forEachIndexed { index, title -> Tab(selected = tab == index, onClick = { tab = index }, text = { Text(title, maxLines = 1) }) }
            }
            when (tab) {
                0 -> AdminOverview(state)
                1 -> AdminModeration(state, onModerateGem, onModerateReview, onResolveReport)
                2 -> AdminUsers(state.adminUsers, onUpdateUser)
                3 -> AdminAnalyticsView(state)
                else -> AdminSettingsView(state.adminSettings, onSaveSettings)
            }
        }
    }
}

@Composable
private fun AdminOverview(state: AppUiState) {
    val dashboard = state.adminDashboard
    val metrics = dashboard?.metrics
    LazyColumn(contentPadding = PaddingValues(16.dp), verticalArrangement = Arrangement.spacedBy(12.dp)) {
        item {
            Text("Dashboard overview", style = MaterialTheme.typography.headlineSmall, fontWeight = FontWeight.ExtraBold)
            Text("Monitor platform health and recent activity.", color = MaterialTheme.colorScheme.onSurfaceVariant)
        }
        item {
            Row(horizontalArrangement = Arrangement.spacedBy(10.dp), modifier = Modifier.fillMaxWidth()) {
                AdminMetric("Users", metrics?.totalUsers ?: 0, Icons.Default.Groups, Modifier.weight(1f))
                AdminMetric("Posts", metrics?.totalPosts ?: 0, Icons.Default.ContentPasteSearch, Modifier.weight(1f))
            }
        }
        item {
            Row(horizontalArrangement = Arrangement.spacedBy(10.dp), modifier = Modifier.fillMaxWidth()) {
                AdminMetric("Reports", metrics?.activeReports ?: 0, Icons.Default.Report, Modifier.weight(1f))
                AdminMetric("Pending", (metrics?.pendingGems ?: 0) + (metrics?.pendingReviews ?: 0), Icons.Default.PendingActions, Modifier.weight(1f))
            }
        }
        item { Text("Recent activity", style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.Bold) }
        if (dashboard?.recentActivity.isNullOrEmpty()) {
            item { Text("No recent activity to display.", color = MaterialTheme.colorScheme.onSurfaceVariant) }
        } else {
            items(dashboard!!.recentActivity, key = { it.id }) { activity ->
                Card(shape = RoundedCornerShape(15.dp), colors = CardDefaults.cardColors(containerColor = Color.White)) {
                    Row(Modifier.fillMaxWidth().padding(13.dp), verticalAlignment = Alignment.CenterVertically) {
                        Box(Modifier.size(38.dp).background(MaterialTheme.colorScheme.primaryContainer, CircleShape), contentAlignment = Alignment.Center) {
                            Icon(Icons.Default.AdminPanelSettings, null, tint = MaterialTheme.colorScheme.primary)
                        }
                        Spacer(Modifier.width(10.dp))
                        Column(Modifier.weight(1f)) {
                            Text(activity.description.ifBlank { activity.actionType }, fontWeight = FontWeight.SemiBold)
                            Text("${activity.fullName.orEmpty()} · ${activity.createdAt.take(16)}", style = MaterialTheme.typography.labelSmall, color = MaterialTheme.colorScheme.onSurfaceVariant)
                        }
                    }
                }
            }
        }
    }
}

@Composable
private fun AdminMetric(label: String, value: Int, icon: androidx.compose.ui.graphics.vector.ImageVector, modifier: Modifier = Modifier) {
    Card(modifier, shape = RoundedCornerShape(18.dp), colors = CardDefaults.cardColors(containerColor = Color.White)) {
        Column(Modifier.fillMaxWidth().padding(16.dp)) {
            Icon(icon, null, tint = MaterialTheme.colorScheme.primary)
            Spacer(Modifier.height(8.dp))
            Text(value.toString(), style = MaterialTheme.typography.headlineSmall, fontWeight = FontWeight.ExtraBold)
            Text(label, color = MaterialTheme.colorScheme.onSurfaceVariant)
        }
    }
}

@Composable
private fun AdminModeration(
    state: AppUiState,
    onModerateGem: (Int, String, String) -> Unit,
    onModerateReview: (Int, String) -> Unit,
    onResolveReport: (Int, String, String) -> Unit,
) {
    val moderation = state.adminModeration
    LazyColumn(contentPadding = PaddingValues(16.dp), verticalArrangement = Arrangement.spacedBy(12.dp)) {
        item {
            Text("Content moderation", style = MaterialTheme.typography.headlineSmall, fontWeight = FontWeight.ExtraBold)
            Text("Approve quality submissions and act on reports.", color = MaterialTheme.colorScheme.onSurfaceVariant)
        }
        item { AdminSectionTitle("Pending gems", moderation.pendingGems.size) }
        if (moderation.pendingGems.isEmpty()) item { EmptyAdminCard("No pending gems.") }
        items(moderation.pendingGems, key = { "gem-${it.id}" }) { gem -> PendingGemCard(gem, onModerateGem) }
        item { AdminSectionTitle("Pending reviews", moderation.pendingReviews.size) }
        if (moderation.pendingReviews.isEmpty()) item { EmptyAdminCard("No pending reviews.") }
        items(moderation.pendingReviews, key = { "review-${it.id}" }) { review -> PendingReviewCard(review, onModerateReview) }
        item { AdminSectionTitle("Reported content", moderation.reports.size) }
        if (moderation.reports.isEmpty()) item { EmptyAdminCard("No active reports.") }
        items(moderation.reports, key = { "report-${it.id}" }) { report -> ReportCard(report, onResolveReport) }
        item { Spacer(Modifier.height(16.dp)) }
    }
}

@Composable
private fun PendingGemCard(gem: Gem, onModerate: (Int, String, String) -> Unit) {
    var showReject by remember { mutableStateOf(false) }
    var reason by remember { mutableStateOf("") }
    Card(shape = RoundedCornerShape(16.dp), colors = CardDefaults.cardColors(containerColor = Color.White)) {
        Column(Modifier.padding(14.dp)) {
            Text(gem.title, fontWeight = FontWeight.Bold)
            Text("${gem.location}, ${gem.city}", color = MaterialTheme.colorScheme.onSurfaceVariant)
            Text(gem.description, maxLines = 2, overflow = TextOverflow.Ellipsis)
            Spacer(Modifier.height(10.dp))
            Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                Button(onClick = { onModerate(gem.id, "approved", "") }, modifier = Modifier.weight(1f)) { Text("Approve") }
                OutlinedButton(onClick = { showReject = true }, modifier = Modifier.weight(1f)) { Text("Reject") }
            }
        }
    }
    if (showReject) {
        AlertDialog(
            onDismissRequest = { showReject = false },
            title = { Text("Reject ${gem.title}?") },
            text = { OutlinedTextField(reason, { reason = it }, label = { Text("Reason") }, modifier = Modifier.fillMaxWidth()) },
            confirmButton = { Button(onClick = { onModerate(gem.id, "rejected", reason); showReject = false }) { Text("Reject") } },
            dismissButton = { TextButton(onClick = { showReject = false }) { Text("Cancel") } },
        )
    }
}

@Composable
private fun PendingReviewCard(review: Review, onModerate: (Int, String) -> Unit) {
    Card(shape = RoundedCornerShape(16.dp), colors = CardDefaults.cardColors(containerColor = Color.White)) {
        Column(Modifier.padding(14.dp)) {
            Text(review.gemTitle, fontWeight = FontWeight.Bold)
            Row {
                repeat(5) { index -> Icon(Icons.Default.Star, null, modifier = Modifier.size(16.dp), tint = if (index < review.rating) Color(0xFFF0A128) else Color.LightGray) }
            }
            Text(review.comment.ifBlank { "No written comment" }, color = MaterialTheme.colorScheme.onSurfaceVariant)
            Spacer(Modifier.height(10.dp))
            Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                Button(onClick = { onModerate(review.id, "approved") }, modifier = Modifier.weight(1f)) { Text("Approve") }
                OutlinedButton(onClick = { onModerate(review.id, "rejected") }, modifier = Modifier.weight(1f)) { Text("Reject") }
            }
        }
    }
}

@Composable
private fun ReportCard(report: ContentReport, onResolve: (Int, String, String) -> Unit) {
    Card(shape = RoundedCornerShape(16.dp), colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.errorContainer)) {
        Column(Modifier.padding(14.dp)) {
            Text(report.gemTitle ?: "Reported review", fontWeight = FontWeight.Bold)
            Text(report.violationType, color = MaterialTheme.colorScheme.error, fontWeight = FontWeight.SemiBold)
            Text(report.details.orEmpty().ifBlank { "No additional details." })
            Text("Reported by ${report.reporterName}", style = MaterialTheme.typography.labelSmall)
            Spacer(Modifier.height(10.dp))
            Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                Button(onClick = { onResolve(report.id, "resolved", "Actioned in mobile admin") }, modifier = Modifier.weight(1f)) { Text("Resolve") }
                OutlinedButton(onClick = { onResolve(report.id, "dismissed", "Dismissed in mobile admin") }, modifier = Modifier.weight(1f)) { Text("Dismiss") }
            }
        }
    }
}

@Composable
private fun AdminUsers(users: List<User>, onUpdate: (Int, String, String) -> Unit) {
    var editing by remember { mutableStateOf<User?>(null) }
    LazyColumn(contentPadding = PaddingValues(16.dp), verticalArrangement = Arrangement.spacedBy(10.dp)) {
        item {
            Text("User management", style = MaterialTheme.typography.headlineSmall, fontWeight = FontWeight.ExtraBold)
            Text("Manage roles and account status.", color = MaterialTheme.colorScheme.onSurfaceVariant)
        }
        items(users, key = { it.id }) { user ->
            Card(shape = RoundedCornerShape(16.dp), colors = CardDefaults.cardColors(containerColor = Color.White)) {
                Row(Modifier.fillMaxWidth().padding(14.dp), verticalAlignment = Alignment.CenterVertically) {
                    Box(Modifier.size(42.dp).background(MaterialTheme.colorScheme.primaryContainer, CircleShape), contentAlignment = Alignment.Center) {
                        Text(user.fullName.take(1).uppercase(), fontWeight = FontWeight.Bold, color = MaterialTheme.colorScheme.primary)
                    }
                    Spacer(Modifier.width(10.dp))
                    Column(Modifier.weight(1f)) {
                        Text(user.fullName, fontWeight = FontWeight.Bold)
                        Text(user.email, style = MaterialTheme.typography.bodySmall, maxLines = 1, overflow = TextOverflow.Ellipsis)
                        Text("${user.role} · ${user.status} · Level ${user.level}", style = MaterialTheme.typography.labelSmall, color = MaterialTheme.colorScheme.onSurfaceVariant)
                    }
                    OutlinedButton(onClick = { editing = user }) { Text("Edit") }
                }
            }
        }
    }
    editing?.let { user ->
        var role by remember(user.id) { mutableStateOf(user.role) }
        var status by remember(user.id) { mutableStateOf(user.status) }
        AlertDialog(
            onDismissRequest = { editing = null },
            title = { Text("Manage ${user.fullName}") },
            text = {
                Column(verticalArrangement = Arrangement.spacedBy(8.dp)) {
                    Text("Role")
                    Row(horizontalArrangement = Arrangement.spacedBy(7.dp)) {
                        listOf("user", "admin").forEach { FilterChip(selected = role == it, onClick = { role = it }, label = { Text(it) }) }
                    }
                    Text("Status")
                    Row(horizontalArrangement = Arrangement.spacedBy(7.dp)) {
                        listOf("active", "suspended").forEach { FilterChip(selected = status == it, onClick = { status = it }, label = { Text(it) }) }
                    }
                }
            },
            confirmButton = { Button(onClick = { onUpdate(user.id, role, status); editing = null }) { Text("Save") } },
            dismissButton = { TextButton(onClick = { editing = null }) { Text("Cancel") } },
        )
    }
}

@Composable
private fun AdminAnalyticsView(state: AppUiState) {
    val analytics = state.adminAnalytics
    val maximum = analytics?.categories?.maxOfOrNull { it.posts }?.coerceAtLeast(1) ?: 1
    LazyColumn(contentPadding = PaddingValues(16.dp), verticalArrangement = Arrangement.spacedBy(12.dp)) {
        item {
            Text("Platform analytics", style = MaterialTheme.typography.headlineSmall, fontWeight = FontWeight.ExtraBold)
            Text("Growth, engagement and category performance.", color = MaterialTheme.colorScheme.onSurfaceVariant)
        }
        item {
            Row(horizontalArrangement = Arrangement.spacedBy(8.dp), modifier = Modifier.fillMaxWidth()) {
                AdminMetric("Posts", analytics?.engagement?.posts ?: 0, Icons.Default.ContentPasteSearch, Modifier.weight(1f))
                AdminMetric("Reviews", analytics?.engagement?.reviews ?: 0, Icons.Default.Star, Modifier.weight(1f))
            }
        }
        item {
            Row(horizontalArrangement = Arrangement.spacedBy(8.dp), modifier = Modifier.fillMaxWidth()) {
                AdminMetric("Likes", analytics?.engagement?.likes ?: 0, Icons.Default.CheckCircle, Modifier.weight(1f))
                AdminMetric("Saves", analytics?.engagement?.saves ?: 0, Icons.Default.Analytics, Modifier.weight(1f))
            }
        }
        item { Text("Popular categories", style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.Bold) }
        if (analytics?.categories.isNullOrEmpty()) item { EmptyAdminCard("No analytics yet.") }
        items(analytics?.categories.orEmpty(), key = { it.category }) { point ->
            Column {
                Row { Text(point.category, Modifier.weight(1f)); Text("${point.posts} posts") }
                LinearProgressIndicator(progress = { point.posts.toFloat() / maximum.toFloat() }, modifier = Modifier.fillMaxWidth().height(9.dp))
            }
        }
        item { Text("User growth", style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.Bold) }
        items(analytics?.growth.orEmpty(), key = { it.month }) { point ->
            Row(Modifier.fillMaxWidth()) {
                Text(point.month, Modifier.weight(1f))
                Text("${point.total} new users", fontWeight = FontWeight.SemiBold)
            }
        }
    }
}

@Composable
private fun AdminSettingsView(
    settings: Map<String, String>,
    onSave: (Map<String, String>) -> Unit,
) {
    var siteName by remember(settings) { mutableStateOf(settings["site_name"] ?: "Mzansi Gem") }
    var description by remember(settings) { mutableStateOf(settings["site_description"] ?: "Discover South Africa's hidden treasures") }
    var maxPosts by remember(settings) { mutableStateOf(settings["max_posts_per_day"] ?: "10") }
    var allowRegistration by remember(settings) { mutableStateOf(settings["allow_registration"] != "0") }
    var requireVerification by remember(settings) { mutableStateOf(settings["require_email_verification"] == "1") }
    var enableModeration by remember(settings) { mutableStateOf(settings["enable_moderation"] != "0") }
    var autoApprove by remember(settings) { mutableStateOf(settings["auto_approve_content"] == "1") }

    LazyColumn(
        contentPadding = PaddingValues(16.dp),
        verticalArrangement = Arrangement.spacedBy(12.dp),
    ) {
        item {
            Text("Platform settings", style = MaterialTheme.typography.headlineSmall, fontWeight = FontWeight.ExtraBold)
            Text("Configure registration, moderation and general platform behaviour.", color = MaterialTheme.colorScheme.onSurfaceVariant)
        }
        item {
            Row(verticalAlignment = Alignment.CenterVertically) {
                Icon(Icons.Default.Settings, null, tint = MaterialTheme.colorScheme.primary)
                Spacer(Modifier.width(8.dp))
                Text("General", style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.Bold)
            }
        }
        item { OutlinedTextField(siteName, { siteName = it }, label = { Text("Site name") }, modifier = Modifier.fillMaxWidth()) }
        item { OutlinedTextField(description, { description = it }, label = { Text("Site description") }, minLines = 3, modifier = Modifier.fillMaxWidth()) }
        item { OutlinedTextField(maxPosts, { maxPosts = it.filter(Char::isDigit).take(3) }, label = { Text("Maximum posts per user per day") }, modifier = Modifier.fillMaxWidth()) }
        item { AdminSettingToggle("Allow user registration", "Enable new users to create accounts", allowRegistration) { allowRegistration = it } }
        item { AdminSettingToggle("Require email verification", "Users verify email before signing in", requireVerification) { requireVerification = it } }
        item { AdminSettingToggle("Enable content moderation", "Review gems and reviews before publishing", enableModeration) { enableModeration = it } }
        item { AdminSettingToggle("Auto-approve content", "Publish new content without manual approval", autoApprove) { autoApprove = it } }
        item {
            Button(
                onClick = {
                    onSave(
                        mapOf(
                            "site_name" to siteName.trim().ifBlank { "Mzansi Gem" },
                            "site_description" to description.trim(),
                            "allow_registration" to if (allowRegistration) "1" else "0",
                            "require_email_verification" to if (requireVerification) "1" else "0",
                            "max_posts_per_day" to (maxPosts.toIntOrNull() ?: 10).coerceIn(1, 100).toString(),
                            "enable_moderation" to if (enableModeration) "1" else "0",
                            "auto_approve_content" to if (autoApprove) "1" else "0",
                        )
                    )
                },
                modifier = Modifier.fillMaxWidth(),
            ) {
                Icon(Icons.Default.Save, null)
                Spacer(Modifier.width(7.dp))
                Text("Save platform settings")
            }
        }
    }
}

@Composable
private fun AdminSettingToggle(title: String, subtitle: String, checked: Boolean, onChecked: (Boolean) -> Unit) {
    Card(shape = RoundedCornerShape(16.dp), colors = CardDefaults.cardColors(containerColor = Color.White)) {
        Row(Modifier.fillMaxWidth().padding(14.dp), verticalAlignment = Alignment.CenterVertically) {
            Column(Modifier.weight(1f)) {
                Text(title, fontWeight = FontWeight.SemiBold)
                Text(subtitle, style = MaterialTheme.typography.bodySmall, color = MaterialTheme.colorScheme.onSurfaceVariant)
            }
            Switch(checked = checked, onCheckedChange = onChecked)
        }
    }
}

@Composable
private fun AdminSectionTitle(title: String, count: Int) {
    Row(verticalAlignment = Alignment.CenterVertically) {
        Text(title, style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.Bold, modifier = Modifier.weight(1f))
        Text(count.toString(), modifier = Modifier.background(MaterialTheme.colorScheme.primaryContainer, CircleShape).padding(horizontal = 10.dp, vertical = 4.dp), color = MaterialTheme.colorScheme.primary)
    }
}

@Composable
private fun EmptyAdminCard(text: String) {
    Card(shape = RoundedCornerShape(14.dp), colors = CardDefaults.cardColors(containerColor = Color.White)) {
        Text(text, modifier = Modifier.fillMaxWidth().padding(16.dp), color = MaterialTheme.colorScheme.onSurfaceVariant)
    }
}
