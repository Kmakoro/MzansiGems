package za.co.hiddengems.app.data

import com.google.gson.Gson
import com.google.gson.reflect.TypeToken
import org.junit.Assert.assertEquals
import org.junit.Assert.assertTrue
import org.junit.Test

class GemJsonDefaultsTest {
    private val gson = Gson()

    @Test
    fun listGemsWithoutDetailFieldsCanBeLikedAndSaved() {
        // Matches the public /gems list: no personal_note, images or reviews.
        val json = """
            [{
              "id": 5,
              "user_id": 4,
              "title": "Sunset Beach Run",
              "description": "A scenic route.",
              "location": "Three Anchor Bay",
              "city": "Cape Town",
              "budget_level": "Free",
              "activity_type": "Fitness",
              "is_liked": false,
              "like_count": 2,
              "is_saved": false,
              "save_count": 4
            }]
        """.trimIndent()
        val type = object : TypeToken<List<Gem>>() {}.type
        val gem = gson.fromJson<List<Gem>>(json, type).single()

        assertEquals("", gem.personalNote)
        assertTrue(gem.images.isEmpty())
        assertTrue(gem.reviews.isEmpty())

        val liked = gem.copy(isLiked = true, likeCount = gem.likeCount + 1)
        val saved = liked.copy(isSaved = true, saveCount = liked.saveCount + 1)

        assertTrue(saved.isLiked)
        assertTrue(saved.isSaved)
        assertEquals(3, saved.likeCount)
        assertEquals(5, saved.saveCount)
    }

    @Test
    fun savedGemRetainsItsPersonalNoteWhenCopied() {
        val json = """
            {
              "id": 5,
              "user_id": 4,
              "title": "Sunset Beach Run",
              "description": "A scenic route.",
              "location": "Three Anchor Bay",
              "city": "Cape Town",
              "budget_level": "Free",
              "activity_type": "Fitness",
              "personal_note": "Visit on Saturday"
            }
        """.trimIndent()
        val gem = gson.fromJson(json, Gem::class.java)
        val updated = gem.copy(isSaved = true)

        assertEquals("Visit on Saturday", updated.personalNote)
        assertTrue(updated.images.isEmpty())
        assertTrue(updated.reviews.isEmpty())
    }
}
