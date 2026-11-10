package za.co.hiddengems.app.auth

import org.junit.Assert.assertEquals
import org.junit.Assert.assertNull
import org.junit.Test

class AuthValidationTest {
    @Test fun validLoginHasNoError() {
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
                "Thandi Ndlovu", "thandi@example.com", "Johannesburg", "MySaferPassword2026!", "MySaferPassword2026!"
            )
        )
    }

    @Test fun shortRegistrationPasswordIsRejected() {
        assertEquals(
            "Password must have at least 12 characters.",
            AuthValidation.registrationError("Thandi Ndlovu", "thandi@example.com", "Johannesburg", "1234", "1234")
        )
    }

    @Test fun mismatchedRegistrationPasswordsAreRejected() {
        assertEquals(
            "Passwords do not match.",
            AuthValidation.registrationError(
                "Thandi Ndlovu", "thandi@example.com", "Johannesburg", "MySaferPassword2026!", "DifferentSaferPassword2026!"
            )
        )
    }

    @Test fun commonPasswordIsRejected() {
        assertEquals("Choose a less common password.", AuthValidation.passwordResetError("password1234", "password1234"))
    }

    @Test fun resetPasswordValidationWorks() {
        assertNull(AuthValidation.passwordResetError("NewPassword123", "NewPassword123"))
        assertEquals(
            "Passwords do not match.",
            AuthValidation.passwordResetError("NewPassword123", "Different123")
        )
    }
}
