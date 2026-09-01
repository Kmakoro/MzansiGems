package za.co.hiddengems.app.data

import android.content.Context
import androidx.datastore.preferences.core.edit
import androidx.datastore.preferences.core.stringPreferencesKey
import androidx.datastore.preferences.preferencesDataStore
import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.flow.first
import kotlinx.coroutines.flow.map

private val Context.sessionDataStore by preferencesDataStore(name = "hidden_gems_session")

class SessionManager(private val context: Context) {
    private val tokenKey = stringPreferencesKey("api_token")
    private val siteNameKey = stringPreferencesKey("site_name")

    val tokenFlow: Flow<String?> = context.sessionDataStore.data.map { it[tokenKey] }
    val siteNameFlow: Flow<String> = context.sessionDataStore.data.map { it[siteNameKey] ?: "Mzansi Gem" }

    suspend fun token(): String? = tokenFlow.first()
    suspend fun siteName(): String = siteNameFlow.first()

    suspend fun saveToken(token: String) {
        context.sessionDataStore.edit { it[tokenKey] = token }
    }

    suspend fun saveSiteName(name: String) {
        context.sessionDataStore.edit { it[siteNameKey] = name }
    }

    suspend fun clear() {
        context.sessionDataStore.edit { it.remove(tokenKey) }
    }
}
