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
4. For development you may use an emulator, but for the final Task 2 verification connect a physical Android phone (Android 6.0/API 23 or later).
5. Run `app`.

The default emulator API URL is defined in `app/build.gradle.kts` as:

```text
http://10.0.2.2/website/api/v1/
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

The first wrapper run downloads Gradle 9.4.1. JDK 21 and Android SDK 37 are required.

## Production checklist

- Change `API_BASE_URL` to an HTTPS production URL.
- Set `android:usesCleartextTraffic="false"`.
- Replace the development network security configuration.
- Create a release signing configuration.
- Set a unique production application ID if required.
- Disable HTTP logging in release builds; this is already controlled through `BuildConfig.DEBUG`.

## Feature coverage

The Android project includes discovery, authentication, password-reset requests, gem and review contribution flows, image upload, saved notes, profile settings, reporting, and mobile administrator screens for moderation, users, analytics and platform settings. The website remains the target of password-reset email links so users can securely complete the reset without placing reset tokens inside the app.

## Task 2: Firebase Google SSO

The app uses Firebase Authentication only for Google sign-in. After Firebase authenticates the Google account, the Android app sends the Firebase ID token to `POST /api/v1/auth/firebase`. The PHP API verifies the token, links or creates the matching MySQL user, and returns the same bearer token used by normal login. Website and Android therefore share the same account/data.

Copy:

```text
firebase.properties.example -> firebase.properties
```

Fill in:

```text
FIREBASE_API_KEY=...
FIREBASE_APP_ID=...
FIREBASE_PROJECT_ID=...
GOOGLE_WEB_CLIENT_ID=...
```

Also set this on the PHP server:

```text
FIREBASE_WEB_API_KEY=...
```

In Firebase Console, register package `za.co.hiddengems.app`, add the debug/release SHA-1 values, and enable Google Authentication.

## Run on a real Android phone

The final Task 2 build should be tested on a real phone.

1. Put phone and computer on the same Wi-Fi.
2. Start XAMPP/Apache or Docker.
3. Find the computer LAN IP.
4. Use that IP for `API_BASE_URL`.

XAMPP:

```bash
./gradlew assembleDebug -PAPI_BASE_URL=http://192.168.1.20/website/api/v1/
```

Docker:

```bash
./gradlew assembleDebug -PAPI_BASE_URL=http://192.168.1.20:8080/api/v1/
```

`10.0.2.2` is for the emulator only.

## Tests and CI

Run locally:

```bash
./gradlew testDebugUnitTest assembleDebug
```

GitHub Actions runs the same unit tests and APK build automatically using `.github/workflows/android-ci.yml`.

See `USER_GUIDE.md` for user instructions.


## APK for phones on any network

A physical Android phone **cannot** reach the emulator-only default `10.0.2.2`. For an app that works over Wi-Fi and mobile data from any location, first host the existing PHP website/API and single shared MySQL database at a public HTTPS domain.

Set the GitHub Actions **repository variable** `PUBLIC_API_BASE_URL` to the public PHP REST API address, such as `https://YOUR-DOMAIN.example/api/v1/`. The workflow checks the API health endpoint and builds a separately labelled `mzansi-gem-anywhere-apk` artifact when the variable exists. It continues producing `mzansi-gem-debug-apk` for emulator-only development.

For the XAMPP missing-table error, safe database checks, PHP Firebase server configuration, and full internet hosting steps, see [PHONE_ANYWHERE_DEPLOYMENT.md](../docs/PHONE_ANYWHERE_DEPLOYMENT.md). Never import `website/database/install.sql` into a populated database because it drops tables.
