package za.co.hiddengems.app.auth

import org.junit.Assert.assertEquals
import org.junit.Assert.assertNull
import org.junit.Test

class AuthValidationTest {
    @Test fun validLoginHasNoError() {
        // An existing eight-character password is not blocked at sign-in.
        assertNull(AuthValidation.loginError("user@example.com", "Password123"))
    }

    @Test fun invalidEmailIsRejected() {
        assertEquals("Enter a valid email address.", AuthValidation.loginError("user.example.com", "Password123"))
    }

    @Test fun blankPasswordIsRejected() {
        assertEquals("Enter your password.", AuthValidation.loginError("user@example.com", ""))
    }

    @Test fun validRegistrationHasNoError() {
        assertNull(
            AuthValidation.registrationError(
                "Thandi Ndlovu", "thandi@example.com", "Johannesburg",
                "River Moon Lantern 92!", "River Moon Lantern 92!"
            )
        )
    }

    @Test fun shortRegistrationPasswordIsRejected() {
        assertEquals(
            "Password must have at least 15 characters.",
            AuthValidation.registrationError("Thandi Ndlovu", "thandi@example.com", "Johannesburg", "12345678", "12345678")
        )
    }

    @Test fun predictablePasswordsAreRejected() {
        val error = "Choose a less predictable password; avoid common passwords, sequences, and repeated patterns."
        for (password in listOf("123456789012345", "Password12345678!", "aaaaaaaaaaaaaaaa")) {
            assertEquals(error, AuthValidation.newPasswordError(password))
        }
    }

    @Test fun longPassphrasesAreAccepted() {
        assertNull(AuthValidation.newPasswordError("quiet-coffee-river-moon"))
    }

    @Test fun mismatchedRegistrationPasswordsAreRejected() {
        assertEquals(
            "Passwords do not match.",
            AuthValidation.registrationError(
                "Thandi Ndlovu", "thandi@example.com", "Johannesburg",
                "River Moon Lantern 92!", "Quiet River Lantern 92!"
            )
        )
    }

    @Test fun resetPasswordValidationWorks() {
        assertNull(AuthValidation.passwordResetError("River Moon Lantern 92!", "River Moon Lantern 92!"))
        assertEquals(
            "Passwords do not match.",
            AuthValidation.passwordResetError("River Moon Lantern 92!", "Different long secret 92")
        )
        assertEquals(
            "Password must have at least 15 characters.",
            AuthValidation.passwordResetError("12345678", "12345678")
        )
    }
}
