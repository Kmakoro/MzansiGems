package za.co.hiddengems.app.auth

object AuthValidation {
    private val emailPattern = Regex("^[A-Za-z0-9+_.-]+@[A-Za-z0-9.-]+\\.[A-Za-z]{2,}$")

    fun loginError(email: String, password: String): String? = when {
        email.isBlank() -> "Enter your email address."
        !emailPattern.matches(email.trim()) -> "Enter a valid email address."
        password.isBlank() -> "Enter your password."
        else -> null
    }

    /**
     * Mirrors the website's creation policy. Existing passwords are still accepted on login.
     */
    fun newPasswordError(password: String): String? {
        if (password.codePointCount(0, password.length) < 15) {
            return "Password must have at least 15 characters."
        }
        if (password.toByteArray(Charsets.UTF_8).size > 4096) return "Password is too long."

        val hasUppercase = password.any { it in 'A'..'Z' }
        val hasLowercase = password.any { it in 'a'..'z' }
        val hasDigit = password.any { it in '0'..'9' }
        val hasSymbol = password.any { it in "!@#$%^&*" }
        if (!hasUppercase || !hasLowercase || !hasDigit || !hasSymbol) {
            return "Password must include uppercase (A-Z), lowercase (a-z), a number (0-9), and a symbol (!@#$%^&*)."
        }
        if (Regex("(?:00|11|22|33|44|55|66|77|88|99){4,}").containsMatchIn(password)) {
            return "Choose a less predictable password; avoid common passwords, sequences, and repeated patterns."
        }

        val normalized = password.lowercase().filter { it.isLetterOrDigit() }
        val common = setOf(
            "password", "password123", "password12345678", "passwordpassword",
            "12345678", "123456789", "1234567890", "qwerty", "qwerty123",
            "qwertyuiop", "letmein", "welcome", "admin", "administrator",
            "iloveyou", "mzansigems", "hiddengems", "correcthorsebatterystaple",
        )
        if (normalized in common ||
            Regex("^(?:password|welcome|admin|mzansigems|hiddengems)[0-9]*$").matches(normalized) ||
            Regex("^(.{1,8})\\1+$").matches(normalized) ||
            listOf("12345678", "87654321", "abcdefgh", "qwertyui").any { it in normalized }
        ) {
            return "Choose a less predictable password; avoid common passwords, sequences, and repeated patterns."
        }
        return null
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
        newPasswordError(password) != null -> newPasswordError(password)
        password != confirmPassword -> "Passwords do not match."
        else -> null
    }

    fun passwordResetError(password: String, confirmPassword: String): String? = when {
        newPasswordError(password) != null -> newPasswordError(password)
        password != confirmPassword -> "Passwords do not match."
        else -> null
    }
}
