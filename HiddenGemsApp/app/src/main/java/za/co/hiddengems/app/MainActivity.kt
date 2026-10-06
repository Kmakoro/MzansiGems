package za.co.hiddengems.app

import android.content.Intent
import android.net.Uri
import android.os.Bundle
import androidx.activity.ComponentActivity
import androidx.activity.compose.setContent
import androidx.activity.enableEdgeToEdge
import androidx.lifecycle.viewmodel.compose.viewModel
import za.co.hiddengems.app.ui.AppViewModel
import za.co.hiddengems.app.ui.MzansiGemApp
import za.co.hiddengems.app.ui.theme.HiddenGemsTheme

import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.setValue

class MainActivity : ComponentActivity() {
    private var deepLinkUri by mutableStateOf<Uri?>(null)

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        deepLinkUri = intent?.data
        enableEdgeToEdge()
        setContent {
            val viewModel: AppViewModel = viewModel(factory = AppViewModel.factory(application as HiddenGemsApplication))
            
            LaunchedEffect(deepLinkUri) {
                deepLinkUri?.let { uri ->
                    handleDeepLink(uri, viewModel)
                    deepLinkUri = null
                }
            }

            HiddenGemsTheme {
                MzansiGemApp(viewModel)
            }
        }
    }

    override fun onNewIntent(intent: Intent) {
        super.onNewIntent(intent)
        deepLinkUri = intent.data
    }

    private fun handleDeepLink(uri: Uri, viewModel: AppViewModel) {
        val token = uri.getQueryParameter("token")
        if (token != null) {
            when {
                uri.path?.contains("verify-email") == true || uri.host == "auth" && uri.path?.contains("verify-email") == true ->
                    viewModel.verifyEmail(token)
                uri.path?.contains("reset-password") == true || uri.host == "auth" && uri.path?.contains("reset-password") == true ->
                    viewModel.setResetToken(token)
            }
        }
    }
}
