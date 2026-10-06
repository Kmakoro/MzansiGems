package za.co.hiddengems.app.data

import com.google.gson.annotations.SerializedName

data class ApiEnvelope<T>(
    val success: Boolean = false,
    val message: String = "",
    val data: T? = null,
    val meta: PageMeta? = null,
    val errors: Map<String, String>? = null,
)

data class PageMeta(
    val page: Int = 1,
    @SerializedName("per_page") val perPage: Int = 12,
    val total: Int = 0,
    @SerializedName("last_page") val lastPage: Int = 1,
)

data class AuthPayload(
    val token: String,
    @SerializedName("token_type") val tokenType: String,
    val user: User,
)

data class LoginRequest(
    val email: String,
    val password: String,
    @SerializedName("device_name") val deviceName: String = "Mzansi Gem Android",
)

data class RegisterRequest(
    @SerializedName("full_name") val fullName: String,
    val email: String,
    val city: String,
    val password: String,
    @SerializedName("confirm_password") val confirmPassword: String,
    @SerializedName("device_name") val deviceName: String = "Mzansi Gem Android",
)

data class FirebaseLoginRequest(
    @SerializedName("id_token") val idToken: String,
    @SerializedName("device_name") val deviceName: String = "Mzansi Gem Android - Google",
)

data class Preferences(
    @SerializedName("notify_new_gems") val notifyNewGems: Boolean = true,
    @SerializedName("notify_comments") val notifyComments: Boolean = true,
    @SerializedName("notify_likes_saves") val notifyLikesSaves: Boolean = true,
    @SerializedName("personalized_recommendations") val personalizedRecommendations: Boolean = true,
    @SerializedName("show_saved_gems") val showSavedGems: Boolean = true,
    @SerializedName("show_activity_status") val showActivityStatus: Boolean = true,
)

data class ProfileStats(
    val posted: Int = 0,
    val saved: Int = 0,
    val reviews: Int = 0,
)

data class User(
    val id: Int,
    @SerializedName("full_name") val fullName: String,
    val email: String,
    val city: String,
    val bio: String = "",
    val role: String = "user",
    val status: String = "active",
    val level: Int = 1,
    val points: Int = 0,
    @SerializedName("email_verified") val emailVerified: Boolean = false,
    val preferences: Preferences = Preferences(),
    val stats: ProfileStats? = null,
    @SerializedName("created_at") val createdAt: String = "",
    @SerializedName("updated_at") val updatedAt: String = "",
)

data class Gem(
    val id: Int,
    @SerializedName("user_id") val userId: Int,
    val title: String,
    val slug: String = "",
    val description: String,
    val location: String,
    val city: String,
    val vibes: List<String> = emptyList(),
    @SerializedName("budget_level") val budgetLevel: String,
    @SerializedName("activity_type") val activityType: String,
    @SerializedName("operating_hours") val operatingHours: String = "All Day",
    @SerializedName("cover_image") val coverImage: String? = null,
    @SerializedName("average_rating") val averageRating: Double = 0.0,
    @SerializedName("review_count") val reviewCount: Int = 0,
    @SerializedName("like_count") val likeCount: Int = 0,
    @SerializedName("save_count") val saveCount: Int = 0,
    @SerializedName("is_liked") val isLiked: Boolean = false,
    @SerializedName("is_saved") val isSaved: Boolean = false,
    @SerializedName("personal_note") val personalNote: String = "",
    val status: String = "approved",
    val images: List<String> = emptyList(),
    val reviews: List<Review> = emptyList(),
    @SerializedName("created_at") val createdAt: String = "",
    @SerializedName("updated_at") val updatedAt: String = "",
)

data class Review(
    val id: Int,
    @SerializedName("gem_id") val gemId: Int,
    @SerializedName("user_id") val userId: Int,
    @SerializedName("reviewer_name") val reviewerName: String = "",
    @SerializedName("gem_title") val gemTitle: String = "",
    val rating: Int,
    val comment: String = "",
    val vibes: List<String> = emptyList(),
    val status: String = "pending",
    @SerializedName("is_edited") val isEdited: Boolean = false,
    @SerializedName("like_count") val likeCount: Int = 0,
    @SerializedName("is_liked") val isLiked: Boolean = false,
    @SerializedName("created_at") val createdAt: String = "",
    @SerializedName("updated_at") val updatedAt: String = "",
)

data class Categories(
    val cities: List<String> = emptyList(),
    val vibes: List<String> = emptyList(),
    val budgets: List<String> = emptyList(),
    val activities: List<String> = emptyList(),
)

data class TogglePayload(
    @SerializedName("is_liked") val isLiked: Boolean? = null,
    @SerializedName("is_saved") val isSaved: Boolean? = null,
    @SerializedName("like_count") val likeCount: Int? = null,
    @SerializedName("save_count") val saveCount: Int? = null,
)

data class ReviewRequest(
    val rating: Int,
    val comment: String,
    val vibes: List<String> = emptyList(),
)

data class NoteRequest(@SerializedName("personal_note") val personalNote: String)

data class NotePayload(@SerializedName("personal_note") val personalNote: String)

data class ProfileRequest(
    @SerializedName("full_name") val fullName: String,
    val email: String,
    val city: String,
    val bio: String,
)

data class PreferencesRequest(
    @SerializedName("notify_new_gems") val notifyNewGems: Boolean,
    @SerializedName("notify_comments") val notifyComments: Boolean,
    @SerializedName("notify_likes_saves") val notifyLikesSaves: Boolean,
    @SerializedName("personalized_recommendations") val personalizedRecommendations: Boolean,
    @SerializedName("show_saved_gems") val showSavedGems: Boolean,
    @SerializedName("show_activity_status") val showActivityStatus: Boolean,
)

data class PasswordRequest(
    @SerializedName("current_password") val currentPassword: String,
    val password: String,
    @SerializedName("confirm_password") val confirmPassword: String,
)

data class ReportRequest(
    @SerializedName("gem_id") val gemId: Int? = null,
    @SerializedName("review_id") val reviewId: Int? = null,
    @SerializedName("violation_type") val violationType: String,
    val details: String = "",
)

data class GemDraft(
    val title: String,
    val description: String,
    val location: String,
    val city: String,
    val vibes: List<String>,
    val budgetLevel: String,
    val activityType: String,
    val operatingHours: String,
    val rating: Int,
)

sealed interface ApiResult<out T> {
    data class Success<T>(val value: T, val message: String = "") : ApiResult<T>
    data class Error(val message: String, val fieldErrors: Map<String, String> = emptyMap()) : ApiResult<Nothing>
}

data class AdminMetrics(
    @SerializedName("total_users") val totalUsers: Int = 0,
    @SerializedName("total_posts") val totalPosts: Int = 0,
    @SerializedName("active_reports") val activeReports: Int = 0,
    @SerializedName("pending_gems") val pendingGems: Int = 0,
    @SerializedName("pending_reviews") val pendingReviews: Int = 0,
)

data class ActivityItem(
    val id: Int = 0,
    @SerializedName("full_name") val fullName: String? = null,
    @SerializedName("action_type") val actionType: String = "",
    val description: String = "",
    @SerializedName("created_at") val createdAt: String = "",
)

data class AdminDashboard(
    val metrics: AdminMetrics = AdminMetrics(),
    @SerializedName("recent_activity") val recentActivity: List<ActivityItem> = emptyList(),
)

data class ContentReport(
    val id: Int,
    @SerializedName("gem_id") val gemId: Int? = null,
    @SerializedName("review_id") val reviewId: Int? = null,
    @SerializedName("violation_type") val violationType: String = "",
    val details: String? = null,
    val status: String = "pending",
    @SerializedName("reporter_name") val reporterName: String = "",
    @SerializedName("gem_title") val gemTitle: String? = null,
    @SerializedName("review_comment") val reviewComment: String? = null,
    @SerializedName("created_at") val createdAt: String = "",
)

data class ModerationPayload(
    @SerializedName("pending_gems") val pendingGems: List<Gem> = emptyList(),
    @SerializedName("pending_reviews") val pendingReviews: List<Review> = emptyList(),
    val reports: List<ContentReport> = emptyList(),
)

data class GrowthPoint(val month: String = "", val total: Int = 0)
data class CategoryPoint(val category: String = "", val posts: Int = 0)
data class Engagement(
    val posts: Int = 0,
    val reviews: Int = 0,
    val likes: Int = 0,
    val saves: Int = 0,
)
data class AdminAnalytics(
    val growth: List<GrowthPoint> = emptyList(),
    val categories: List<CategoryPoint> = emptyList(),
    val engagement: Engagement = Engagement(),
)

data class ModerationRequest(
    val status: String,
    @SerializedName("rejection_reason") val rejectionReason: String = "",
)
data class AdminUserRequest(val role: String, val status: String)
data class ReportResolutionRequest(
    val status: String,
    @SerializedName("resolution_note") val resolutionNote: String = "",
)

data class DeleteAccountRequest(val password: String)
