package za.co.hiddengems.app

import android.app.Application
import za.co.hiddengems.app.data.ApiClient

class HiddenGemsApplication : Application() {
    val apiClient: ApiClient by lazy { ApiClient(this) }
}
