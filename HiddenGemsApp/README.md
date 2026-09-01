# Mzansi Gem Android App

Native Android client for the Mzansi Gem PHP/MySQL platform.

## Technology

- Kotlin
- Jetpack Compose and Material 3
- Navigation Compose
- ViewModel and StateFlow
- Retrofit and OkHttp
- Gson
- Coil 3
- DataStore Preferences

## Open and run

1. Run the PHP website/API at `http://localhost:8080`.
2. Open this folder in Android Studio.
3. Sync Gradle.
4. Use an emulator with Android 6.0/API 23 or later.
5. Run `app`.

The default emulator API URL is defined in `app/build.gradle.kts` as:

```text
http://10.0.2.2:8080/api/v1/
```

For a physical device, use your development computer's LAN IP and ensure the device can reach port 8080:

```bash
./gradlew assembleDebug -PAPI_BASE_URL=http://192.168.1.20:8080/api/v1/
```

## Main code map

```text
app/src/main/java/za/co/hiddengems/app/
├── data/
│   ├── ApiClient.kt
│   ├── ApiService.kt
│   ├── Models.kt
│   ├── Repository.kt
│   └── SessionManager.kt
├── ui/
│   ├── components/Common.kt
│   ├── screens/
│   │   ├── AdminScreens.kt
│   │   ├── AuthScreens.kt
│   │   ├── GemScreens.kt
│   │   ├── HomeDiscoverScreens.kt
│   │   ├── ProfileScreens.kt
│   │   └── SavedShareScreens.kt
│   ├── theme/Theme.kt
│   ├── AppViewModel.kt
│   └── HiddenGemsApp.kt
├── HiddenGemsApplication.kt
└── MainActivity.kt
```

## Build from a terminal

```bash
./gradlew assembleDebug
```

APK output:

```text
app/build/outputs/apk/debug/app-debug.apk
```

The first wrapper run downloads Gradle 9.4.1. JDK 17 and Android SDK 37 are required.

## Production checklist

- Change `API_BASE_URL` to an HTTPS production URL.
- Set `android:usesCleartextTraffic="false"`.
- Replace the development network security configuration.
- Create a release signing configuration.
- Set a unique production application ID if required.
- Disable HTTP logging in release builds; this is already controlled through `BuildConfig.DEBUG`.

## Feature coverage

The Android project includes discovery, authentication, password-reset requests, gem and review contribution flows, image upload, saved notes, profile settings, reporting, and mobile administrator screens for moderation, users, analytics and platform settings. The website remains the target of password-reset email links so users can securely complete the reset without placing reset tokens inside the app.
