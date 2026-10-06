package za.co.hiddengems.app.validation

import org.junit.Assert.assertEquals
import org.junit.Assert.assertNull
import org.junit.Test

class ShareGemValidationTest {
    private fun validate(
        title: String = "Hidden Garden",
        description: String = "A peaceful hidden garden with shaded paths and local plants.",
        location: String = "Braamfontein",
        city: String = "Johannesburg",
        vibes: List<String> = listOf("Quiet", "Chill"),
        budget: String = "Free",
        activity: String = "Nature",
        rating: Int = 5,
    ) = ShareGemValidation.error(title, description, location, city, vibes, budget, activity, rating)

    @Test fun validGemHasNoError() {
        assertNull(validate())
    }

    @Test fun shortTitleIsRejected() {
        assertEquals("Name must be 3 to 100 characters.", validate(title = "Hi"))
    }

    @Test fun shortDescriptionIsRejected() {
        assertEquals("Description must be 20 to 500 characters.", validate(description = "Too short"))
    }

    @Test fun missingLocationIsRejected() {
        assertEquals("Location is required.", validate(location = ""))
    }

    @Test fun missingVibeIsRejected() {
        assertEquals("Select at least one vibe.", validate(vibes = emptyList()))
    }

    @Test fun moreThanThreeVibesIsRejected() {
        assertEquals("Select no more than three vibes.", validate(vibes = listOf("Quiet", "Chill", "Social", "Romantic")))
    }

    @Test fun invalidRatingIsRejected() {
        assertEquals("Choose a rating from 1 to 5.", validate(rating = 0))
    }
}
