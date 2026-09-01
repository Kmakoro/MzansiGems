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
import androidx.compose.foundation.lazy.grid.GridCells
import androidx.compose.foundation.lazy.grid.LazyVerticalGrid
import androidx.compose.foundation.lazy.grid.items
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.AutoAwesome
import androidx.compose.material.icons.filled.Coffee
import androidx.compose.material.icons.filled.Explore
import androidx.compose.material.icons.filled.Favorite
import androidx.compose.material.icons.filled.FitnessCenter
import androidx.compose.material.icons.filled.LocalBar
import androidx.compose.material.icons.filled.Museum
import androidx.compose.material.icons.filled.Person
import androidx.compose.material.icons.filled.Search
import androidx.compose.material3.AssistChip
import androidx.compose.material3.Button
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.DropdownMenu
import androidx.compose.material3.DropdownMenuItem
import androidx.compose.material3.ElevatedButton
import androidx.compose.material3.FilledTonalButton
import androidx.compose.material3.Icon
import androidx.compose.material3.IconButton
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.OutlinedButton
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.Surface
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Brush
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.vector.ImageVector
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.unit.dp
import za.co.hiddengems.app.data.Categories
import za.co.hiddengems.app.ui.AppUiState
import za.co.hiddengems.app.ui.components.AppLogo
import za.co.hiddengems.app.ui.components.GemCard
import za.co.hiddengems.app.ui.theme.Orange
import za.co.hiddengems.app.ui.theme.Peach

private data class HomeCategory(val name: String, val icon: ImageVector)

private val homeCategories = listOf(
    HomeCategory("Food", Icons.Default.Coffee),
    HomeCategory("Fitness", Icons.Default.FitnessCenter),
    HomeCategory("Culture", Icons.Default.Museum),
    HomeCategory("Nightlife", Icons.Default.LocalBar),
    HomeCategory("Date Spots", Icons.Default.Favorite),
)

@Composable
fun HomeScreen(
    state: AppUiState,
    onExplore: () -> Unit,
    onSearch: (String) -> Unit,
    onCategory: (String) -> Unit,
    onRandom: () -> Unit,
    onGem: (Int) -> Unit,
    onLike: (Int) -> Unit,
    onSave: (Int) -> Unit,
    onLogin: () -> Unit,
    onRegister: () -> Unit,
    onShare: () -> Unit,
) {
    var query by remember { mutableStateOf("") }
    LazyColumn(
        modifier = Modifier.fillMaxSize(),
        contentPadding = PaddingValues(bottom = 24.dp),
        verticalArrangement = Arrangement.spacedBy(18.dp),
    ) {
        item {
            Row(
                Modifier.fillMaxWidth().padding(horizontal = 18.dp, vertical = 12.dp),
                verticalAlignment = Alignment.CenterVertically,
            ) {
                AppLogo(siteName = state.siteName, modifier = Modifier.weight(1f))
                if (state.currentUser == null) {
                    TextButton(onClick = onLogin) { Text("Sign in") }
                } else {
                    Surface(shape = CircleShape, color = MaterialTheme.colorScheme.primaryContainer) {
                        Box(Modifier.size(42.dp), contentAlignment = Alignment.Center) {
                            Text(state.currentUser.fullName.take(1).uppercase(), fontWeight = FontWeight.ExtraBold, color = MaterialTheme.colorScheme.primary)
                        }
                    }
                }
            }
        }
        item {
            Column(
                Modifier
                    .padding(horizontal = 16.dp)
                    .fillMaxWidth()
                    .background(Brush.linearGradient(listOf(Orange, Peach)), RoundedCornerShape(28.dp))
                    .padding(horizontal = 22.dp, vertical = 30.dp),
                horizontalAlignment = Alignment.CenterHorizontally,
            ) {
                Icon(Icons.Default.Explore, null, tint = Color.White, modifier = Modifier.size(38.dp))
                Spacer(Modifier.height(10.dp))
                Text(
                    "Discover the hidden side of your city",
                    style = MaterialTheme.typography.headlineMedium,
                    fontWeight = FontWeight.ExtraBold,
                    color = Color.White,
                    textAlign = TextAlign.Center,
                )
                Text(
                    "Explore lesser-known gems, authentic experiences, and community favourites across South Africa.",
                    color = Color.White.copy(alpha = .88f),
                    textAlign = TextAlign.Center,
                )
                Spacer(Modifier.height(18.dp))
                OutlinedTextField(
                    value = query,
                    onValueChange = { query = it },
                    placeholder = { Text("Search Mzansi Gem…") },
                    leadingIcon = { Icon(Icons.Default.Search, null) },
                    trailingIcon = {
                        IconButton(onClick = { onSearch(query) }) { Icon(Icons.Default.Search, "Search") }
                    },
                    singleLine = true,
                    shape = RoundedCornerShape(16.dp),
                    modifier = Modifier.fillMaxWidth(),
                )
                Spacer(Modifier.height(12.dp))
                Row(horizontalArrangement = Arrangement.spacedBy(10.dp), modifier = Modifier.fillMaxWidth()) {
                    Button(onClick = onExplore, modifier = Modifier.weight(1f)) {
                        Icon(Icons.Default.Explore, null)
                        Spacer(Modifier.width(6.dp))
                        Text("Explore")
                    }
                    ElevatedButton(onClick = onRandom, modifier = Modifier.weight(1f)) {
                        Icon(Icons.Default.AutoAwesome, null)
                        Spacer(Modifier.width(6.dp))
                        Text("Surprise Me")
                    }
                }
            }
        }
        item { SectionTitle("Trending categories", "Choose your next vibe") }
        item {
            LazyRow(
                contentPadding = PaddingValues(horizontal = 16.dp),
                horizontalArrangement = Arrangement.spacedBy(10.dp),
            ) {
                items(homeCategories) { category ->
                    Card(
                        modifier = Modifier.width(116.dp).clickable {
                            if (category.name == "Date Spots") {
                                onCategory("vibe:Romantic")
                            } else {
                                onCategory("activity:${category.name}")
                            }
                        },
                        shape = RoundedCornerShape(18.dp),
                        colors = CardDefaults.cardColors(containerColor = Color.White),
                    ) {
                        Column(
                            Modifier.fillMaxWidth().padding(vertical = 18.dp, horizontal = 10.dp),
                            horizontalAlignment = Alignment.CenterHorizontally,
                        ) {
                            Box(
                                Modifier.size(44.dp).background(MaterialTheme.colorScheme.primaryContainer, CircleShape),
                                contentAlignment = Alignment.Center,
                            ) { Icon(category.icon, null, tint = MaterialTheme.colorScheme.primary) }
                            Spacer(Modifier.height(9.dp))
                            Text(category.name, fontWeight = FontWeight.SemiBold, textAlign = TextAlign.Center)
                        }
                    }
                }
            }
        }
        item { SectionTitle("Featured Mzansi Gems", "Community favourites") }
        items(state.gems.take(6), key = { it.id }) { gem ->
            GemCard(
                gem = gem,
                onClick = { onGem(gem.id) },
                onLike = { onLike(gem.id) },
                onSave = { onSave(gem.id) },
                modifier = Modifier.padding(horizontal = 16.dp),
            )
        }
        item {
            Column(
                Modifier.padding(horizontal = 16.dp).fillMaxWidth()
                    .background(MaterialTheme.colorScheme.primaryContainer, RoundedCornerShape(24.dp))
                    .padding(22.dp),
                horizontalAlignment = Alignment.CenterHorizontally,
            ) {
                Text("Join Our Community", style = MaterialTheme.typography.titleLarge, fontWeight = FontWeight.Bold)
                Text("Share your favourite spots, earn badges, and help others discover the hidden side of South Africa.", textAlign = TextAlign.Center)
                Spacer(Modifier.height(14.dp))
                if (state.currentUser == null) {
                    Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                        OutlinedButton(onClick = onLogin, modifier = Modifier.weight(1f)) { Text("Sign In") }
                        Button(onClick = onRegister, modifier = Modifier.weight(1f)) { Text("Create an Account") }
                    }
                } else {
                    Button(onClick = onShare, modifier = Modifier.fillMaxWidth()) {
                        Text("Share a Mzansi Gem")
                    }
                }
            }
        }
    }
}

@Composable
fun DiscoverScreen(
    state: AppUiState,
    onFiltersChanged: (String, String, String, String, String, String) -> Unit,
    onSearch: () -> Unit,
    onClear: () -> Unit,
    onGem: (Int) -> Unit,
    onLike: (Int) -> Unit,
    onSave: (Int) -> Unit,
) {
    var query by remember(state.query) { mutableStateOf(state.query) }
    var city by remember(state.city) { mutableStateOf(state.city) }
    var vibe by remember(state.vibe) { mutableStateOf(state.vibe) }
    var budget by remember(state.budget) { mutableStateOf(state.budget) }
    var activity by remember(state.activity) { mutableStateOf(state.activity) }
    var sort by remember(state.sort) { mutableStateOf(state.sort) }

    fun apply() {
        onFiltersChanged(query, city, vibe, budget, activity, sort)
        onSearch()
    }

    Column(Modifier.fillMaxSize()) {
        Column(Modifier.fillMaxWidth().padding(16.dp)) {
            Row(verticalAlignment = Alignment.CenterVertically) {
                Column(Modifier.weight(1f)) {
                    Text("Discover", style = MaterialTheme.typography.headlineMedium, fontWeight = FontWeight.ExtraBold)
                    Text("Find a gem that matches your mood.", color = MaterialTheme.colorScheme.onSurfaceVariant)
                }
                AssistChip(onClick = onClear, label = { Text("Clear all") })
            }
            Spacer(Modifier.height(12.dp))
            OutlinedTextField(
                value = query,
                onValueChange = { query = it },
                placeholder = { Text("Search places or locations") },
                leadingIcon = { Icon(Icons.Default.Search, null) },
                trailingIcon = { IconButton(onClick = { apply() }) { Icon(Icons.Default.Search, "Search") } },
                singleLine = true,
                modifier = Modifier.fillMaxWidth(),
            )
            Spacer(Modifier.height(10.dp))
            LazyRow(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                item { FilterMenu("City", city, state.categories.cities, labelAll = "All Cities") { city = it; apply() } }
                item { FilterMenu("Vibe", vibe, state.categories.vibes, labelAll = "All Vibes") { vibe = it; apply() } }
                item { FilterMenu("Budget", budget, state.categories.budgets, labelAll = "All Budgets") { budget = it; apply() } }
                item { FilterMenu("Activity", activity, state.categories.activities, labelAll = "All Activities") { activity = it; apply() } }
                item { FilterMenu("Sort", sort, listOf("recommended", "rating", "popular", "newest"), allowEmpty = false) { sort = it; apply() } }
            }
            Spacer(Modifier.height(10.dp))
            val count = state.gems.size
            Text("$count gem${if (count == 1) "" else "s"} found", style = MaterialTheme.typography.labelLarge, color = MaterialTheme.colorScheme.primary)
        }
        if (state.gems.isEmpty()) {
            Box(Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                Column(horizontalAlignment = Alignment.CenterHorizontally, modifier = Modifier.padding(32.dp)) {
                    Icon(Icons.Default.Search, null, modifier = Modifier.size(48.dp), tint = MaterialTheme.colorScheme.primary)
                    Text("No gems match these filters", style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.Bold)
                    Text("Clear a filter or try a broader search.", textAlign = TextAlign.Center)
                    Spacer(Modifier.height(10.dp))
                    FilledTonalButton(onClick = onClear) { Text("Reset filters") }
                }
            }
        } else {
            LazyVerticalGrid(
                columns = GridCells.Adaptive(170.dp),
                modifier = Modifier.fillMaxSize(),
                contentPadding = PaddingValues(horizontal = 14.dp, vertical = 8.dp),
                horizontalArrangement = Arrangement.spacedBy(12.dp),
                verticalArrangement = Arrangement.spacedBy(12.dp),
            ) {
                items(state.gems, key = { it.id }) { gem ->
                    GemCard(gem = gem, onClick = { onGem(gem.id) }, onLike = { onLike(gem.id) }, onSave = { onSave(gem.id) })
                }
            }
        }
    }
}

@Composable
private fun FilterMenu(
    label: String,
    value: String,
    options: List<String>,
    allowEmpty: Boolean = true,
    labelAll: String = "All",
    onSelected: (String) -> Unit,
) {
    var expanded by remember { mutableStateOf(false) }
    Box {
        AssistChip(
            onClick = { expanded = true },
            label = { Text(if (value.isBlank()) label else "$label: $value") },
        )
        DropdownMenu(expanded = expanded, onDismissRequest = { expanded = false }) {
            if (allowEmpty) {
                DropdownMenuItem(
                    text = { Text(labelAll) },
                    onClick = { onSelected(""); expanded = false },
                )
            }
            options.forEach { option ->
                DropdownMenuItem(
                    text = { Text(option.replaceFirstChar { it.uppercase() }) },
                    onClick = { onSelected(option); expanded = false },
                )
            }
        }
    }
}

@Composable
private fun SectionTitle(title: String, subtitle: String) {
    Column(Modifier.padding(horizontal = 16.dp)) {
        Text(title, style = MaterialTheme.typography.titleLarge, fontWeight = FontWeight.ExtraBold)
        Text(subtitle, color = MaterialTheme.colorScheme.onSurfaceVariant)
    }
}
