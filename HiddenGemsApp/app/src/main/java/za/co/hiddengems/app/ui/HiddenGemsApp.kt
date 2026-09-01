package za.co.hiddengems.app.ui

import android.content.Intent
import android.net.Uri
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.padding
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.AddCircle
import androidx.compose.material.icons.filled.Bookmark
import androidx.compose.material.icons.filled.Home
import androidx.compose.material.icons.filled.Person
import androidx.compose.material.icons.filled.Search
import androidx.compose.material3.Icon
import androidx.compose.material3.NavigationBar
import androidx.compose.material3.NavigationBarItem
import androidx.compose.material3.Scaffold
import androidx.compose.material3.SnackbarHost
import androidx.compose.material3.SnackbarHostState
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.runtime.remember
import androidx.compose.ui.Modifier
import androidx.compose.ui.platform.LocalContext
import androidx.navigation.NavGraph.Companion.findStartDestination
import androidx.navigation.NavHostController
import androidx.navigation.compose.NavHost
import androidx.navigation.compose.composable
import androidx.navigation.compose.currentBackStackEntryAsState
import androidx.navigation.compose.rememberNavController
import androidx.navigation.navArgument
import androidx.navigation.NavType
import za.co.hiddengems.app.ui.components.LoadingOverlay
import za.co.hiddengems.app.ui.screens.AdminScreen
import za.co.hiddengems.app.ui.screens.DiscoverScreen
import za.co.hiddengems.app.ui.screens.GemDetailScreen
import za.co.hiddengems.app.ui.screens.HomeScreen
import za.co.hiddengems.app.ui.screens.LoginScreen
import za.co.hiddengems.app.ui.screens.ProfileScreen
import za.co.hiddengems.app.ui.screens.RegisterScreen
import za.co.hiddengems.app.ui.screens.ReviewScreen
import za.co.hiddengems.app.ui.screens.SavedScreen
import za.co.hiddengems.app.ui.screens.SettingsScreen
import za.co.hiddengems.app.ui.screens.ShareGemScreen

object Routes {
    const val HOME = "home"
    const val DISCOVER = "discover"
    const val SHARE = "share"
    const val SAVED = "saved"
    const val PROFILE = "profile"
    const val LOGIN = "login"
    const val REGISTER = "register"
    const val SETTINGS = "settings"
    const val ADMIN = "admin"
    const val GEM = "gem/{gemId}"
    const val REVIEW = "review/{gemId}"
    const val EDIT_GEM = "edit-gem/{gemId}"
    const val EDIT_REVIEW = "edit-review/{reviewId}"

    fun gem(id: Int) = "gem/$id"
    fun review(id: Int) = "review/$id"
    fun editGem(id: Int) = "edit-gem/$id"
    fun editReview(id: Int) = "edit-review/$id"
}

private data class BottomDestination(
    val route: String,
    val label: String,
    val icon: androidx.compose.ui.graphics.vector.ImageVector,
)

private val bottomDestinations = listOf(
    BottomDestination(Routes.HOME, "Home", Icons.Default.Home),
    BottomDestination(Routes.DISCOVER, "Discover", Icons.Default.Search),
    BottomDestination(Routes.SHARE, "Share", Icons.Default.AddCircle),
    BottomDestination(Routes.SAVED, "Saved", Icons.Default.Bookmark),
    BottomDestination(Routes.PROFILE, "Profile", Icons.Default.Person),
)

@Composable
fun MzansiGemApp(viewModel: AppViewModel) {
    val state by viewModel.state.collectAsState()
    val navController = rememberNavController()
    val snackbarHostState = remember { SnackbarHostState() }
    val backStackEntry by navController.currentBackStackEntryAsState()
    val currentRoute = backStackEntry?.destination?.route
    val showBottomBar = currentRoute in bottomDestinations.map { it.route }
    val context = LocalContext.current

    LaunchedEffect(viewModel) {
        viewModel.events.collect { event ->
            when (event) {
                is UiEvent.Message -> snackbarHostState.showSnackbar(event.text)
                is UiEvent.Navigate -> navigateFromEvent(navController, event.route)
            }
        }
    }

    Scaffold(
        snackbarHost = { SnackbarHost(snackbarHostState) },
        bottomBar = {
            if (showBottomBar) {
                NavigationBar {
                    bottomDestinations.forEach { destination ->
                        NavigationBarItem(
                            selected = currentRoute == destination.route,
                            onClick = {
                                if ((destination.route == Routes.SAVED || destination.route == Routes.SHARE || destination.route == Routes.PROFILE) && state.currentUser == null) {
                                    navController.navigate(Routes.LOGIN)
                                } else {
                                    navController.navigate(destination.route) {
                                        popUpTo(navController.graph.findStartDestination().id) { saveState = true }
                                        launchSingleTop = true
                                        restoreState = true
                                    }
                                }
                            },
                            icon = { Icon(destination.icon, contentDescription = destination.label) },
                            label = { Text(destination.label) },
                        )
                    }
                }
            }
        },
    ) { innerPadding ->
        LoadingOverlay(show = state.busy && state.sessionReady) {
            Box(Modifier.padding(innerPadding)) {
                NavHost(navController = navController, startDestination = Routes.HOME) {
                    composable(Routes.HOME) {
                        HomeScreen(
                            state = state,
                            onExplore = { navController.navigate(Routes.DISCOVER) },
                            onSearch = { query ->
                                viewModel.updateFilters(query, "", "", "", "", "recommended")
                                viewModel.loadGems()
                                navController.navigate(Routes.DISCOVER)
                            },
                            onCategory = { selection ->
                                val (type, value) = if (selection.contains(':')) {
                                    selection.split(':', limit = 2)
                                } else {
                                    listOf("activity", selection)
                                }
                                viewModel.updateFilters(
                                    query = "",
                                    city = "",
                                    vibe = if (type == "vibe") value else "",
                                    budget = "",
                                    activity = if (type == "activity") value else "",
                                    sort = "recommended"
                                )
                                viewModel.loadGems()
                                navController.navigate(Routes.DISCOVER)
                            },
                            onRandom = viewModel::randomGem,
                            onGem = { navController.navigate(Routes.gem(it)) },
                            onLike = viewModel::toggleLike,
                            onSave = viewModel::toggleSave,
                            onLogin = { navController.navigate(Routes.LOGIN) },
                            onRegister = { navController.navigate(Routes.REGISTER) },
                            onShare = { navController.navigate(Routes.SHARE) },
                        )
                    }
                    composable(Routes.DISCOVER) {
                        DiscoverScreen(
                            state = state,
                            onFiltersChanged = viewModel::updateFilters,
                            onSearch = viewModel::loadGems,
                            onClear = viewModel::clearFilters,
                            onGem = { navController.navigate(Routes.gem(it)) },
                            onLike = viewModel::toggleLike,
                            onSave = viewModel::toggleSave,
                        )
                    }
                    composable(Routes.SHARE) {
                        ShareGemScreen(
                            state = state,
                            onBack = { navController.popBackStack() },
                            onSubmit = viewModel::shareGem,
                        )
                    }
                    composable(Routes.SAVED) {
                        SavedScreen(
                            state = state,
                            onLoad = viewModel::loadSaved,
                            onGem = { navController.navigate(Routes.gem(it)) },
                            onToggleLike = viewModel::toggleLike,
                            onToggleSave = viewModel::toggleSave,
                            onSaveNote = viewModel::saveNote,
                            onExplore = { navController.navigate(Routes.DISCOVER) },
                        )
                    }
                    composable(Routes.PROFILE) {
                        ProfileScreen(
                            state = state,
                            onLoad = viewModel::loadProfile,
                            onGem = { navController.navigate(Routes.gem(it)) },
                            onSettings = { navController.navigate(Routes.SETTINGS) },
                            onLogin = { navController.navigate(Routes.LOGIN) },
                            onRegister = { navController.navigate(Routes.REGISTER) },
                            onAdmin = { navController.navigate(Routes.ADMIN) },
                            onEditGem = { id -> viewModel.loadGem(id); navController.navigate(Routes.editGem(id)) },
                            onDeleteGem = viewModel::deleteGem,
                            onEditReview = { navController.navigate(Routes.editReview(it)) },
                            onDeleteReview = viewModel::deleteReview,
                        )
                    }
                    composable(Routes.LOGIN) {
                        LoginScreen(
                            busy = state.busy,
                            serverOnline = state.serverOnline,
                            resetToken = state.resetToken,
                            siteName = state.siteName,
                            onBack = { navController.popBackStack() },
                            onLogin = viewModel::login,
                            onForgotPassword = viewModel::forgotPassword,
                            onResetPassword = viewModel::apiResetPassword,
                            onDismissReset = { viewModel.setResetToken(null) },
                            onRegister = { navController.navigate(Routes.REGISTER) },
                        )
                    }
                    composable(Routes.REGISTER) {
                        RegisterScreen(
                            busy = state.busy,
                            serverOnline = state.serverOnline,
                            siteName = state.siteName,
                            cities = state.categories.cities,
                            showVerificationNotice = state.registrationRequiresVerification,
                            onDismissNotice = viewModel::dismissVerificationNotice,
                            onBack = { navController.popBackStack() },
                            onRegister = viewModel::register,
                            onLogin = { navController.navigate(Routes.LOGIN) },
                        )
                    }
                    composable(Routes.SETTINGS) {
                        SettingsScreen(
                            state = state,
                            onBack = { navController.popBackStack() },
                            onSaveProfile = viewModel::updateProfile,
                            onSavePreferences = viewModel::updatePreferences,
                            onChangePassword = viewModel::changePassword,
                            onLogout = viewModel::logout,
                            onDeleteAccount = viewModel::deleteAccount,
                        )
                    }
                    composable(Routes.ADMIN) {
                        AdminScreen(
                            state = state,
                            onBack = { navController.popBackStack() },
                            onLoad = viewModel::loadAdmin,
                            onModerateGem = viewModel::moderateGem,
                            onModerateReview = viewModel::moderateReview,
                            onResolveReport = viewModel::resolveReport,
                            onUpdateUser = viewModel::updateAdminUser,
                            onSaveSettings = viewModel::updateAdminSettings,
                        )
                    }
                    composable(
                        route = Routes.GEM,
                        arguments = listOf(navArgument("gemId") { type = NavType.IntType }),
                    ) { entry ->
                        val id = entry.arguments?.getInt("gemId") ?: return@composable
                        GemDetailScreen(
                            gemId = id,
                            state = state,
                            onLoad = viewModel::loadGem,
                            onBack = { navController.popBackStack() },
                            onLike = viewModel::toggleLike,
                            onSave = viewModel::toggleSave,
                            onShare = { gem ->
                                val sendIntent: Intent = Intent().apply {
                                    action = Intent.ACTION_SEND
                                    putExtra(Intent.EXTRA_TEXT, "Check out this Mzansi Gem: ${gem.title} in ${gem.location}, ${gem.city}! #MzansiGem")
                                    type = "text/plain"
                                }
                                val shareIntent = Intent.createChooser(sendIntent, null)
                                context.startActivity(shareIntent)
                            },
                            onOpenMaps = { location ->
                                val gmmIntentUri = Uri.parse("geo:0,0?q=${Uri.encode(location)}")
                                val mapIntent = Intent(Intent.ACTION_VIEW, gmmIntentUri)
                                mapIntent.setPackage("com.google.android.apps.maps")
                                context.startActivity(mapIntent)
                            },
                            onReview = { navController.navigate(Routes.review(it)) },
                            onReviewLike = viewModel::toggleReviewLike,
                            onReport = viewModel::reportGem,
                            onReviewReport = viewModel::reportReview,
                        )
                    }
                    composable(
                        route = Routes.REVIEW,
                        arguments = listOf(navArgument("gemId") { type = NavType.IntType }),
                    ) { entry ->
                        val id = entry.arguments?.getInt("gemId") ?: return@composable
                        ReviewScreen(
                            gemId = id,
                            gem = state.selectedGem,
                            onBack = { navController.popBackStack() },
                            onSubmit = viewModel::submitReview,
                        )
                    }
                    composable(
                        route = Routes.EDIT_GEM,
                        arguments = listOf(navArgument("gemId") { type = NavType.IntType }),
                    ) { entry ->
                        val id = entry.arguments?.getInt("gemId") ?: return@composable
                        LaunchedEffect(id) { viewModel.loadGem(id) }
                        ShareGemScreen(
                            state = state,
                            onBack = { navController.popBackStack() },
                            existingGem = state.selectedGem?.takeIf { it.id == id },
                            onSubmit = { draft, images -> viewModel.updateGem(id, draft, images) },
                        )
                    }
                    composable(
                        route = Routes.EDIT_REVIEW,
                        arguments = listOf(navArgument("reviewId") { type = NavType.IntType }),
                    ) { entry ->
                        val reviewId = entry.arguments?.getInt("reviewId") ?: return@composable
                        val review = state.profileReviews.firstOrNull { it.id == reviewId }
                        ReviewScreen(
                            gemId = review?.gemId ?: 0,
                            gem = state.gems.firstOrNull { it.id == review?.gemId },
                            existingReview = review,
                            onBack = { navController.popBackStack() },
                            onSubmit = { id, rating, comment, vibes -> viewModel.updateReview(id, rating, comment, vibes) },
                        )
                    }
                }
            }
        }
    }
}

private fun navigateFromEvent(navController: NavHostController, route: String) {
    navController.navigate(route) {
        launchSingleTop = true
        if (route == Routes.HOME) {
            popUpTo(navController.graph.findStartDestination().id) { inclusive = false }
        }
    }
}
