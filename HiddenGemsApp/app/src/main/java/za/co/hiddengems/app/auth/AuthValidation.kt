package za.co.hiddengems.app.auth

object AuthValidation {
    private val emailPattern = Regex("^[A-Za-z0-9+_.-]+@[A-Za-z0-9.-]+\\.[A-Za-z]{2,}$")

    fun loginError(email: String, password: String): String? = when {
        email.isBlank() -> "Enter your email address."
        !emailPattern.matches(email.trim()) -> "Enter a valid email address."
        password.isBlank() -> "Enter your password."
        else -> null
    }

    fun registrationError(
        name: String,
        email: String,
        city: String,
        password: String,
        confirmPassword: String,
    ): String? = when {
        name.trim().length < 2 -> "Full name must have at least 2 characters."
        !emailPattern.matches(email.trim()) -> "Enter a valid email address."
        city.isBlank() -> "City is required."
        password.length < 12 -> "Password must have at least 12 characters."
        password.lowercase() in setOf("password1234", "password12345", "123456789012", "qwerty123456", "admin12345678") -> "Choose a less common password."
        password != confirmPassword -> "Passwords do not match."
        else -> null
    }

    fun passwordResetError(password: String, confirmPassword: String): String? = when {
        password.length < 8 -> "Password must have at least 8 characters."
        password != confirmPassword -> "Passwords do not match."
        else -> null
    }
}
