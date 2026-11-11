package za.co.hiddengems.app.ui

import org.junit.Assert.assertEquals
import org.junit.Assert.assertFalse
import org.junit.Assert.assertNull
import org.junit.Assert.assertTrue
import org.junit.Test
import za.co.hiddengems.app.data.ApiResult
import za.co.hiddengems.app.data.Gem

class GemDetailOfflineStateTest {
    private fun gem(id: Int) = Gem(
        id = id,
        userId = 2,
        title = "Gem $id",
        description = "Test description",
        location = "Cape Town",
        city = "Cape Town",
        budgetLevel = "Free",
        activityType = "Culture",
    )

    @Test fun offlineFailureEndsLoadingAndOffersRetryForRequestedGem() {
        val initial = AppUiState().beginGemLoad(3)
        assertTrue(initial.selectedGemLoading)
        assertEquals(3, initial.selectedGemRequestId)

        val failed = initial.withGemResult(
            3,
            ApiResult.Error(
                "You're offline. Check your internet connection and try again.",
                isNetworkError = true,
            ),
        )
        assertFalse(failed.selectedGemLoading)
        assertNull(failed.selectedGem)
        assertTrue(failed.selectedGemOffline)
        assertEquals("You're offline. Check your internet connection and try again.", failed.selectedGemLoadError)
    }

    @Test fun retryClearsOfflineErrorAndLoadsGem() {
        val offline = AppUiState().beginGemLoad(7).withGemResult(
            7, ApiResult.Error("You're offline", isNetworkError = true),
        )
        val retrying = offline.beginGemLoad(7)
        assertTrue(retrying.selectedGemLoading)
        assertNull(retrying.selectedGemLoadError)
        assertFalse(retrying.selectedGemOffline)

        val loaded = retrying.withGemResult(7, ApiResult.Success(gem(7)))
        assertEquals(7, loaded.selectedGem?.id)
        assertFalse(loaded.selectedGemLoading)
        assertNull(loaded.selectedGemLoadError)
        assertFalse(loaded.selectedGemOffline)
        assertTrue(loaded.serverOnline == true)
    }

    @Test fun failedRefreshPreservesDetailsOfSameGem() {
        val current = AppUiState(selectedGem = gem(8))
        val error = current.beginGemLoad(8).withGemResult(
            8, ApiResult.Error("You're offline", isNetworkError = true),
        )
        assertEquals(8, error.selectedGem?.id)
        assertTrue(error.selectedGemOffline)
    }

    @Test fun requestingAnotherGemClearsPreviousDetailAndIgnoresLateResponse() {
        val requestOne = AppUiState(selectedGem = gem(4)).beginGemLoad(5)
        assertNull(requestOne.selectedGem)
        val requestTwo = requestOne.beginGemLoad(6)
        val afterLateResponse = requestTwo.withGemResult(5, ApiResult.Success(gem(5)))
        assertEquals(6, afterLateResponse.selectedGemRequestId)
        assertNull(afterLateResponse.selectedGem)
        assertTrue(afterLateResponse.selectedGemLoading)
    }

    @Test fun serverErrorIsNotIncorrectlyLabelledOffline() {
        val error = AppUiState().beginGemLoad(2).withGemResult(
            2, ApiResult.Error("Server error"),
        )
        assertFalse(error.selectedGemOffline)
        assertEquals("Server error", error.selectedGemLoadError)
    }
}
