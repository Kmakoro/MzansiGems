package za.co.hiddengems.app.ui.screens

import android.app.Activity

import androidx.compose.foundation.background
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.ColumnScope
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.imePadding
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.layout.width
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.ArrowBack
import androidx.compose.material.icons.filled.Email
import androidx.compose.material.icons.filled.LocationCity
import androidx.compose.material.icons.filled.Lock
import androidx.compose.material.icons.filled.Person
import androidx.compose.material.icons.filled.Visibility
import androidx.compose.material.icons.filled.VisibilityOff
import androidx.compose.material3.AlertDialog
import androidx.compose.material3.Button
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.Checkbox
import androidx.compose.material3.DropdownMenuItem
import androidx.compose.material3.ExperimentalMaterial3Api
import androidx.compose.material3.ExposedDropdownMenuBox
import androidx.compose.material3.ExposedDropdownMenuDefaults
import androidx.compose.material3.Icon
import androidx.compose.material3.IconButton
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.MenuAnchorType
import androidx.compose.material3.OutlinedButton
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.rememberCoroutineScope
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Brush
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.input.ImeAction
import androidx.compose.ui.text.input.KeyboardType
import androidx.compose.ui.text.input.PasswordVisualTransformation
import androidx.compose.ui.text.input.VisualTransformation
import androidx.compose.foundation.text.KeyboardOptions
import androidx.compose.ui.unit.dp
import kotlinx.coroutines.launch
import za.co.hiddengems.app.auth.AuthValidation
import za.co.hiddengems.app.auth.FirebaseGoogleSignIn
import za.co.hiddengems.app.ui.components.AppLogo
import za.co.hiddengems.app.ui.theme.Orange
import za.co.hiddengems.app.ui.theme.OrangeDark
import za.co.hiddengems.app.ui.theme.Peach

@Composable
fun LoginScreen(
    busy: Boolean,
    serverOnline: Boolean?,
    resetToken: String?,
    siteName: String,
    onBack: () -> Unit,
    onLogin: (String, String) -> Unit,
    onFirebaseLogin: (String) -> Unit,
    onForgotPassword: (String) -> Unit,
    onResetPassword: (String, String) -> Unit,
    onDismissReset: () -> Unit,
    onRegister: () -> Unit,
) {
    var email by remember { mutableStateOf("") }
    var password by remember { mutableStateOf("") }
    var showPassword by remember { mutableStateOf(false) }
    var showForgotPassword by remember { mutableStateOf(false) }
    var resetEmail by remember { mutableStateOf("") }
    var localError by remember { mutableStateOf("") }
    var rememberMe by remember { mutableStateOf(false) }
    val context = LocalContext.current
    val activity = context as? Activity
    val scope = rememberCoroutineScope()
    val googleSignIn = remember(activity) { activity?.let(::FirebaseGoogleSignIn) }

    var newPassword by remember { mutableStateOf("") }
    var confirmPassword by remember { mutableStateOf("") }

    AuthBackground(onBack = onBack) {
        AppLogo(siteName = siteName, showName = true)
        Spacer(Modifier.height(24.dp))
        
        if (resetToken != null) {
            Text("Reset Password", style = MaterialTheme.typography.headlineSmall, fontWeight = FontWeight.ExtraBold)
            Text("Enter a new password for your account.", color = MaterialTheme.colorScheme.onSurfaceVariant)
            Spacer(Modifier.height(22.dp))
            OutlinedTextField(
                value = newPassword,
                onValueChange = { newPassword = it },
                label = { Text("New Password") },
                leadingIcon = { Icon(Icons.Default.Lock, null) },
                visualTransformation = PasswordVisualTransformation(),
                modifier = Modifier.fillMaxWidth()
            )
            Spacer(Modifier.height(12.dp))
            OutlinedTextField(
                value = confirmPassword,
                onValueChange = { confirmPassword = it },
                label = { Text("Confirm New Password") },
                leadingIcon = { Icon(Icons.Default.Lock, null) },
                visualTransformation = PasswordVisualTransformation(),
                modifier = Modifier.fillMaxWidth()
            )
            Spacer(Modifier.height(16.dp))
            Button(
                onClick = {
                    localError = AuthValidation.passwordResetError(newPassword, confirmPassword).orEmpty()
                    if (localError.isBlank()) onResetPassword(newPassword, confirmPassword)
                },
                modifier = Modifier.fillMaxWidth().height(50.dp)
            ) { Text("Update Password") }
            if (localError.isNotBlank()) {
                Text(localError, color = MaterialTheme.colorScheme.error, style = MaterialTheme.typography.bodySmall)
            }
            TextButton(onClick = onDismissReset, modifier = Modifier.fillMaxWidth()) { Text("Cancel") }
        } else {
            Text("Welcome Back!", style = MaterialTheme.typography.headlineSmall, fontWeight = FontWeight.ExtraBold)
            Text("Sign in to discover more Mzansi Gems.", color = MaterialTheme.colorScheme.onSurfaceVariant)
            Spacer(Modifier.height(22.dp))
            OutlinedTextField(
                value = email,
                onValueChange = { email = it; localError = "" },
                label = { Text("Email address") },
                leadingIcon = { Icon(Icons.Default.Email, null) },
                singleLine = true,
                keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Email, imeAction = ImeAction.Next),
                modifier = Modifier.fillMaxWidth(),
            )
            Spacer(Modifier.height(12.dp))
            OutlinedTextField(
                value = password,
                onValueChange = { password = it; localError = "" },
                label = { Text("Password") },
                leadingIcon = { Icon(Icons.Default.Lock, null) },
                trailingIcon = {
                    IconButton(onClick = { showPassword = !showPassword }) {
                        Icon(if (showPassword) Icons.Default.VisibilityOff else Icons.Default.Visibility, "Show password")
                    }
                },
                singleLine = true,
                visualTransformation = if (showPassword) VisualTransformation.None else PasswordVisualTransformation(),
                keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Password, imeAction = ImeAction.Done),
                modifier = Modifier.fillMaxWidth(),
            )
            Row(
                modifier = Modifier.fillMaxWidth(),
                verticalAlignment = Alignment.CenterVertically,
                horizontalArrangement = Arrangement.SpaceBetween
            ) {
                Row(verticalAlignment = Alignment.CenterVertically) {
                    Checkbox(checked = rememberMe, onCheckedChange = { rememberMe = it })
                    Text("Remember me", style = MaterialTheme.typography.bodySmall)
                }
                TextButton(onClick = { resetEmail = email; showForgotPassword = true }) {
                    Text("Forgot password?", color = OrangeDark, fontWeight = FontWeight.Bold)
                }
            }
            if (localError.isNotBlank()) {
                Text(localError, color = MaterialTheme.colorScheme.error, style = MaterialTheme.typography.bodySmall)
            }
            Spacer(Modifier.height(12.dp))
            Button(
                onClick = {
                    localError = AuthValidation.loginError(email, password).orEmpty()
                    if (localError.isBlank()) onLogin(email, password)
                },
                enabled = !busy,
                modifier = Modifier.fillMaxWidth().height(50.dp),
            ) { Text("Sign In") }
            Spacer(Modifier.height(12.dp))
            OutlinedButton(
                onClick = {
                    localError = ""
                    val manager = googleSignIn
                    if (manager == null) {
                        localError = "Google sign-in requires an Android Activity."
                    } else {
                        scope.launch {
                            manager.signIn()
                                .onSuccess(onFirebaseLogin)
                                .onFailure { error ->
                                    localError = error.message ?: "Google sign-in failed. Please try again."
                                }
                        }
                    }
                },
                enabled = !busy,
                modifier = Modifier.fillMaxWidth(),
            ) {
                Text("◉ Continue with Google")
            }
        }
        
        Spacer(Modifier.height(14.dp))
        Row(verticalAlignment = Alignment.CenterVertically, horizontalArrangement = Arrangement.Center, modifier = Modifier.fillMaxWidth()) {
            Text("Don't have an account?")
            TextButton(onClick = onRegister) { Text("Sign up") }
        }
        Text(
            "Demo: user@mzansigem.local / User@123",
            style = MaterialTheme.typography.labelSmall,
            color = MaterialTheme.colorScheme.onSurfaceVariant,
            modifier = Modifier.padding(top = 8.dp)
        )
        if (serverOnline != null) {
            Spacer(Modifier.height(8.dp))
            Row(verticalAlignment = Alignment.CenterVertically) {
                Box(Modifier.size(8.dp).background(if (serverOnline) Color(0xFF20A66A) else Color.Red, CircleShape))
                Spacer(Modifier.width(6.dp))
                Text(
                    if (serverOnline) "API is Online" else "API is Offline (Check XAMPP)",
                    style = MaterialTheme.typography.labelSmall,
                    color = if (serverOnline) Color(0xFF20A66A) else Color.Red
                )
            }
        }
    }

    if (showForgotPassword) {
        AlertDialog(
            onDismissRequest = { showForgotPassword = false },
            title = { Text("Reset your password") },
            text = {
                Column(verticalArrangement = Arrangement.spacedBy(8.dp)) {
                    Text("Enter the email address linked to your account. We will send a one-hour reset link.")
                    OutlinedTextField(
                        value = resetEmail,
                        onValueChange = { resetEmail = it },
                        label = { Text("Email address") },
                        leadingIcon = { Icon(Icons.Default.Email, null) },
                        singleLine = true,
                        keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Email),
                        modifier = Modifier.fillMaxWidth(),
                    )
                }
            },
            confirmButton = {
                Button(
                    onClick = { onForgotPassword(resetEmail); showForgotPassword = false },
                    enabled = resetEmail.contains('@'),
                ) { Text("Send reset link") }
            },
            dismissButton = { TextButton(onClick = { showForgotPassword = false }) { Text("Cancel") } },
        )
    }
}

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun RegisterScreen(
    busy: Boolean,
    serverOnline: Boolean?,
    siteName: String,
    cities: List<String>,
    showVerificationNotice: Boolean,
    onDismissNotice: () -> Unit,
    onBack: () -> Unit,
    onRegister: (String, String, String, String, String) -> Unit,
    onLogin: () -> Unit,
) {
    var name by remember { mutableStateOf("") }
    var email by remember { mutableStateOf("") }
    var city by remember(cities) { mutableStateOf(cities.firstOrNull() ?: "Cape Town") }
    var password by remember { mutableStateOf("") }
    var confirm by remember { mutableStateOf("") }
    var showPassword by remember { mutableStateOf(false) }
    var localError by remember { mutableStateOf("") }
    var cityExpanded by remember { mutableStateOf(false) }

    AuthBackground(onBack = onBack) {
        AppLogo(siteName = siteName, showName = true)
        Spacer(Modifier.height(20.dp))
        Text("Join the Community", style = MaterialTheme.typography.headlineSmall, fontWeight = FontWeight.ExtraBold)
        Text("Start discovering with Mzansi Gem today", color = MaterialTheme.colorScheme.onSurfaceVariant)
        Spacer(Modifier.height(18.dp))
        AuthField(name, { name = it }, "Full name", Icons.Default.Person)
        Spacer(Modifier.height(10.dp))
        AuthField(email, { email = it }, "Email address", Icons.Default.Email, KeyboardType.Email)
        Spacer(Modifier.height(10.dp))

        ExposedDropdownMenuBox(
            expanded = cityExpanded,
            onExpandedChange = { cityExpanded = !cityExpanded },
            modifier = Modifier.fillMaxWidth()
        ) {
            OutlinedTextField(
                value = city,
                onValueChange = {},
                readOnly = true,
                label = { Text("City") },
                leadingIcon = { Icon(Icons.Default.LocationCity, null) },
                trailingIcon = { ExposedDropdownMenuDefaults.TrailingIcon(expanded = cityExpanded) },
                colors = ExposedDropdownMenuDefaults.outlinedTextFieldColors(),
                modifier = Modifier.menuAnchor(MenuAnchorType.PrimaryNotEditable, true).fillMaxWidth()
            )
            ExposedDropdownMenu(
                expanded = cityExpanded,
                onDismissRequest = { cityExpanded = false }
            ) {
                cities.forEach { selection ->
                    DropdownMenuItem(
                        text = { Text(selection) },
                        onClick = {
                            city = selection
                            cityExpanded = false
                        },
                        contentPadding = ExposedDropdownMenuDefaults.ItemContentPadding
                    )
                }
            }
        }

        Spacer(Modifier.height(10.dp))
        OutlinedTextField(
            value = password,
            onValueChange = { password = it },
            label = { Text("Password") },
            leadingIcon = { Icon(Icons.Default.Lock, null) },
            trailingIcon = {
                IconButton(onClick = { showPassword = !showPassword }) {
                    Icon(if (showPassword) Icons.Default.VisibilityOff else Icons.Default.Visibility, "Show password")
                }
            },
            visualTransformation = if (showPassword) VisualTransformation.None else PasswordVisualTransformation(),
            keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Password),
            singleLine = true,
            modifier = Modifier.fillMaxWidth(),
        )
        Spacer(Modifier.height(10.dp))
        OutlinedTextField(
            value = confirm,
            onValueChange = { confirm = it },
            label = { Text("Confirm password") },
            leadingIcon = { Icon(Icons.Default.Lock, null) },
            visualTransformation = PasswordVisualTransformation(),
            keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Password, imeAction = ImeAction.Done),
            singleLine = true,
            modifier = Modifier.fillMaxWidth(),
        )
        if (localError.isNotBlank()) {
            Spacer(Modifier.height(8.dp))
            Text(localError, color = MaterialTheme.colorScheme.error, style = MaterialTheme.typography.bodySmall)
        }
        Spacer(Modifier.height(16.dp))
        Button(
            onClick = {
                localError = AuthValidation.registrationError(name, email, city, password, confirm).orEmpty()
                if (localError.isBlank()) onRegister(name, email, city, password, confirm)
            },
            enabled = !busy,
            modifier = Modifier.fillMaxWidth().height(50.dp),
        ) { Text("Create Account") }
        Row(verticalAlignment = Alignment.CenterVertically, horizontalArrangement = Arrangement.Center, modifier = Modifier.fillMaxWidth()) {
            Text("Already have an account?")
            TextButton(onClick = onLogin) { Text("Sign in") }
        }
        if (serverOnline != null) {
            Spacer(Modifier.height(8.dp))
            Row(verticalAlignment = Alignment.CenterVertically) {
                Box(Modifier.size(8.dp).background(if (serverOnline) Color(0xFF20A66A) else Color.Red, CircleShape))
                Spacer(Modifier.width(6.dp))
                Text(
                    if (serverOnline) "API is Online" else "API is Offline (Check XAMPP)",
                    style = MaterialTheme.typography.labelSmall,
                    color = if (serverOnline) Color(0xFF20A66A) else Color.Red
                )
            }
        }
    }

    if (showVerificationNotice) {
        AlertDialog(
            onDismissRequest = onDismissNotice,
            title = { Text("Account Verification Required") },
            text = { Text("We have sent a verification link to your email address. Please follow the instructions in the email to activate your account.") },
            confirmButton = { Button(onClick = { onDismissNotice(); onLogin() }) { Text("Go to Login") } }
        )
    }
}

@Composable
private fun AuthField(
    value: String,
    onValueChange: (String) -> Unit,
    label: String,
    icon: androidx.compose.ui.graphics.vector.ImageVector,
    keyboardType: KeyboardType = KeyboardType.Text,
) {
    OutlinedTextField(
        value = value,
        onValueChange = onValueChange,
        label = { Text(label) },
        leadingIcon = { Icon(icon, null) },
        keyboardOptions = KeyboardOptions(keyboardType = keyboardType, imeAction = ImeAction.Next),
        singleLine = true,
        modifier = Modifier.fillMaxWidth(),
    )
}

@Composable
private fun AuthBackground(onBack: () -> Unit, content: @Composable ColumnScope.() -> Unit) {
    Box(
        Modifier
            .fillMaxSize()
            .background(Brush.verticalGradient(listOf(Peach.copy(alpha = .85f), Orange, MaterialTheme.colorScheme.background)))
            .imePadding(),
    ) {
        IconButton(onClick = onBack, modifier = Modifier.padding(8.dp).align(Alignment.TopStart)) {
            Icon(Icons.AutoMirrored.Filled.ArrowBack, "Back", tint = Color.White)
        }
        Card(
            modifier = Modifier
                .align(Alignment.Center)
                .padding(horizontal = 22.dp, vertical = 60.dp)
                .fillMaxWidth(),
            shape = RoundedCornerShape(26.dp),
            colors = CardDefaults.cardColors(containerColor = Color.White),
            elevation = CardDefaults.cardElevation(10.dp),
        ) {
            Column(
                Modifier
                    .fillMaxWidth()
                    .verticalScroll(rememberScrollState())
                    .padding(24.dp),
                horizontalAlignment = Alignment.CenterHorizontally,
                content = content,
            )
        }
    }
}
