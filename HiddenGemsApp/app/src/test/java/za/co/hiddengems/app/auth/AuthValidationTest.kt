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
                "Thandi Ndlovu", "thandi@example.com", "Johannesburg", "Password123", "Password123"
            )
        )
    }

    @Test fun shortRegistrationPasswordIsRejected() {
        assertEquals(
            "Password must have at least 8 characters.",
            AuthValidation.registrationError("Thandi Ndlovu", "thandi@example.com", "Johannesburg", "1234", "1234")
        )
    }

    @Test fun mismatchedRegistrationPasswordsAreRejected() {
        assertEquals(
            "Passwords do not match.",
            AuthValidation.registrationError(
                "Thandi Ndlovu", "thandi@example.com", "Johannesburg", "Password123", "Password321"
            )
        )
    }

    @Test fun resetPasswordValidationWorks() {
        assertNull(AuthValidation.passwordResetError("NewPassword123", "NewPassword123"))
        assertEquals(
            "Passwords do not match.",
            AuthValidation.passwordResetError("NewPassword123", "Different123")
        )
    }
}
