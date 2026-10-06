# Mzansi Gem - PHP/MySQL Full-Stack Website

A complete responsive implementation of the supplied Mzansi Gem prototype using PHP 8+, MySQL, HTML, CSS, and vanilla JavaScript.

## Included functionality

- Landing page with hero search, trending categories, featured gems, and Surprise Me
- Registration, optional email verification, login, 30-day Remember Me, logout, password reset tokens, and optional Google OAuth
- Discovery feed with keyword, city, vibe, budget, and activity filters
- Gem detail pages with gallery, location link, likes, favourites, reviews, review likes, and reports
- Share-a-gem form with validation and up to three image uploads
- Saved gems with private personal notes and removal confirmation
- User profile tabs for posted gems, saved gems, and reviews
- Edit/delete flows for user gems and reviews
- Profile, notification, privacy, password, logout, and account deletion settings
- Admin dashboard, user management, content moderation, reports, analytics, and platform settings
- CSRF protection, prepared PDO queries, password hashing, upload MIME validation, role checks, and secure remember-me tokens

## Demo accounts

- Administrator: `admin@mzansigem.local` / `Admin@123`
- User: `user@mzansigem.local` / `User@123`

Change the demo passwords before public deployment.

## Option A: Run with Docker

1. Install Docker Desktop.
2. Open a terminal in this folder.
3. Run:

```bash
docker compose up --build
```

4. Open `http://localhost:8080`.

The MySQL database is created and seeded automatically on the first run. To reset all data:

```bash
docker compose down -v
docker compose up --build
```

## Option B: Run with XAMPP

1. Copy the folder to `C:\xampp\htdocs\mzansi-gem`.
2. Start Apache and MySQL in XAMPP.
3. Open phpMyAdmin and import `database/install.sql`.
4. Confirm these defaults in `config/config.php`:

```text
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=hidden_gems
DB_USER=root
DB_PASS=
```

5. Open `http://localhost/mzansi-gem`.
6. Ensure `uploads/` is writable by PHP.

For an existing installation created before the rename, import `database/migrate_to_mzansi_gem.sql` once to update the stored site name and demo account emails.

## Google sign-in

Google authentication is implemented but requires your own OAuth credentials.

1. Create a Google OAuth web application.
2. Add this authorised redirect URI:

```text
http://localhost:8080/google-callback.php
```

For XAMPP, use:

```text
http://localhost/mzansi-gem/google-callback.php
```

3. Set `GOOGLE_CLIENT_ID` and `GOOGLE_CLIENT_SECRET` in your environment or `.env` used by Docker Compose.

Without credentials, the Google button safely returns a configuration message and standard email/password login remains fully functional.


## Transactional email

Set `MAIL_FROM` and configure the server's PHP `mail()` transport to send password-reset and email-verification messages. During development, the generated links are displayed on screen so the flows can be tested without an SMTP server. For production, connect a reliable transactional email provider.

## Production checklist

- Change all demo passwords and database credentials.
- Set `APP_ENV=production` and configure `APP_URL` with HTTPS.
- Use a dedicated MySQL user with minimum privileges.
- Configure a transactional email provider for password reset emails.
- Store uploaded files in managed object storage if scaling beyond one server.
- Add image processing and malware scanning for public uploads.
- Configure backups, logging, rate limiting, and a Content Security Policy.

## Main project structure

```text
admin/                 Administrator pages
assets/css/            Responsive site and admin styling
assets/js/             UI interactions and previews
config/                Application and PDO configuration
database/install.sql   Schema and seeded prototype data
includes/              Bootstrap, security helpers, layouts, cards
uploads/                User-uploaded gem images
*.php                   Public pages and form actions
```

## Notes

The design follows the warm orange/peach gradient, cream background, rounded cards, filter panels, bottom mobile navigation, profile dashboard, and admin sidebar shown in the supplied prototype. External seed photos and Chart.js use internet-hosted resources; uploaded user photos are stored locally.

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

## Task 2 Android additions

### Firebase Google SSO

The Android app uses Firebase Authentication for Google identity only. Application users, gems, reviews, settings and all other data still flow through the PHP REST API into the same MySQL database used by the website. This keeps mobile and website changes synchronized.

Setup:
1. Create/open a Firebase project.
2. Add Android package `za.co.hiddengems.app`.
3. Add the Android SHA-1 fingerprint.
4. Enable **Authentication > Sign-in method > Google**.
5. Copy `HiddenGemsApp/firebase.properties.example` to `HiddenGemsApp/firebase.properties`.
6. Add `FIREBASE_API_KEY`, `FIREBASE_APP_ID`, `FIREBASE_PROJECT_ID`, and `GOOGLE_WEB_CLIENT_ID`.
7. Set `FIREBASE_WEB_API_KEY` on the PHP server.
8. For an existing database, run `website/database/migrate_task2_compliance.sql` and then `website/database/task2_seed_additions.sql` once.

### Run on a real Android phone

For Task 2, test the final app on a physical Android device.

1. Put the phone and development computer on the same Wi-Fi.
2. Find the computer LAN IP, for example `192.168.1.20`.
3. Make sure Apache/XAMPP or Docker is reachable from the phone.
4. Build with the real LAN API address.

XAMPP example:

```bash
cd HiddenGemsApp
./gradlew assembleDebug -PAPI_BASE_URL=http://192.168.1.20/website/api/v1/
```

Docker example:

```bash
cd HiddenGemsApp
./gradlew assembleDebug -PAPI_BASE_URL=http://192.168.1.20:8080/api/v1/
```

Do not use `10.0.2.2` on a physical phone; that address is only for the Android emulator.

### Automated testing and APK build

`.github/workflows/android-ci.yml` runs `testDebugUnitTest`, builds the debug APK, and uploads the APK as a GitHub Actions artifact.

### User support

The Android Settings screen now contains **Help & Support**. A full guide is in `HiddenGemsApp/USER_GUIDE.md`, and rubric mapping is in `TASK2_COMPLIANCE.md`.

