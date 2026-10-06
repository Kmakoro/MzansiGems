package za.co.hiddengems.app.ui

import android.app.Application
import android.net.Uri
import androidx.lifecycle.AndroidViewModel
import androidx.lifecycle.ViewModel
import androidx.lifecycle.ViewModelProvider
import androidx.lifecycle.viewModelScope
import kotlinx.coroutines.flow.MutableSharedFlow
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.SharedFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asSharedFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import za.co.hiddengems.app.HiddenGemsApplication
import za.co.hiddengems.app.data.AdminAnalytics
import za.co.hiddengems.app.data.AdminDashboard
import za.co.hiddengems.app.data.ApiResult
import za.co.hiddengems.app.data.Categories
import za.co.hiddengems.app.data.ModerationPayload
import za.co.hiddengems.app.data.Gem
import za.co.hiddengems.app.data.GemDraft
import za.co.hiddengems.app.data.HiddenGemsRepository
import za.co.hiddengems.app.data.Preferences
import za.co.hiddengems.app.data.Review
import za.co.hiddengems.app.data.SessionManager
import za.co.hiddengems.app.data.User

data class AppUiState(
    val sessionReady: Boolean = false,
    val busy: Boolean = false,
    val currentUser: User? = null,
    val categories: Categories = Categories(),
    val gems: List<Gem> = emptyList(),
    val selectedGem: Gem? = null,
    val savedGems: List<Gem> = emptyList(),
    val profile: User? = null,
    val profileGems: List<Gem> = emptyList(),
    val profileReviews: List<Review> = emptyList(),
    val adminDashboard: AdminDashboard? = null,
    val adminModeration: ModerationPayload = ModerationPayload(),
    val adminUsers: List<User> = emptyList(),
    val adminAnalytics: AdminAnalytics? = null,
    val adminSettings: Map<String, String> = emptyMap(),
    val siteName: String = "Mzansi Gem",
    val registrationRequiresVerification: Boolean = false,
    val resetToken: String? = null,
    val query: String = "",
    val city: String = "",
    val vibe: String = "",
    val budget: String = "",
    val activity: String = "",
    val sort: String = "recommended",
    val serverOnline: Boolean? = null,
)

sealed interface UiEvent {
    data class Message(val text: String) : UiEvent
    data class Navigate(val route: String) : UiEvent
}

class AppViewModel(
    application: Application,
    private val repository: HiddenGemsRepository,
    private val session: SessionManager,
) : AndroidViewModel(application) {
    private val _state = MutableStateFlow(AppUiState())
    val state: StateFlow<AppUiState> = _state.asStateFlow()

    private val _events = MutableSharedFlow<UiEvent>(extraBufferCapacity = 8)
    val events: SharedFlow<UiEvent> = _events.asSharedFlow()

    init {
        bootstrap()
        checkHealth()
        observeSiteName()
    }

    private fun observeSiteName() = viewModelScope.launch {
        session.siteNameFlow.collect { name ->
            _state.update { it.copy(siteName = name) }
        }
    }

    fun checkHealth() = viewModelScope.launch {
        val result = repository.health()
        _state.update { it.copy(serverOnline = result is ApiResult.Success) }
    }

    private fun bootstrap() = viewModelScope.launch {
        _state.update { it.copy(busy = true) }
        when (val categories = repository.categories()) {
            is ApiResult.Success -> _state.update { it.copy(categories = categories.value) }
            is ApiResult.Error -> _events.emit(UiEvent.Message(categories.message))
        }
        when (val gems = repository.gems(null, null, null, null, null, "recommended")) {
            is ApiResult.Success -> _state.update { it.copy(gems = gems.value) }
            is ApiResult.Error -> _events.emit(UiEvent.Message(gems.message))
        }
        val token = session.token()
        if (!token.isNullOrBlank()) {
            when (val me = repository.me()) {
                is ApiResult.Success -> {
                    _state.update { it.copy(currentUser = me.value) }
                    if (me.value.role == "admin") {
                        loadAdminSettingsOnly()
                    }
                }
                is ApiResult.Error -> session.clear()
            }
        }
        _state.update { it.copy(sessionReady = true, busy = false) }
    }

    private fun loadAdminSettingsOnly() = viewModelScope.launch {
        val settings = repository.adminSettings()
        if (settings is ApiResult.Success) {
            val name = settings.value["site_name"]
            if (!name.isNullOrBlank()) {
                session.saveSiteName(name)
            }
            _state.update { it.copy(adminSettings = settings.value) }
        }
    }

    fun login(email: String, password: String) = launchBusy {
        when (val result = repository.login(email.trim(), password)) {
            is ApiResult.Success -> {
                _state.update { it.copy(currentUser = result.value) }
                _events.emit(UiEvent.Message(result.message.ifBlank { "Welcome back!" }))
                _events.emit(UiEvent.Navigate("home"))
            }
            is ApiResult.Error -> _events.emit(UiEvent.Message(result.message))
        }
    }

    fun firebaseLogin(idToken: String) = launchBusy {
        when (val result = repository.firebaseLogin(idToken)) {
            is ApiResult.Success -> {
                _state.update { it.copy(currentUser = result.value) }
                _events.emit(UiEvent.Message(result.message.ifBlank { "Signed in with Google." }))
                _events.emit(UiEvent.Navigate("home"))
            }
            is ApiResult.Error -> _events.emit(UiEvent.Message(result.message))
        }
    }

    fun forgotPassword(email: String) = launchBusy {
        when (val result = repository.forgotPassword(email.trim())) {
            is ApiResult.Success -> _events.emit(UiEvent.Message(result.message))
            is ApiResult.Error -> _events.emit(UiEvent.Message(result.message))
        }
    }

    fun register(name: String, email: String, city: String, password: String, confirm: String) = launchBusy {
        when (val result = repository.register(name.trim(), email.trim(), city.trim(), password, confirm)) {
            is ApiResult.Success -> {
                if (!result.value.emailVerified) {
                    _state.update {
                        it.copy(
                            currentUser = null,
                            registrationRequiresVerification = true,
                        )
                    }
                    _events.emit(UiEvent.Message(result.message.ifBlank { "Verify your email address before signing in." }))
                } else {
                    _state.update { it.copy(currentUser = result.value, registrationRequiresVerification = false) }
                    _events.emit(UiEvent.Message(result.message.ifBlank { "Account created!" }))
                    _events.emit(UiEvent.Navigate("home"))
                }
            }
            is ApiResult.Error -> _events.emit(UiEvent.Message(result.message))
        }
    }

    fun verifyEmail(token: String) = launchBusy {
        when (val result = repository.verifyEmail(token)) {
            is ApiResult.Success -> {
                _events.emit(UiEvent.Message(result.message))
                _events.emit(UiEvent.Navigate("login"))
            }
            is ApiResult.Error -> _events.emit(UiEvent.Message(result.message))
        }
    }

    fun setResetToken(token: String?) {
        _state.update { it.copy(resetToken = token) }
        if (token != null) {
            _events.tryEmit(UiEvent.Navigate("login"))
        }
    }

    fun apiResetPassword(password: String, confirm: String) = launchBusy {
        val token = _state.value.resetToken ?: return@launchBusy
        when (val result = repository.resetPassword(token, password, confirm)) {
            is ApiResult.Success -> {
                _state.update { it.copy(resetToken = null) }
                _events.emit(UiEvent.Message(result.message))
            }
            is ApiResult.Error -> _events.emit(UiEvent.Message(result.message))
        }
    }

    fun logout() = launchBusy {
        repository.logout()
        _state.value = AppUiState(sessionReady = true, categories = _state.value.categories, gems = _state.value.gems)
        _events.emit(UiEvent.Message("Signed out successfully."))
        _events.emit(UiEvent.Navigate("home"))
    }

    fun updateFilters(query: String, city: String, vibe: String, budget: String, activity: String, sort: String) {
        _state.update { it.copy(query = query, city = city, vibe = vibe, budget = budget, activity = activity, sort = sort) }
    }

    fun loadGems() = launchBusy {
        val s = _state.value
        when (val result = repository.gems(s.query.ifBlank { null }, s.city.ifBlank { null }, s.vibe.ifBlank { null }, s.budget.ifBlank { null }, s.activity.ifBlank { null }, s.sort)) {
            is ApiResult.Success -> _state.update { it.copy(gems = result.value) }
            is ApiResult.Error -> _events.emit(UiEvent.Message(result.message))
        }
    }

    fun clearFilters() {
        _state.update { it.copy(query = "", city = "", vibe = "", budget = "", activity = "", sort = "recommended") }
        loadGems()
    }

    fun randomGem() = launchBusy {
        when (val result = repository.randomGem()) {
            is ApiResult.Success -> _events.emit(UiEvent.Navigate("gem/${result.value.id}"))
            is ApiResult.Error -> _events.emit(UiEvent.Message(result.message))
        }
    }

    fun loadGem(id: Int) = launchBusy {
        when (val result = repository.gem(id)) {
            is ApiResult.Success -> _state.update { it.copy(selectedGem = result.value) }
            is ApiResult.Error -> _events.emit(UiEvent.Message(result.message))
        }
    }

    fun toggleLike(id: Int) {
        requireAuthThen {
            viewModelScope.launch {
                when (val result = repository.toggleLike(id)) {
                    is ApiResult.Success -> {
                        _state.update { s ->
                            val selected = s.selectedGem
                            s.copy(
                                selectedGem = if (selected?.id == id) selected.copy(
                                    isLiked = result.value.isLiked ?: selected.isLiked,
                                    likeCount = result.value.likeCount ?: selected.likeCount,
                                ) else selected,
                                gems = s.gems.map { gem -> if (gem.id == id) gem.copy(isLiked = result.value.isLiked ?: gem.isLiked, likeCount = result.value.likeCount ?: gem.likeCount) else gem },
                                savedGems = s.savedGems.map { gem -> if (gem.id == id) gem.copy(isLiked = result.value.isLiked ?: gem.isLiked, likeCount = result.value.likeCount ?: gem.likeCount) else gem },
                            )
                        }
                    }
                    is ApiResult.Error -> _events.emit(UiEvent.Message(result.message))
                }
            }
        }
    }

    fun toggleSave(id: Int) {
        requireAuthThen {
            viewModelScope.launch {
                when (val result = repository.toggleSave(id)) {
                    is ApiResult.Success -> {
                        _state.update { s ->
                            val selected = s.selectedGem
                            val nowSaved = result.value.isSaved
                            val updatedSaved = s.savedGems.map { gem ->
                                if (gem.id == id) gem.copy(isSaved = nowSaved ?: gem.isSaved, saveCount = result.value.saveCount ?: gem.saveCount) else gem
                            }.let { list -> if (nowSaved == false) list.filterNot { it.id == id } else list }
                            s.copy(
                                selectedGem = if (selected?.id == id) selected.copy(
                                    isSaved = nowSaved ?: selected.isSaved,
                                    saveCount = result.value.saveCount ?: selected.saveCount,
                                ) else selected,
                                gems = s.gems.map { gem -> if (gem.id == id) gem.copy(isSaved = nowSaved ?: gem.isSaved, saveCount = result.value.saveCount ?: gem.saveCount) else gem },
                                savedGems = updatedSaved,
                            )
                        }
                        _events.emit(UiEvent.Message(result.message))
                    }
                    is ApiResult.Error -> _events.emit(UiEvent.Message(result.message))
                }
            }
        }
    }

    fun submitReview(gemId: Int, rating: Int, comment: String, vibes: List<String>) = launchBusy {
        when (val result = repository.createReview(gemId, rating, comment, vibes)) {
            is ApiResult.Success -> {
                _events.emit(UiEvent.Message(result.message))
                loadGem(gemId)
                _events.emit(UiEvent.Navigate("gem/$gemId"))
            }
            is ApiResult.Error -> _events.emit(UiEvent.Message(result.message))
        }
    }

    fun updateReview(reviewId: Int, rating: Int, comment: String, vibes: List<String>) = launchBusy {
        when (val result = repository.updateReview(reviewId, rating, comment, vibes)) {
            is ApiResult.Success -> {
                _state.update { s ->
                    s.copy(
                        profileReviews = s.profileReviews.map { if (it.id == reviewId) result.value else it },
                        selectedGem = if (s.selectedGem?.reviews?.any { it.id == reviewId } == true) {
                            s.selectedGem.copy(reviews = s.selectedGem.reviews.map { if (it.id == reviewId) result.value else it })
                        } else s.selectedGem
                    )
                }
                _events.emit(UiEvent.Message(result.message))
                _events.emit(UiEvent.Navigate("profile"))
            }
            is ApiResult.Error -> _events.emit(UiEvent.Message(result.message))
        }
    }

    fun deleteReview(reviewId: Int) = launchBusy {
        when (val result = repository.deleteReview(reviewId)) {
            is ApiResult.Success -> {
                _state.update { s ->
                    s.copy(
                        profileReviews = s.profileReviews.filterNot { it.id == reviewId },
                        selectedGem = s.selectedGem?.copy(reviews = s.selectedGem.reviews.filterNot { it.id == reviewId })
                    )
                }
                _events.emit(UiEvent.Message(result.message))
            }
            is ApiResult.Error -> _events.emit(UiEvent.Message(result.message))
        }
    }

    fun toggleReviewLike(reviewId: Int) {
        requireAuthThen {
            viewModelScope.launch {
                when (val result = repository.toggleReviewLike(reviewId)) {
                    is ApiResult.Success -> _state.update { s ->
                        val selected = s.selectedGem
                        s.copy(selectedGem = selected?.copy(reviews = selected.reviews.map { review ->
                            if (review.id == reviewId) review.copy(
                                isLiked = result.value.isLiked ?: review.isLiked,
                                likeCount = result.value.likeCount ?: review.likeCount,
                            ) else review
                        }))
                    }
                    is ApiResult.Error -> _events.emit(UiEvent.Message(result.message))
                }
            }
        }
    }

    fun loadSaved() {
        requireAuthThen {
            viewModelScope.launch {
                _state.update { it.copy(busy = true) }
                when (val result = repository.saved()) {
                    is ApiResult.Success -> _state.update { it.copy(savedGems = result.value) }
                    is ApiResult.Error -> _events.emit(UiEvent.Message(result.message))
                }
                _state.update { it.copy(busy = false) }
            }
        }
    }

    fun saveNote(gemId: Int, note: String) = launchBusy {
        when (val result = repository.saveNote(gemId, note)) {
            is ApiResult.Success -> {
                _state.update { s ->
                    s.copy(
                        savedGems = s.savedGems.map { if (it.id == gemId) it.copy(personalNote = note) else it },
                        selectedGem = if (s.selectedGem?.id == gemId) s.selectedGem.copy(personalNote = note) else s.selectedGem,
                        gems = s.gems.map { if (it.id == gemId) it.copy(personalNote = note) else it }
                    )
                }
                _events.emit(UiEvent.Message(result.message))
            }
            is ApiResult.Error -> _events.emit(UiEvent.Message(result.message))
        }
    }

    fun shareGem(draft: GemDraft, images: List<Uri>) = launchBusy {
        when (val result = repository.createGem(draft, images)) {
            is ApiResult.Success -> {
                _events.emit(UiEvent.Message(result.message.ifBlank { "Your Mzansi Gem was submitted!" }))
                loadProfile()
                _events.emit(UiEvent.Navigate("profile"))
            }
            is ApiResult.Error -> _events.emit(UiEvent.Message(result.message))
        }
    }

    fun updateGem(id: Int, draft: GemDraft, images: List<Uri>) = launchBusy {
        when (val result = repository.updateGem(id, draft, images)) {
            is ApiResult.Success -> {
                _state.update { s ->
                    s.copy(
                        profileGems = s.profileGems.map { if (it.id == id) result.value else it },
                        gems = s.gems.map { if (it.id == id) result.value else it },
                        selectedGem = result.value
                    )
                }
                _events.emit(UiEvent.Message(result.message))
                _events.emit(UiEvent.Navigate("profile"))
            }
            is ApiResult.Error -> _events.emit(UiEvent.Message(result.message))
        }
    }

    fun deleteGem(id: Int) = launchBusy {
        when (val result = repository.deleteGem(id)) {
            is ApiResult.Success -> {
                _state.update { s -> s.copy(profileGems = s.profileGems.filterNot { it.id == id }, gems = s.gems.filterNot { it.id == id }) }
                _events.emit(UiEvent.Message(result.message))
            }
            is ApiResult.Error -> _events.emit(UiEvent.Message(result.message))
        }
    }

    fun loadProfile() {
        requireAuthThen {
            viewModelScope.launch {
                _state.update { it.copy(busy = true) }
                val profile = repository.profile()
                val gems = repository.profileGems()
                val reviews = repository.profileReviews()
                val saved = repository.saved()
                if (profile is ApiResult.Success) _state.update { it.copy(profile = profile.value, currentUser = profile.value) }
                if (gems is ApiResult.Success) _state.update { it.copy(profileGems = gems.value) }
                if (reviews is ApiResult.Success) _state.update { it.copy(profileReviews = reviews.value) }
                if (saved is ApiResult.Success) _state.update { it.copy(savedGems = saved.value) }
                if (profile is ApiResult.Error) _events.emit(UiEvent.Message(profile.message))
                _state.update { it.copy(busy = false) }
            }
        }
    }

    fun updateProfile(name: String, email: String, city: String, bio: String) = launchBusy {
        when (val result = repository.updateProfile(name, email, city, bio)) {
            is ApiResult.Success -> {
                _state.update { it.copy(currentUser = result.value, profile = result.value) }
                _events.emit(UiEvent.Message(result.message))
            }
            is ApiResult.Error -> _events.emit(UiEvent.Message(result.message))
        }
    }

    fun updatePreferences(preferences: Preferences) = launchBusy {
        when (val result = repository.updatePreferences(preferences)) {
            is ApiResult.Success -> {
                _state.update { it.copy(currentUser = result.value, profile = result.value) }
                _events.emit(UiEvent.Message(result.message))
            }
            is ApiResult.Error -> _events.emit(UiEvent.Message(result.message))
        }
    }

    fun changePassword(current: String, password: String, confirm: String) = launchBusy {
        when (val result = repository.changePassword(current, password, confirm)) {
            is ApiResult.Success -> _events.emit(UiEvent.Message(result.message))
            is ApiResult.Error -> _events.emit(UiEvent.Message(result.message))
        }
    }

    fun deleteAccount(password: String) = launchBusy {
        when (val result = repository.deleteAccount(password)) {
            is ApiResult.Success -> {
                session.clear()
                _state.value = AppUiState(sessionReady = true, categories = _state.value.categories, gems = _state.value.gems)
                _events.emit(UiEvent.Message(result.message.ifBlank { "Account deleted." }))
                _events.emit(UiEvent.Navigate("home"))
            }
            is ApiResult.Error -> _events.emit(UiEvent.Message(result.message))
        }
    }

    fun reportGem(id: Int, type: String, details: String) = launchBusy {
        when (val result = repository.reportGem(id, type, details)) {
            is ApiResult.Success -> _events.emit(UiEvent.Message(result.message))
            is ApiResult.Error -> _events.emit(UiEvent.Message(result.message))
        }
    }

    fun reportReview(id: Int, type: String, details: String) = launchBusy {
        when (val result = repository.reportReview(id, type, details)) {
            is ApiResult.Success -> _events.emit(UiEvent.Message(result.message))
            is ApiResult.Error -> _events.emit(UiEvent.Message(result.message))
        }
    }


    fun loadAdmin() {
        requireAuthThen {
            viewModelScope.launch {
                if (_state.value.currentUser?.role != "admin") {
                    _events.emit(UiEvent.Message("Administrator access is required."))
                    _events.emit(UiEvent.Navigate("profile"))
                    return@launch
                }
                _state.update { it.copy(busy = true) }
                val dashboard = repository.adminDashboard()
                val moderation = repository.adminModeration()
                val users = repository.adminUsers()
                val analytics = repository.adminAnalytics()
                val settings = repository.adminSettings()
                if (dashboard is ApiResult.Success) _state.update { it.copy(adminDashboard = dashboard.value) }
                if (moderation is ApiResult.Success) _state.update { it.copy(adminModeration = moderation.value) }
                if (users is ApiResult.Success) _state.update { it.copy(adminUsers = users.value) }
                if (analytics is ApiResult.Success) _state.update { it.copy(adminAnalytics = analytics.value) }
                if (settings is ApiResult.Success) {
                    val name = settings.value["site_name"]
                    if (!name.isNullOrBlank()) session.saveSiteName(name)
                    _state.update { it.copy(adminSettings = settings.value) }
                }
                listOf(dashboard, moderation, users, analytics, settings).filterIsInstance<ApiResult.Error>().firstOrNull()?.let {
                    _events.emit(UiEvent.Message(it.message))
                }
                _state.update { it.copy(busy = false) }
            }
        }
    }

    fun moderateGem(id: Int, status: String, reason: String) = launchBusy {
        when (val result = repository.moderateGem(id, status, reason)) {
            is ApiResult.Success -> { _events.emit(UiEvent.Message(result.message)); loadAdmin() }
            is ApiResult.Error -> _events.emit(UiEvent.Message(result.message))
        }
    }

    fun moderateReview(id: Int, status: String) = launchBusy {
        when (val result = repository.moderateReview(id, status)) {
            is ApiResult.Success -> { _events.emit(UiEvent.Message(result.message)); loadAdmin() }
            is ApiResult.Error -> _events.emit(UiEvent.Message(result.message))
        }
    }

    fun resolveReport(id: Int, status: String, note: String) = launchBusy {
        when (val result = repository.resolveReport(id, status, note)) {
            is ApiResult.Success -> { _events.emit(UiEvent.Message(result.message)); loadAdmin() }
            is ApiResult.Error -> _events.emit(UiEvent.Message(result.message))
        }
    }

    fun updateAdminSettings(settings: Map<String, String>) = launchBusy {
        when (val result = repository.updateAdminSettings(settings)) {
            is ApiResult.Success -> {
                val name = result.value["site_name"]
                if (!name.isNullOrBlank()) session.saveSiteName(name)
                _state.update { it.copy(adminSettings = result.value) }
                _events.emit(UiEvent.Message(result.message.ifBlank { "Platform settings saved." }))
            }
            is ApiResult.Error -> _events.emit(UiEvent.Message(result.message))
        }
    }

    fun updateAdminUser(id: Int, role: String, status: String) = launchBusy {
        when (val result = repository.updateAdminUser(id, role, status)) {
            is ApiResult.Success -> {
                _state.update { s -> s.copy(adminUsers = s.adminUsers.map { if (it.id == id) result.value else it }) }
                _events.emit(UiEvent.Message(result.message))
            }
            is ApiResult.Error -> _events.emit(UiEvent.Message(result.message))
        }
    }

    fun dismissVerificationNotice() {
        _state.update { it.copy(registrationRequiresVerification = false) }
    }

    private fun requireAuthThen(action: () -> Unit) {
        if (_state.value.currentUser == null) {
            _events.tryEmit(UiEvent.Message("Please sign in to continue."))
            _events.tryEmit(UiEvent.Navigate("login"))
        } else action()
    }

    private fun launchBusy(block: suspend () -> Unit) {
        viewModelScope.launch {
            _state.update { it.copy(busy = true) }
            try { block() } finally { _state.update { it.copy(busy = false) } }
        }
    }

    companion object {
        fun factory(application: HiddenGemsApplication): ViewModelProvider.Factory = object : ViewModelProvider.Factory {
            @Suppress("UNCHECKED_CAST")
            override fun <T : ViewModel> create(modelClass: Class<T>): T = AppViewModel(
                application,
                application.apiClient.repository,
                application.apiClient.session,
            ) as T
        }
    }
}
