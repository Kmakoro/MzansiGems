package za.co.hiddengems.app.ui

import org.junit.Assert.assertEquals
import org.junit.Assert.assertFalse
import org.junit.Assert.assertNull
import org.junit.Assert.assertTrue
import org.junit.Test
import za.co.hiddengems.app.data.ApiResult
import za.co.hiddengems.app.data.Gem

class GemsOfflineStateTest {
    private val gem = Gem(
        id = 1,
        userId = 2,
        title = "Test Gem",
        description = "Test description",
        location = "Cape Town",
        city = "Cape Town",
        budgetLevel = "Free",
        activityType = "Culture",
    )

    @Test fun offlineFailureKeepsCachedGemsInsteadOfShowingEmptyResults() {
        val current = AppUiState(gems = listOf(gem), query = "coffee", serverOnline = true)
        val offline = current.withGemsResult(
            ApiResult.Error(
                "You're offline. Check your internet connection and try again.",
                isNetworkError = true,
            )
        )

        assertEquals(listOf(gem), offline.gems)
        assertEquals("coffee", offline.query)
        assertTrue(offline.gemsOffline)
        assertFalse(offline.serverOnline ?: true)
        assertEquals("You're offline. Check your internet connection and try again.", offline.gemsLoadError)
    }

    @Test fun successfulRetryReplacesResultsAndClearsOfflineNotice() {
        val offline = AppUiState(gemsOffline = true, gemsLoadError = "You're offline")
        val recovered = offline.withGemsResult(ApiResult.Success(listOf(gem)))

        assertEquals(listOf(gem), recovered.gems)
        assertNull(recovered.gemsLoadError)
        assertFalse(recovered.gemsOffline)
        assertTrue(recovered.serverOnline == true)
    }

    @Test fun genuineEmptyResponseIsNotAnOfflineFailure() {
        val successfulEmpty = AppUiState().withGemsResult(ApiResult.Success(emptyList<Gem>()))

        assertTrue(successfulEmpty.gems.isEmpty())
        assertNull(successfulEmpty.gemsLoadError)
        assertFalse(successfulEmpty.gemsOffline)
    }

    @Test fun serverFailureDoesNotClaimDeviceIsOffline() {
        val failed = AppUiState(serverOnline = true).withGemsResult(ApiResult.Error("Server is unavailable"))

        assertFalse(failed.gemsOffline)
        assertEquals("Server is unavailable", failed.gemsLoadError)
        assertTrue(failed.serverOnline == true)
    }
}
