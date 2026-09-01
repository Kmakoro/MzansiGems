package za.co.hiddengems.app.data

import android.content.Context
import android.net.Uri
import com.google.gson.Gson
import com.google.gson.reflect.TypeToken
import okhttp3.MediaType.Companion.toMediaTypeOrNull
import okhttp3.MultipartBody
import okhttp3.RequestBody.Companion.toRequestBody
import retrofit2.Response

class HiddenGemsRepository(
    private val context: Context,
    private val api: HiddenGemsApi,
    private val session: SessionManager,
    private val gson: Gson,
) {
    private suspend fun <T> call(block: suspend () -> Response<ApiEnvelope<T>>): ApiResult<T> = try {
        val response = block()
        val body = response.body()
        if (response.isSuccessful && body?.success == true && body.data != null) {
            ApiResult.Success(body.data, body.message)
        } else if (response.isSuccessful && body?.success == true) {
            @Suppress("UNCHECKED_CAST")
            ApiResult.Success(Unit as T, body.message)
        } else {
            val parsed = response.errorBody()?.string()?.let { raw ->
                runCatching {
                    val type = object : TypeToken<ApiEnvelope<Any>>() {}.type
                    gson.fromJson<ApiEnvelope<Any>>(raw, type)
                }.getOrNull()
            }
            ApiResult.Error(parsed?.message ?: body?.message ?: "Request failed (${response.code()}).", parsed?.errors.orEmpty())
        }
    } catch (e: Exception) {
        ApiResult.Error(e.message ?: "Could not connect to the Mzansi Gem server.")
    }

    suspend fun login(email: String, password: String): ApiResult<User> = when (val result = call { api.login(LoginRequest(email, password)) }) {
        is ApiResult.Success -> {
            session.saveToken(result.value.token)
            ApiResult.Success(result.value.user, result.message)
        }
        is ApiResult.Error -> result
    }

    suspend fun register(name: String, email: String, city: String, password: String, confirm: String): ApiResult<User> =
        when (val result = call { api.register(RegisterRequest(name, email, city, password, confirm)) }) {
            is ApiResult.Success -> {
                session.saveToken(result.value.token)
                ApiResult.Success(result.value.user, result.message)
            }
            is ApiResult.Error -> result
        }

    suspend fun forgotPassword(email: String) = call { api.forgotPassword(mapOf("email" to email)) }

    suspend fun logout(): ApiResult<Unit> {
        val result = call { api.logout() }
        session.clear()
        return result
    }

    suspend fun me() = call { api.me() }
    suspend fun verifyEmail(token: String) = call { api.verifyEmail(mapOf("token" to token)) }
    suspend fun resetPassword(token: String, pass: String, confirm: String) =
        call { api.resetPassword(mapOf("token" to token, "password" to pass, "confirm_password" to confirm)) }
    suspend fun health() = call { api.health() }
    suspend fun categories() = call { api.categories() }
    suspend fun gems(query: String?, city: String?, vibe: String?, budget: String?, activity: String?, sort: String) =
        call { api.gems(query, city, vibe, budget, activity, sort) }
    suspend fun randomGem() = call { api.randomGem() }
    suspend fun gem(id: Int) = call { api.gem(id) }
    suspend fun toggleLike(id: Int) = call { api.toggleLike(id) }
    suspend fun toggleSave(id: Int) = call { api.toggleSave(id) }
    suspend fun saveNote(id: Int, note: String) = call { api.saveNote(id, NoteRequest(note)) }
    suspend fun saved() = call { api.saved() }
    suspend fun createReview(id: Int, rating: Int, comment: String, vibes: List<String>) =
        call { api.createReview(id, ReviewRequest(rating, comment, vibes)) }
    suspend fun updateReview(id: Int, rating: Int, comment: String, vibes: List<String>) =
        call { api.updateReview(id, ReviewRequest(rating, comment, vibes)) }
    suspend fun deleteReview(id: Int) = call { api.deleteReview(id) }
    suspend fun toggleReviewLike(id: Int) = call { api.toggleReviewLike(id) }
    suspend fun profile() = call { api.profile() }
    suspend fun profileGems() = call { api.profileGems() }
    suspend fun profileReviews() = call { api.profileReviews() }
    suspend fun updateProfile(name: String, email: String, city: String, bio: String) =
        call { api.updateProfile(ProfileRequest(name, email, city, bio)) }
    suspend fun updatePreferences(p: Preferences) = call {
        api.updatePreferences(
            PreferencesRequest(
                p.notifyNewGems,
                p.notifyComments,
                p.notifyLikesSaves,
                p.personalizedRecommendations,
                p.showSavedGems,
                p.showActivityStatus,
            )
        )
    }
    suspend fun changePassword(current: String, password: String, confirmation: String) =
        call { api.changePassword(PasswordRequest(current, password, confirmation)) }
    suspend fun deleteAccount(password: String) = call { api.deleteAccount(DeleteAccountRequest(password)) }
    suspend fun reportGem(id: Int, type: String, details: String) =
        call { api.report(ReportRequest(gemId = id, violationType = type, details = details)) }

    suspend fun reportReview(id: Int, type: String, details: String) =
        call { api.report(ReportRequest(reviewId = id, violationType = type, details = details)) }

    suspend fun adminDashboard() = call { api.adminDashboard() }
    suspend fun adminUsers() = call { api.adminUsers() }
    suspend fun adminModeration() = call { api.adminModeration() }
    suspend fun adminAnalytics() = call { api.adminAnalytics() }
    suspend fun adminSettings() = call { api.adminSettings() }
    suspend fun updateAdminSettings(settings: Map<String, String>) = call { api.updateAdminSettings(settings) }
    suspend fun moderateGem(id: Int, status: String, reason: String = "") =
        call { api.moderateGem(id, ModerationRequest(status, reason)) }
    suspend fun moderateReview(id: Int, status: String) =
        call { api.moderateReview(id, ModerationRequest(status)) }
    suspend fun resolveReport(id: Int, status: String, note: String = "") =
        call { api.resolveReport(id, ReportResolutionRequest(status, note)) }
    suspend fun updateAdminUser(id: Int, role: String, status: String) =
        call { api.updateAdminUser(id, AdminUserRequest(role, status)) }

    private fun imageParts(imageUris: List<Uri>): List<MultipartBody.Part> = imageUris.take(3).mapIndexedNotNull { index, uri ->
        val mime = context.contentResolver.getType(uri) ?: "image/jpeg"
        val bytes = context.contentResolver.openInputStream(uri)?.use { it.readBytes() } ?: return@mapIndexedNotNull null
        val body = bytes.toRequestBody(mime.toMediaTypeOrNull())
        val extension = when (mime) { "image/png" -> "png"; else -> "jpg" }
        MultipartBody.Part.createFormData("images[]", "hidden_gem_${index + 1}.$extension", body)
    }

    suspend fun createGem(draft: GemDraft, imageUris: List<Uri>): ApiResult<Gem> {
        val text = "text/plain".toMediaTypeOrNull()
        return call {
            api.createGem(
                draft.title.toRequestBody(text),
                draft.description.toRequestBody(text),
                draft.location.toRequestBody(text),
                draft.city.toRequestBody(text),
                draft.vibes.joinToString(",").toRequestBody(text),
                draft.budgetLevel.toRequestBody(text),
                draft.activityType.toRequestBody(text),
                draft.operatingHours.toRequestBody(text),
                draft.rating.toString().toRequestBody(text),
                imageParts(imageUris),
            )
        }
    }

    suspend fun updateGem(id: Int, draft: GemDraft, imageUris: List<Uri>): ApiResult<Gem> {
        val text = "text/plain".toMediaTypeOrNull()
        return call {
            api.updateGem(
                id,
                draft.title.toRequestBody(text),
                draft.description.toRequestBody(text),
                draft.location.toRequestBody(text),
                draft.city.toRequestBody(text),
                draft.vibes.joinToString(",").toRequestBody(text),
                draft.budgetLevel.toRequestBody(text),
                draft.activityType.toRequestBody(text),
                draft.operatingHours.toRequestBody(text),
                imageParts(imageUris),
            )
        }
    }

    suspend fun deleteGem(id: Int) = call { api.deleteGem(id) }
}
