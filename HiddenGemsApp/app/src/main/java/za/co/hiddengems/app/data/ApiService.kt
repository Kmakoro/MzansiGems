package za.co.hiddengems.app.data

import okhttp3.MultipartBody
import okhttp3.RequestBody
import retrofit2.Response
import retrofit2.http.Body
import retrofit2.http.DELETE
import retrofit2.http.GET
import retrofit2.http.HTTP
import retrofit2.http.Multipart
import retrofit2.http.PATCH
import retrofit2.http.POST
import retrofit2.http.Part
import retrofit2.http.Path
import retrofit2.http.Query

interface HiddenGemsApi {
    @GET("health")
    suspend fun health(): Response<ApiEnvelope<Map<String, String>>>

    @POST("auth/login")
    suspend fun login(@Body request: LoginRequest): Response<ApiEnvelope<AuthPayload>>

    @POST("auth/register")
    suspend fun register(@Body request: RegisterRequest): Response<ApiEnvelope<AuthPayload>>

    @POST("auth/firebase")
    suspend fun firebaseLogin(@Body request: FirebaseLoginRequest): Response<ApiEnvelope<AuthPayload>>

    @POST("auth/forgot-password")
    suspend fun forgotPassword(@Body request: Map<String, String>): Response<ApiEnvelope<Unit>>

    @POST("auth/logout")
    suspend fun logout(): Response<ApiEnvelope<Unit>>

    @GET("auth/me")
    suspend fun me(): Response<ApiEnvelope<User>>

    @POST("auth/verify-email")
    suspend fun verifyEmail(@Body request: Map<String, String>): Response<ApiEnvelope<Unit>>

    @POST("auth/reset-password")
    suspend fun resetPassword(@Body request: Map<String, String>): Response<ApiEnvelope<Unit>>

    @GET("categories")
    suspend fun categories(): Response<ApiEnvelope<Categories>>

    @GET("gems")
    suspend fun gems(
        @Query("q") query: String? = null,
        @Query("city") city: String? = null,
        @Query("vibe") vibe: String? = null,
        @Query("budget") budget: String? = null,
        @Query("activity") activity: String? = null,
        @Query("sort") sort: String = "recommended",
        @Query("page") page: Int = 1,
        @Query("per_page") perPage: Int = 30,
    ): Response<ApiEnvelope<List<Gem>>>

    @GET("gems/random")
    suspend fun randomGem(): Response<ApiEnvelope<Gem>>

    @GET("gems/{id}")
    suspend fun gem(@Path("id") id: Int): Response<ApiEnvelope<Gem>>

    @Multipart
    @POST("gems")
    suspend fun createGem(
        @Part("title") title: RequestBody,
        @Part("description") description: RequestBody,
        @Part("location") location: RequestBody,
        @Part("city") city: RequestBody,
        @Part("vibes") vibes: RequestBody,
        @Part("budget_level") budgetLevel: RequestBody,
        @Part("activity_type") activityType: RequestBody,
        @Part("operating_hours") operatingHours: RequestBody,
        @Part("rating") rating: RequestBody,
        @Part images: List<MultipartBody.Part>,
    ): Response<ApiEnvelope<Gem>>

    @Multipart
    @POST("gems/{id}")
    suspend fun updateGem(
        @Path("id") id: Int,
        @Part("title") title: RequestBody,
        @Part("description") description: RequestBody,
        @Part("location") location: RequestBody,
        @Part("city") city: RequestBody,
        @Part("vibes") vibes: RequestBody,
        @Part("budget_level") budgetLevel: RequestBody,
        @Part("activity_type") activityType: RequestBody,
        @Part("operating_hours") operatingHours: RequestBody,
        @Part images: List<MultipartBody.Part>,
    ): Response<ApiEnvelope<Gem>>

    @DELETE("gems/{id}")
    suspend fun deleteGem(@Path("id") id: Int): Response<ApiEnvelope<Unit>>

    @POST("gems/{id}/like")
    suspend fun toggleLike(@Path("id") id: Int): Response<ApiEnvelope<TogglePayload>>

    @POST("gems/{id}/save")
    suspend fun toggleSave(@Path("id") id: Int): Response<ApiEnvelope<TogglePayload>>

    @PATCH("gems/{id}/note")
    suspend fun saveNote(@Path("id") id: Int, @Body request: NoteRequest): Response<ApiEnvelope<NotePayload>>

    @POST("gems/{id}/reviews")
    suspend fun createReview(@Path("id") id: Int, @Body request: ReviewRequest): Response<ApiEnvelope<Review>>

    @PATCH("reviews/{id}")
    suspend fun updateReview(@Path("id") id: Int, @Body request: ReviewRequest): Response<ApiEnvelope<Review>>

    @DELETE("reviews/{id}")
    suspend fun deleteReview(@Path("id") id: Int): Response<ApiEnvelope<Unit>>

    @POST("reviews/{id}/like")
    suspend fun toggleReviewLike(@Path("id") id: Int): Response<ApiEnvelope<TogglePayload>>

    @GET("saved")
    suspend fun saved(): Response<ApiEnvelope<List<Gem>>>

    @GET("profile")
    suspend fun profile(): Response<ApiEnvelope<User>>

    @PATCH("profile")
    suspend fun updateProfile(@Body request: ProfileRequest): Response<ApiEnvelope<User>>

    @PATCH("profile/settings")
    suspend fun updatePreferences(@Body request: PreferencesRequest): Response<ApiEnvelope<User>>

    @PATCH("profile/password")
    suspend fun changePassword(@Body request: PasswordRequest): Response<ApiEnvelope<Unit>>

    @HTTP(method = "DELETE", path = "profile", hasBody = true)
    suspend fun deleteAccount(@Body request: DeleteAccountRequest): Response<ApiEnvelope<Unit>>

    @GET("profile/gems")
    suspend fun profileGems(): Response<ApiEnvelope<List<Gem>>>

    @GET("profile/reviews")
    suspend fun profileReviews(): Response<ApiEnvelope<List<Review>>>

    @POST("reports")
    suspend fun report(@Body request: ReportRequest): Response<ApiEnvelope<Unit>>

    @GET("admin/dashboard")
    suspend fun adminDashboard(): Response<ApiEnvelope<AdminDashboard>>

    @GET("admin/users")
    suspend fun adminUsers(
        @Query("q") query: String? = null,
        @Query("role") role: String? = null,
        @Query("status") status: String? = null,
    ): Response<ApiEnvelope<List<User>>>

    @PATCH("admin/users/{id}")
    suspend fun updateAdminUser(@Path("id") id: Int, @Body request: AdminUserRequest): Response<ApiEnvelope<User>>

    @GET("admin/moderation")
    suspend fun adminModeration(): Response<ApiEnvelope<ModerationPayload>>

    @PATCH("admin/gems/{id}")
    suspend fun moderateGem(@Path("id") id: Int, @Body request: ModerationRequest): Response<ApiEnvelope<Gem>>

    @PATCH("admin/reviews/{id}")
    suspend fun moderateReview(@Path("id") id: Int, @Body request: ModerationRequest): Response<ApiEnvelope<Unit>>

    @PATCH("admin/reports/{id}")
    suspend fun resolveReport(@Path("id") id: Int, @Body request: ReportResolutionRequest): Response<ApiEnvelope<Unit>>

    @GET("admin/analytics")
    suspend fun adminAnalytics(): Response<ApiEnvelope<AdminAnalytics>>

    @GET("admin/settings")
    suspend fun adminSettings(): Response<ApiEnvelope<Map<String, String>>>

    @PATCH("admin/settings")
    suspend fun updateAdminSettings(@Body settings: Map<String, String>): Response<ApiEnvelope<Map<String, String>>>
}
