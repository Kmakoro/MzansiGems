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
import androidx.compose.material.icons.filled.Bookmark
import androidx.compose.material.icons.filled.DeleteForever
import androidx.compose.material.icons.filled.Edit
import androidx.compose.material.icons.filled.Lock
import androidx.compose.material.icons.filled.Logout
import androidx.compose.material.icons.filled.Map
import androidx.compose.material.icons.filled.Person
import androidx.compose.material.icons.filled.RateReview
import androidx.compose.material.icons.filled.Save
import androidx.compose.material.icons.filled.Settings
import androidx.compose.material3.AlertDialog
import androidx.compose.material3.Button
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.ExperimentalMaterial3Api
import androidx.compose.material3.Icon
import androidx.compose.material3.IconButton
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
import androidx.compose.ui.graphics.Brush
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.input.PasswordVisualTransformation
import androidx.compose.ui.unit.dp
import za.co.hiddengems.app.data.Preferences
import za.co.hiddengems.app.ui.AppUiState
import za.co.hiddengems.app.ui.components.GemCard
import za.co.hiddengems.app.ui.components.ReviewCard
import za.co.hiddengems.app.ui.theme.Orange
import za.co.hiddengems.app.ui.theme.Peach

@Composable
fun ProfileScreen(
    state: AppUiState,
    onLoad: () -> Unit,
    onGem: (Int) -> Unit,
    onSettings: () -> Unit,
    onLogin: () -> Unit,
    onRegister: () -> Unit,
    onAdmin: () -> Unit,
    onEditGem: (Int) -> Unit,
    onDeleteGem: (Int) -> Unit,
    onEditReview: (Int) -> Unit,
    onDeleteReview: (Int) -> Unit,
) {
    val user = state.currentUser
    if (user == null) {
        Box(Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
            Card(
                modifier = Modifier.padding(24.dp).fillMaxWidth(),
                shape = RoundedCornerShape(26.dp),
                colors = CardDefaults.cardColors(containerColor = Color.White),
            ) {
                Column(Modifier.padding(26.dp), horizontalAlignment = Alignment.CenterHorizontally) {
                    Box(Modifier.size(70.dp).background(MaterialTheme.colorScheme.primaryContainer, CircleShape), contentAlignment = Alignment.Center) {
                        Icon(Icons.Default.Person, null, modifier = Modifier.size(36.dp), tint = MaterialTheme.colorScheme.primary)
                    }
                    Spacer(Modifier.height(12.dp))
                    Text("Your Mzansi Gem profile", style = MaterialTheme.typography.headlineSmall, fontWeight = FontWeight.ExtraBold)
                    Text("Sign in to manage posts, saved gems, reviews and settings.", color = MaterialTheme.colorScheme.onSurfaceVariant)
                    Spacer(Modifier.height(18.dp))
                    Button(onClick = onLogin, modifier = Modifier.fillMaxWidth()) { Text("Sign in") }
                    OutlinedButton(onClick = onRegister, modifier = Modifier.fillMaxWidth()) { Text("Create account") }
                }
            }
        }
        return
    }

    LaunchedEffect(user.id) { onLoad() }
    var selectedTab by remember { mutableIntStateOf(0) }
    val profile = state.profile ?: user
    val tabs = listOf("Posted Gems", "Saved Gems", "My Reviews")

    Column(Modifier.fillMaxSize()) {
        Column(
            Modifier.fillMaxWidth().background(Brush.linearGradient(listOf(Orange, Peach))).padding(18.dp),
        ) {
            Row(verticalAlignment = Alignment.Top) {
                Box(Modifier.size(72.dp).background(Color.White.copy(alpha = .95f), CircleShape), contentAlignment = Alignment.Center) {
                    Text(profile.fullName.take(1).uppercase(), style = MaterialTheme.typography.headlineMedium, fontWeight = FontWeight.Black, color = MaterialTheme.colorScheme.primary)
                }
                Spacer(Modifier.width(14.dp))
                Column(Modifier.weight(1f)) {
                    Row(verticalAlignment = Alignment.CenterVertically) {
                        Text(profile.fullName, style = MaterialTheme.typography.titleLarge, fontWeight = FontWeight.ExtraBold, color = Color.White)
                        if (profile.role == "admin") {
                            Spacer(Modifier.width(6.dp))
                            Text("ADMIN", modifier = Modifier.background(Color.White, RoundedCornerShape(30.dp)).padding(horizontal = 8.dp, vertical = 3.dp), color = MaterialTheme.colorScheme.primary, style = MaterialTheme.typography.labelSmall, fontWeight = FontWeight.Bold)
                        }
                    }
                    Text(profile.city, color = Color.White.copy(alpha = .9f))
                    if (profile.bio.isNotBlank()) Text(profile.bio, color = Color.White.copy(alpha = .9f), style = MaterialTheme.typography.bodySmall)
                }
                IconButton(onClick = onSettings) { Icon(Icons.Default.Settings, "Settings", tint = Color.White) }
            }
            Spacer(Modifier.height(14.dp))
            Row(horizontalArrangement = Arrangement.spacedBy(10.dp), modifier = Modifier.fillMaxWidth()) {
                ProfileStat(profile.stats?.posted ?: state.profileGems.size, "Posted", Modifier.weight(1f))
                ProfileStat(profile.stats?.saved ?: state.savedGems.size, "Saved", Modifier.weight(1f))
                ProfileStat(profile.stats?.reviews ?: state.profileReviews.size, "Reviews", Modifier.weight(1f))
            }
            if (profile.role == "admin") {
                Spacer(Modifier.height(12.dp))
                OutlinedButton(onClick = onAdmin, modifier = Modifier.fillMaxWidth()) {
                    Icon(Icons.Default.AdminPanelSettings, null, tint = Color.White)
                    Spacer(Modifier.width(7.dp))
                    Text("Open Admin Control Centre", color = Color.White)
                }
            }
        }
        TabRow(selectedTabIndex = selectedTab) {
            tabs.forEachIndexed { index, title ->
                Tab(selected = selectedTab == index, onClick = { selectedTab = index }, text = { Text(title) })
            }
        }
        when (selectedTab) {
            0 -> ProfileGemList(state.profileGems, onGem, onEditGem, onDeleteGem)
            1 -> ProfileGemList(state.savedGems, onGem)
            else -> ProfileReviewList(state, onEditReview, onDeleteReview)
        }
    }
}

@Composable
private fun ProfileGemList(
    gems: List<za.co.hiddengems.app.data.Gem>,
    onGem: (Int) -> Unit,
    onEdit: ((Int) -> Unit)? = null,
    onDelete: ((Int) -> Unit)? = null,
) {
    if (gems.isEmpty()) {
        EmptyProfileState(Icons.Default.Map, "Nothing here yet", "Your content will appear here as you use Mzansi Gem.")
    } else {
        LazyColumn(contentPadding = PaddingValues(14.dp), verticalArrangement = Arrangement.spacedBy(12.dp)) {
            items(gems, key = { it.id }) { gem ->
                Column {
                    GemCard(gem = gem, onClick = { onGem(gem.id) }, onSave = {})
                    if (onEdit != null && onDelete != null) {
                        Row(horizontalArrangement = Arrangement.spacedBy(8.dp), modifier = Modifier.fillMaxWidth().padding(top = 6.dp)) {
                            OutlinedButton(onClick = { onEdit(gem.id) }, modifier = Modifier.weight(1f)) {
                                Icon(Icons.Default.Edit, null); Spacer(Modifier.width(5.dp)); Text("Edit")
                            }
                            OutlinedButton(onClick = { onDelete(gem.id) }, modifier = Modifier.weight(1f)) {
                                Icon(Icons.Default.DeleteForever, null); Spacer(Modifier.width(5.dp)); Text("Delete")
                            }
                        }
                    }
                }
            }
        }
    }
}

@Composable
private fun ProfileReviewList(state: AppUiState, onEdit: (Int) -> Unit, onDelete: (Int) -> Unit) {
    if (state.profileReviews.isEmpty()) {
        EmptyProfileState(Icons.Default.RateReview, "No reviews yet", "Reviews you write will be listed here.")
    } else {
        LazyColumn(contentPadding = PaddingValues(14.dp), verticalArrangement = Arrangement.spacedBy(10.dp)) {
            items(state.profileReviews, key = { it.id }) { review ->
                Column {
                    Text(review.gemTitle, fontWeight = FontWeight.Bold, color = MaterialTheme.colorScheme.primary)
                    ReviewCard(review = review, onLike = {})
                    Row(verticalAlignment = Alignment.CenterVertically, modifier = Modifier.fillMaxWidth()) {
                        Text("Status: ${review.status}", style = MaterialTheme.typography.labelMedium, color = MaterialTheme.colorScheme.onSurfaceVariant, modifier = Modifier.weight(1f))
                        TextButton(onClick = { onEdit(review.id) }) { Icon(Icons.Default.Edit, null); Text("Edit") }
                        TextButton(onClick = { onDelete(review.id) }) { Icon(Icons.Default.DeleteForever, null); Text("Delete") }
                    }
                }
            }
        }
    }
}

@Composable
private fun EmptyProfileState(icon: androidx.compose.ui.graphics.vector.ImageVector, title: String, subtitle: String) {
    Box(Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
        Column(horizontalAlignment = Alignment.CenterHorizontally, modifier = Modifier.padding(28.dp)) {
            Icon(icon, null, modifier = Modifier.size(50.dp), tint = MaterialTheme.colorScheme.primary)
            Text(title, style = MaterialTheme.typography.titleLarge, fontWeight = FontWeight.Bold)
            Text(subtitle, textAlign = androidx.compose.ui.text.style.TextAlign.Center, color = MaterialTheme.colorScheme.onSurfaceVariant)
        }
    }
}

@Composable
private fun ProfileStat(value: Int, label: String, modifier: Modifier = Modifier) {
    Card(modifier, colors = CardDefaults.cardColors(containerColor = Color.White.copy(alpha = .18f)), shape = RoundedCornerShape(14.dp)) {
        Column(Modifier.fillMaxWidth().padding(10.dp), horizontalAlignment = Alignment.CenterHorizontally) {
            Text(value.toString(), fontWeight = FontWeight.ExtraBold, color = Color.White)
            Text(label, style = MaterialTheme.typography.labelSmall, color = Color.White.copy(alpha = .9f))
        }
    }
}

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun SettingsScreen(
    state: AppUiState,
    onBack: () -> Unit,
    onSaveProfile: (String, String, String, String) -> Unit,
    onSavePreferences: (Preferences) -> Unit,
    onChangePassword: (String, String, String) -> Unit,
    onLogout: () -> Unit,
    onDeleteAccount: (String) -> Unit,
) {
    val user = state.currentUser ?: return
    var name by remember(user.id, user.fullName) { mutableStateOf(user.fullName) }
    var email by remember(user.id, user.email) { mutableStateOf(user.email) }
    var city by remember(user.id, user.city) { mutableStateOf(user.city) }
    var bio by remember(user.id, user.bio) { mutableStateOf(user.bio) }
    var preferences by remember(user.id, user.preferences) { mutableStateOf(user.preferences) }
    var currentPassword by remember { mutableStateOf("") }
    var password by remember { mutableStateOf("") }
    var confirm by remember { mutableStateOf("") }
    var showPasswordDialog by remember { mutableStateOf(false) }
    var showDeleteDialog by remember { mutableStateOf(false) }
    var deletePassword by remember { mutableStateOf("") }

    Scaffold(
        topBar = {
            TopAppBar(
                title = { Text("Settings") },
                navigationIcon = { IconButton(onClick = onBack) { Icon(Icons.AutoMirrored.Filled.ArrowBack, "Back") } },
            )
        },
    ) { padding ->
        LazyColumn(
            modifier = Modifier.fillMaxSize().padding(padding),
            contentPadding = PaddingValues(16.dp),
            verticalArrangement = Arrangement.spacedBy(14.dp),
        ) {
            item { SettingsHeading(Icons.Default.Person, "Profile information") }
            item { OutlinedTextField(name, { name = it }, label = { Text("Full name") }, modifier = Modifier.fillMaxWidth()) }
            item { OutlinedTextField(email, { email = it }, label = { Text("Email") }, modifier = Modifier.fillMaxWidth()) }
            item { OutlinedTextField(city, { city = it }, label = { Text("City") }, modifier = Modifier.fillMaxWidth()) }
            item { OutlinedTextField(bio, { if (it.length <= 255) bio = it }, label = { Text("Bio") }, minLines = 3, supportingText = { Text("${bio.length}/255") }, modifier = Modifier.fillMaxWidth()) }
            item {
                Button(onClick = { onSaveProfile(name, email, city, bio) }, modifier = Modifier.fillMaxWidth()) {
                    Icon(Icons.Default.Save, null)
                    Spacer(Modifier.width(6.dp))
                    Text("Save profile")
                }
            }
            item { SettingsHeading(Icons.Default.Bookmark, "Notifications and privacy") }
            item { SettingsToggle("New gems in your city", "Get notified about new local discoveries", preferences.notifyNewGems) { preferences = preferences.copy(notifyNewGems = it) } }
            item { SettingsToggle("Comments on your posts", "Know when someone comments", preferences.notifyComments) { preferences = preferences.copy(notifyComments = it) } }
            item { SettingsToggle("Likes and saves", "Receive engagement notifications", preferences.notifyLikesSaves) { preferences = preferences.copy(notifyLikesSaves = it) } }
            item { SettingsToggle("Personalised recommendations", "Suggestions based on your activity", preferences.personalizedRecommendations) { preferences = preferences.copy(personalizedRecommendations = it) } }
            item { SettingsToggle("Show saved gems on profile", "Let others see your saved list", preferences.showSavedGems) { preferences = preferences.copy(showSavedGems = it) } }
            item { SettingsToggle("Show activity status", "Display when you are active", preferences.showActivityStatus) { preferences = preferences.copy(showActivityStatus = it) } }
            item { Button(onClick = { onSavePreferences(preferences) }, modifier = Modifier.fillMaxWidth()) { Text("Save preferences") } }
            item { SettingsHeading(Icons.Default.Lock, "Security") }
            item { OutlinedButton(onClick = { showPasswordDialog = true }, modifier = Modifier.fillMaxWidth()) { Icon(Icons.Default.Lock, null); Spacer(Modifier.width(6.dp)); Text("Change password") } }
            item { OutlinedButton(onClick = onLogout, modifier = Modifier.fillMaxWidth()) { Icon(Icons.Default.Logout, null); Spacer(Modifier.width(6.dp)); Text("Log out") } }
            item {
                OutlinedButton(onClick = { showDeleteDialog = true }, modifier = Modifier.fillMaxWidth()) {
                    Icon(Icons.Default.DeleteForever, null, tint = MaterialTheme.colorScheme.error)
                    Spacer(Modifier.width(7.dp))
                    Text("Delete account", color = MaterialTheme.colorScheme.error)
                }
            }
        }
    }

    if (showPasswordDialog) {
        AlertDialog(
            onDismissRequest = { showPasswordDialog = false },
            title = { Text("Change password") },
            text = {
                Column(verticalArrangement = Arrangement.spacedBy(9.dp)) {
                    OutlinedTextField(currentPassword, { currentPassword = it }, label = { Text("Current password") }, visualTransformation = PasswordVisualTransformation())
                    OutlinedTextField(password, { password = it }, label = { Text("New password") }, visualTransformation = PasswordVisualTransformation())
                    OutlinedTextField(confirm, { confirm = it }, label = { Text("Confirm new password") }, visualTransformation = PasswordVisualTransformation())
                }
            },
            confirmButton = {
                Button(onClick = {
                    onChangePassword(currentPassword, password, confirm)
                    currentPassword = ""; password = ""; confirm = ""; showPasswordDialog = false
                }) { Text("Update") }
            },
            dismissButton = { TextButton(onClick = { showPasswordDialog = false }) { Text("Cancel") } },
        )
    }
    if (showDeleteDialog) {
        AlertDialog(
            onDismissRequest = { showDeleteDialog = false },
            title = { Text("Delete your account?") },
            text = {
                Column(verticalArrangement = Arrangement.spacedBy(10.dp)) {
                    Text("This permanently removes your account and associated content. This action cannot be undone.")
                    OutlinedTextField(
                        value = deletePassword,
                        onValueChange = { deletePassword = it },
                        label = { Text("Confirm your password") },
                        visualTransformation = PasswordVisualTransformation(),
                    )
                }
            },
            confirmButton = {
                Button(
                    onClick = { showDeleteDialog = false; onDeleteAccount(deletePassword); deletePassword = "" },
                    enabled = deletePassword.isNotBlank(),
                ) { Text("Delete permanently") }
            },
            dismissButton = { TextButton(onClick = { showDeleteDialog = false }) { Text("Cancel") } },
        )
    }
}

@Composable
private fun SettingsHeading(icon: androidx.compose.ui.graphics.vector.ImageVector, title: String) {
    Row(verticalAlignment = Alignment.CenterVertically) {
        Icon(icon, null, tint = MaterialTheme.colorScheme.primary)
        Spacer(Modifier.width(8.dp))
        Text(title, style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.ExtraBold)
    }
}

@Composable
private fun SettingsToggle(title: String, subtitle: String, checked: Boolean, onChecked: (Boolean) -> Unit) {
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
