package za.co.hiddengems.app.auth

import android.app.Activity
import android.content.Intent
import android.util.Base64
import androidx.credentials.CredentialManager
import androidx.credentials.CustomCredential
import androidx.credentials.exceptions.GetCredentialException
import androidx.credentials.GetCredentialRequest
import com.google.android.gms.auth.api.signin.GoogleSignIn
import com.google.android.gms.auth.api.signin.GoogleSignInOptions
import com.google.android.libraries.identity.googleid.GetSignInWithGoogleOption
import com.google.android.libraries.identity.googleid.GoogleIdTokenCredential
import com.google.firebase.FirebaseApp
import com.google.firebase.FirebaseOptions
import com.google.firebase.auth.FirebaseAuth
import com.google.firebase.auth.GoogleAuthProvider
import com.google.firebase.auth.UserProfileChangeRequest
import org.json.JSONObject
import kotlinx.coroutines.tasks.await
import za.co.hiddengems.app.BuildConfig

class GoogleAccountReauthException(cause: Throwable) :
    IllegalStateException("Google could not reauthenticate this account.", cause)

data class GoogleLoginIdentity(
    val firebaseIdToken: String,
    val googleDisplayName: String?,
)

class FirebaseGoogleSignIn(private val activity: Activity) {
    suspend fun signIn(): Result<GoogleLoginIdentity> = runCatching {
        ensureFirebaseApp()
        val webClientId = webClientId()
        val request = GetCredentialRequest.Builder()
            .addCredentialOption(GetSignInWithGoogleOption.Builder(webClientId).build())
            .build()

        val result = try {
            CredentialManager.create(activity).getCredential(activity, request)
        } catch (error: GetCredentialException) {
            // Some Play services builds report a false cancellation after account selection.
            // Only offer the alternate flow for this specific reauthentication error.
            if (error.message?.contains("Account reauth failed", ignoreCase = true) == true) {
                throw GoogleAccountReauthException(error)
            }
            throw error
        }

        val credential = result.credential
        require(
            credential is CustomCredential &&
                credential.type == GoogleIdTokenCredential.TYPE_GOOGLE_ID_TOKEN_CREDENTIAL
        ) { "Google did not return a valid credential." }

        val googleId = GoogleIdTokenCredential.createFrom(credential.data)
        exchangeGoogleIdToken(googleId.idToken, googleId.displayName)
    }

    // Temporary compatibility fallback for Credential Manager's [16] reauth failure.
    // GoogleSignInClient is deprecated; remove this when the upstream issue is resolved.
    @Suppress("DEPRECATION")
    fun alternateSignInIntent(): Intent {
        val options = GoogleSignInOptions.Builder(GoogleSignInOptions.DEFAULT_SIGN_IN)
            .requestIdToken(webClientId())
            .requestEmail()
            .build()
        return GoogleSignIn.getClient(activity, options).signInIntent
    }

    @Suppress("DEPRECATION")
    suspend fun finishAlternateSignIn(intent: Intent?): Result<GoogleLoginIdentity> = runCatching {
        val account = GoogleSignIn.getSignedInAccountFromIntent(intent).await()
        val idToken = account.idToken ?: error("Google did not provide an ID token.")
        exchangeGoogleIdToken(idToken, account.displayName)
    }

    private fun webClientId(): String {
        val id = BuildConfig.GOOGLE_WEB_CLIENT_ID.trim()
        require(id.isNotEmpty() && id.endsWith(".apps.googleusercontent.com")) {
            "Google Web Client ID is not configured correctly."
        }
        return id
    }

    private suspend fun exchangeGoogleIdToken(googleIdToken: String, googleDisplayName: String?): GoogleLoginIdentity {
        val app = ensureFirebaseApp()
        val auth = FirebaseAuth.getInstance(app)
        val authResult = auth.signInWithCredential(
            GoogleAuthProvider.getCredential(googleIdToken, null)
        ).await()
        val user = authResult.user ?: error("Firebase did not return a user.")

        // Google's account chooser can return the verified Google display name
        // even when Firebase creates a profile with a blank displayName.
        // Populate only missing/placeholder Firebase names so our PHP API can
        // repair the existing "Google User" database record on the next login.
        // Never replace a customized Firebase profile name.
        val providerName = user.providerData
            .firstOrNull { it.providerId == "google.com" }?.displayName
        val name = listOfNotNull(
            googleDisplayName,
            providerName,
            user.displayName,
            nameClaimFromGoogleIdToken(googleIdToken),
        ).map { it.trim() }.firstOrNull {
            it.length in 2..100 && it != "Google User"
        }
        if (name != null && (user.displayName.isNullOrBlank() || user.displayName == "Google User")) {
            // Name synchronization is best-effort; authentication must still work
            // if Firebase rejects this optional profile update.
            runCatching {
                user.updateProfile(
                    UserProfileChangeRequest.Builder().setDisplayName(name).build()
                ).await()
            }
        }

        val firebaseIdToken = user.getIdToken(true).await().token
            ?: error("Firebase did not return an ID token.")
        auth.signOut()
        return GoogleLoginIdentity(firebaseIdToken, name)
    }

    // Google's signed ID token may carry the profile name even when the
    // Google account chooser and Firebase user objects omit displayName.
    // The token is accepted only after signInWithCredential verifies it.
    private fun nameClaimFromGoogleIdToken(googleIdToken: String): String? = runCatching {
        val encodedPayload = googleIdToken.split('.').getOrNull(1) ?: return@runCatching null
        val payload = JSONObject(
            String(Base64.decode(encodedPayload, Base64.URL_SAFE or Base64.NO_WRAP))
        )
        val name = payload.optString("name").trim()
        if (name.isNotBlank()) name else listOf(
            payload.optString("given_name").trim(),
            payload.optString("family_name").trim(),
        ).filter { it.isNotBlank() }.joinToString(" ").ifBlank { null }
    }.getOrNull()

    private fun ensureFirebaseApp(): FirebaseApp {
        FirebaseApp.getApps(activity).firstOrNull()?.let { return it }

        val apiKey = BuildConfig.FIREBASE_API_KEY.trim()
        val appId = BuildConfig.FIREBASE_APP_ID.trim()
        val projectId = BuildConfig.FIREBASE_PROJECT_ID.trim()
        require(apiKey.isNotEmpty() && appId.isNotEmpty() && projectId.isNotEmpty()) {
            "Firebase is not configured. Add values to HiddenGemsApp/firebase.properties."
        }

        val options = FirebaseOptions.Builder()
            .setApiKey(apiKey)
            .setApplicationId(appId)
            .setProjectId(projectId)
            .build()

        return FirebaseApp.initializeApp(activity.applicationContext, options)
    }
}
