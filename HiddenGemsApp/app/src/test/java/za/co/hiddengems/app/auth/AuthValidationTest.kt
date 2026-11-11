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
        for (password in listOf("Password12345678!", "Aa!1122334455667788", "Aa1!Aa1!Aa1!Aa1!")) {
            assertEquals(error, AuthValidation.newPasswordError(password))
        }
    }

    @Test fun longPassphrasesAreAccepted() {
        assertNull(AuthValidation.newPasswordError("Quiet-coffee-river-moon7!"))
        assertNull(AuthValidation.newPasswordError("V7#mQ2!zR9@pL4$s"))
    }

    @Test fun eachCharacterTypeIsMandatory() {
        val message = "Password must include uppercase (A-Z), lowercase (a-z), a number (0-9), and a symbol (!@#$%^&*)."
        for (password in listOf(
            "1122334455667788", // only numbers, even with 16 characters
            "ALLUPPERCASE123!", // no lowercase
            "alllowercase123!", // no uppercase
            "NoNumbersHere!!", // no number
            "NoSymbolsHere123", // no symbol
        )) {
            assertEquals(message, AuthValidation.newPasswordError(password))
        }
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
