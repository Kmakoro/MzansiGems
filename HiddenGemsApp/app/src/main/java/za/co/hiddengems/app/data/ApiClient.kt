package za.co.hiddengems.app.data

import android.content.Context
import com.google.gson.Gson
import kotlinx.coroutines.runBlocking
import okhttp3.Interceptor
import okhttp3.OkHttpClient
import okhttp3.logging.HttpLoggingInterceptor
import retrofit2.Retrofit
import retrofit2.converter.gson.GsonConverterFactory
import za.co.hiddengems.app.BuildConfig
import java.util.concurrent.TimeUnit

class ApiClient(context: Context) {
    val session = SessionManager(context.applicationContext)
    private val gson = Gson()

    private val authInterceptor = Interceptor { chain ->
        val request = chain.request()
        val token = runBlocking { session.token() }
        val authenticated = if (token.isNullOrBlank()) request else request.newBuilder()
            .header("Authorization", "Bearer $token")
            .header("Accept", "application/json")
            .build()
        chain.proceed(authenticated)
    }

    private val logging = HttpLoggingInterceptor().apply {
        level = if (BuildConfig.DEBUG) HttpLoggingInterceptor.Level.BASIC else HttpLoggingInterceptor.Level.NONE
    }

    private val okHttp = OkHttpClient.Builder()
        .addInterceptor(authInterceptor)
        .addInterceptor(logging)
        .connectTimeout(20, TimeUnit.SECONDS)
        .readTimeout(30, TimeUnit.SECONDS)
        .writeTimeout(30, TimeUnit.SECONDS)
        .build()

    val api: HiddenGemsApi = Retrofit.Builder()
        .baseUrl(BuildConfig.API_BASE_URL)
        .client(okHttp)
        .addConverterFactory(GsonConverterFactory.create(gson))
        .build()
        .create(HiddenGemsApi::class.java)

    val repository = HiddenGemsRepository(context.applicationContext, api, session, gson)
}
