package za.co.hiddengems.app.auth

import android.app.Activity
import androidx.credentials.CredentialManager
import androidx.credentials.CustomCredential
import androidx.credentials.GetCredentialRequest
import com.google.android.libraries.identity.googleid.GetGoogleIdOption
import com.google.android.libraries.identity.googleid.GoogleIdTokenCredential
import com.google.firebase.FirebaseApp
import com.google.firebase.FirebaseOptions
import com.google.firebase.auth.FirebaseAuth
import com.google.firebase.auth.GoogleAuthProvider
import kotlinx.coroutines.tasks.await
import za.co.hiddengems.app.BuildConfig

class FirebaseGoogleSignIn(private val activity: Activity) {
    suspend fun signIn(): Result<String> = runCatching {
        val app = ensureFirebaseApp()
        val webClientId = BuildConfig.GOOGLE_WEB_CLIENT_ID.trim()
        require(webClientId.isNotEmpty()) { "Google SSO is not configured yet." }

        val googleIdOption = GetGoogleIdOption.Builder()
            .setServerClientId(webClientId)
            .setFilterByAuthorizedAccounts(false)
            .setAutoSelectEnabled(false)
            .build()

        val request = GetCredentialRequest.Builder()
            .addCredentialOption(googleIdOption)
            .build()

        val result = CredentialManager.create(activity).getCredential(activity, request)
        val credential = result.credential
        require(
            credential is CustomCredential &&
                credential.type == GoogleIdTokenCredential.TYPE_GOOGLE_ID_TOKEN_CREDENTIAL
        ) { "Google did not return a valid credential." }

        val googleId = GoogleIdTokenCredential.createFrom(credential.data)
        val firebaseCredential = GoogleAuthProvider.getCredential(googleId.idToken, null)
        val auth = FirebaseAuth.getInstance(app)
        val authResult = auth.signInWithCredential(firebaseCredential).await()
        val user = authResult.user ?: error("Firebase did not return a user.")
        val idToken = user.getIdToken(true).await().token ?: error("Firebase did not return an ID token.")
        auth.signOut()
        idToken
    }

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
