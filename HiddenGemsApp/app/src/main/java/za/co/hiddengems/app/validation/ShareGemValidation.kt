package za.co.hiddengems.app.validation

object ShareGemValidation {
    fun error(
        title: String,
        description: String,
        location: String,
        city: String,
        vibes: List<String>,
        budget: String,
        activity: String,
        rating: Int,
    ): String? = when {
        title.trim().length !in 3..100 -> "Name must be 3 to 100 characters."
        description.trim().length !in 20..500 -> "Description must be 20 to 500 characters."
        location.isBlank() -> "Location is required."
        city.isBlank() -> "City is required."
        vibes.isEmpty() -> "Select at least one vibe."
        vibes.size > 3 -> "Select no more than three vibes."
        budget.isBlank() -> "Select a budget level."
        activity.isBlank() -> "Select an activity type."
        rating !in 1..5 -> "Choose a rating from 1 to 5."
        else -> null
    }
}
