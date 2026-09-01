package za.co.hiddengems.app.ui.screens

import android.net.Uri
import androidx.activity.compose.rememberLauncherForActivityResult
import androidx.activity.result.contract.ActivityResultContracts
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
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.ArrowBack
import androidx.compose.material.icons.filled.AddAPhoto
import androidx.compose.material.icons.filled.BookmarkBorder
import androidx.compose.material.icons.filled.DeleteOutline
import androidx.compose.material.icons.filled.Explore
import androidx.compose.material.icons.filled.LocationOn
import androidx.compose.material.icons.filled.Save
import androidx.compose.material.icons.filled.Star
import androidx.compose.material3.Button
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.DropdownMenuItem
import androidx.compose.material3.ExperimentalMaterial3Api
import androidx.compose.material3.ExposedDropdownMenuBox
import androidx.compose.material3.ExposedDropdownMenuDefaults
import androidx.compose.material3.FilterChip
import androidx.compose.material3.Icon
import androidx.compose.material3.IconButton
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.MenuAnchorType
import androidx.compose.material3.OutlinedButton
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.Scaffold
import androidx.compose.material3.Text
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
import za.co.hiddengems.app.data.GemDraft
import za.co.hiddengems.app.ui.AppUiState
import za.co.hiddengems.app.ui.components.GemCard

@Composable
fun SavedScreen(
    state: AppUiState,
    onLoad: () -> Unit,
    onGem: (Int) -> Unit,
    onToggleLike: (Int) -> Unit,
    onToggleSave: (Int) -> Unit,
    onSaveNote: (Int, String) -> Unit,
    onExplore: () -> Unit,
) {
    LaunchedEffect(Unit) { onLoad() }
    Column(Modifier.fillMaxSize()) {
        Column(Modifier.padding(horizontal = 16.dp, vertical = 14.dp)) {
            Text("Saved Mzansi Gems", style = MaterialTheme.typography.headlineMedium, fontWeight = FontWeight.ExtraBold)
            Text("${state.savedGems.size} saved", color = MaterialTheme.colorScheme.primary)
        }
        if (state.savedGems.isEmpty()) {
            Box(Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                Column(horizontalAlignment = Alignment.CenterHorizontally, modifier = Modifier.padding(30.dp)) {
                    Icon(Icons.Default.BookmarkBorder, null, modifier = Modifier.size(54.dp), tint = MaterialTheme.colorScheme.primary)
                    Spacer(Modifier.height(8.dp))
                    Text("You haven't saved any gems yet", style = MaterialTheme.typography.titleLarge, fontWeight = FontWeight.Bold, textAlign = TextAlign.Center)
                    Text("Start exploring and bookmark your favourites.", textAlign = TextAlign.Center, color = MaterialTheme.colorScheme.onSurfaceVariant)
                    Spacer(Modifier.height(14.dp))
                    Button(onClick = onExplore) {
                        Icon(Icons.Default.Explore, null)
                        Spacer(Modifier.width(6.dp))
                        Text("Explore gems")
                    }
                }
            }
        } else {
            LazyColumn(
                contentPadding = PaddingValues(horizontal = 16.dp, vertical = 4.dp),
                verticalArrangement = Arrangement.spacedBy(14.dp),
            ) {
                items(state.savedGems, key = { it.id }) { gem ->
                    SavedGemCard(gem, onGem, onToggleLike, onToggleSave, onSaveNote)
                }
                item { Spacer(Modifier.height(18.dp)) }
            }
        }
    }
}

@Composable
private fun SavedGemCard(
    gem: Gem,
    onGem: (Int) -> Unit,
    onToggleLike: (Int) -> Unit,
    onToggleSave: (Int) -> Unit,
    onSaveNote: (Int, String) -> Unit,
) {
    var note by remember(gem.id, gem.personalNote) { mutableStateOf(gem.personalNote) }
    Card(
        shape = RoundedCornerShape(22.dp),
        colors = CardDefaults.cardColors(containerColor = Color.White),
        elevation = CardDefaults.cardElevation(3.dp),
    ) {
        Column {
            GemCard(
                gem = gem,
                onClick = { onGem(gem.id) },
                onLike = { onToggleLike(gem.id) },
                onSave = { onToggleSave(gem.id) }
            )
            Column(Modifier.padding(14.dp)) {
                Text("Personal note", fontWeight = FontWeight.Bold)
                OutlinedTextField(
                    value = note,
                    onValueChange = { if (it.length <= 150) note = it },
                    placeholder = { Text("Add your thoughts about this place…") },
                    supportingText = { Text("${note.length}/150") },
                    minLines = 2,
                    modifier = Modifier.fillMaxWidth(),
                )
                Row(horizontalArrangement = Arrangement.spacedBy(8.dp), modifier = Modifier.fillMaxWidth()) {
                    Button(onClick = { onSaveNote(gem.id, note.trim()) }, modifier = Modifier.weight(1f)) {
                        Icon(Icons.Default.Save, null)
                        Spacer(Modifier.width(5.dp))
                        Text("Save Note")
                    }
                    OutlinedButton(onClick = { onToggleSave(gem.id) }, modifier = Modifier.weight(1f)) {
                        Icon(Icons.Default.DeleteOutline, null)
                        Spacer(Modifier.width(5.dp))
                        Text("Remove")
                    }
                }
            }
        }
    }
}

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun ShareGemScreen(
    state: AppUiState,
    onBack: () -> Unit,
    onSubmit: (GemDraft, List<Uri>) -> Unit,
    existingGem: Gem? = null,
) {
    var title by remember(existingGem?.id, existingGem?.title) { mutableStateOf(existingGem?.title.orEmpty()) }
    var description by remember(existingGem?.id, existingGem?.description) { mutableStateOf(existingGem?.description.orEmpty()) }
    var location by remember(existingGem?.id, existingGem?.location) { mutableStateOf(existingGem?.location.orEmpty()) }
    var city by remember(existingGem?.id, existingGem?.city) { mutableStateOf(existingGem?.city ?: state.currentUser?.city.orEmpty()) }
    val selectedVibes = remember(existingGem?.id, existingGem?.vibes) { mutableStateListOf<String>().apply { addAll(existingGem?.vibes.orEmpty()) } }
    var budget by remember(existingGem?.id, existingGem?.budgetLevel) { mutableStateOf(existingGem?.budgetLevel.orEmpty()) }
    var activity by remember(existingGem?.id, existingGem?.activityType) { mutableStateOf(existingGem?.activityType.orEmpty()) }
    var hours by remember(existingGem?.id, existingGem?.operatingHours) { mutableStateOf(existingGem?.operatingHours ?: "All Day") }
    var rating by remember(existingGem?.id, existingGem?.averageRating) { mutableIntStateOf(existingGem?.averageRating?.toInt()?.coerceIn(1, 5) ?: 0) }
    var error by remember { mutableStateOf("") }
    var cityExpanded by remember { mutableStateOf(false) }
    val images = remember { mutableStateListOf<Uri>() }
    val photoPicker = rememberLauncherForActivityResult(ActivityResultContracts.GetMultipleContents()) { selected ->
        images.clear()
        images.addAll(selected.take(3))
    }

    Scaffold(
        topBar = {
            TopAppBar(
                title = { Text(if (existingGem == null) "Share a Mzansi Gem" else "Edit Your Mzansi Gem") },
                navigationIcon = { IconButton(onClick = onBack) { Icon(Icons.AutoMirrored.Filled.ArrowBack, "Back") } },
            )
        },
    ) { padding ->
        LazyColumn(
            modifier = Modifier.fillMaxSize().padding(padding),
            contentPadding = PaddingValues(18.dp),
            verticalArrangement = Arrangement.spacedBy(16.dp),
        ) {
            item {
                Text(if (existingGem == null) "Share a Mzansi Gem" else "Keep your Mzansi Gem information accurate", style = MaterialTheme.typography.titleLarge, fontWeight = FontWeight.ExtraBold)
                Text(if (existingGem == null) "Add a lesser-known place that deserves more attention." else "Edited content may return to moderation before it is published.", color = MaterialTheme.colorScheme.onSurfaceVariant)
            }
            item {
                Text("Photos (up to 3)", fontWeight = FontWeight.Bold)
                Spacer(Modifier.height(8.dp))
                LazyRow(horizontalArrangement = Arrangement.spacedBy(9.dp)) {
                    item {
                        OutlinedButton(onClick = { photoPicker.launch("image/*") }, modifier = Modifier.size(width = 132.dp, height = 100.dp)) {
                            Column(horizontalAlignment = Alignment.CenterHorizontally) {
                                Icon(Icons.Default.AddAPhoto, null)
                                Text("Add photos")
                            }
                        }
                    }
                    items(images) { uri ->
                        AsyncImage(
                            model = uri,
                            contentDescription = "Selected gem photo",
                            modifier = Modifier.size(width = 132.dp, height = 100.dp).clip(RoundedCornerShape(14.dp)),
                            contentScale = ContentScale.Crop,
                        )
                    }
                }
            }
            item { FormField(title, { if (it.length <= 100) title = it }, "Name of place *", "${title.length}/100") }
            item { FormField(description, { if (it.length <= 500) description = it }, "Description *", "${description.length}/500", minLines = 5) }
            item {
                OutlinedTextField(
                    value = location,
                    onValueChange = { location = it },
                    label = { Text("Location *") },
                    leadingIcon = { Icon(Icons.Default.LocationOn, null) },
                    placeholder = { Text("Woodstock, Cape Town") },
                    modifier = Modifier.fillMaxWidth(),
                )
            }
            item {
                ExposedDropdownMenuBox(
                    expanded = cityExpanded,
                    onExpandedChange = { cityExpanded = !cityExpanded },
                    modifier = Modifier.fillMaxWidth()
                ) {
                    OutlinedTextField(
                        value = city,
                        onValueChange = {},
                        readOnly = true,
                        label = { Text("City *") },
                        leadingIcon = { Icon(Icons.Default.LocationOn, null) },
                        trailingIcon = { ExposedDropdownMenuDefaults.TrailingIcon(expanded = cityExpanded) },
                        colors = ExposedDropdownMenuDefaults.outlinedTextFieldColors(),
                        modifier = Modifier.menuAnchor(MenuAnchorType.PrimaryNotEditable, true).fillMaxWidth()
                    )
                    ExposedDropdownMenu(
                        expanded = cityExpanded,
                        onDismissRequest = { cityExpanded = false }
                    ) {
                        state.categories.cities.forEach { selection ->
                            DropdownMenuItem(
                                text = { Text(selection) },
                                onClick = {
                                    city = selection
                                    cityExpanded = false
                                },
                                contentPadding = ExposedDropdownMenuDefaults.ItemContentPadding
                            )
                        }
                    }
                }
            }
            item {
                ChoiceSection("Select vibes (up to 3) *", state.categories.vibes, selectedVibes.toList()) { choice ->
                    if (choice in selectedVibes) selectedVibes.remove(choice)
                    else if (selectedVibes.size < 3) selectedVibes.add(choice)
                }
            }
            item {
                ChoiceSection("Budget level *", state.categories.budgets, listOfNotNull(budget.takeIf { it.isNotBlank() })) { budget = it }
            }
            item {
                ChoiceSection("Activity type *", state.categories.activities, listOfNotNull(activity.takeIf { it.isNotBlank() })) { activity = it }
            }
            item { FormField(hours, { hours = it }, "Operating hours") }
            item {
                Text("Your rating *", fontWeight = FontWeight.Bold)
                Row(horizontalArrangement = Arrangement.spacedBy(7.dp)) {
                    repeat(5) { index ->
                        val value = index + 1
                        Icon(
                            Icons.Default.Star,
                            "$value stars",
                            tint = if (value <= rating) Color(0xFFF0A128) else Color(0xFFD3D3D3),
                            modifier = Modifier.size(38.dp).clickable { rating = value },
                        )
                    }
                }
            }
            if (error.isNotBlank()) item { Text(error, color = MaterialTheme.colorScheme.error) }
            item {
                Button(
                    onClick = {
                        error = when {
                            title.trim().length !in 3..100 -> "Name must be 3 to 100 characters."
                            description.trim().length !in 20..500 -> "Description must be 20 to 500 characters."
                            location.isBlank() -> "Location is required."
                            city.isBlank() -> "City is required."
                            selectedVibes.isEmpty() -> "Select at least one vibe."
                            budget.isBlank() -> "Select a budget level."
                            activity.isBlank() -> "Select an activity type."
                            rating !in 1..5 -> "Choose a rating from 1 to 5."
                            else -> ""
                        }
                        if (error.isBlank()) {
                            onSubmit(
                                GemDraft(
                                    title.trim(), description.trim(), location.trim(), city.trim(), selectedVibes.toList(),
                                    budget, activity, hours.ifBlank { "All Day" }, rating,
                                ),
                                images.toList(),
                            )
                        }
                    },
                    modifier = Modifier.fillMaxWidth().height(54.dp),
                ) {
                    Icon(Icons.Default.AddAPhoto, null)
                    Spacer(Modifier.width(7.dp))
                    Text(if (existingGem == null) "✦ Share This Mzansi Gem" else "Save Changes")
                }
            }
        }
    }
}

@Composable
private fun FormField(
    value: String,
    onValueChange: (String) -> Unit,
    label: String,
    helper: String = "",
    minLines: Int = 1,
) {
    OutlinedTextField(
        value = value,
        onValueChange = onValueChange,
        label = { Text(label) },
        supportingText = if (helper.isBlank()) null else ({ Text(helper) }),
        minLines = minLines,
        modifier = Modifier.fillMaxWidth(),
    )
}

@Composable
private fun ChoiceSection(title: String, options: List<String>, selected: List<String>, onClick: (String) -> Unit) {
    Column {
        Text(title, fontWeight = FontWeight.Bold)
        Spacer(Modifier.height(7.dp))
        LazyRow(horizontalArrangement = Arrangement.spacedBy(7.dp)) {
            items(options) { option ->
                FilterChip(selected = option in selected, onClick = { onClick(option) }, label = { Text(option) })
            }
        }
    }
}
