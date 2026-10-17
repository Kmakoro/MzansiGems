# MzansiGems

<img src="website/assets/images/brand-icon.svg" alt="MzansiGems logo" width="88" />

**Discover the hidden side of your city.**

MzansiGems is a full-stack South African local-discovery platform with a responsive PHP website and a native Kotlin Android app. Users can find lesser-known places, share local gems, save favourites and post reviews. Both clients connect to **one PHP REST API and one MySQL database**, so an action in the Android app can also appear on the website.

**Live website:** https://vutivivukeya544.helioho.st/  
**Public API health:** https://vutivivukeya544.helioho.st/api/v1/health  
**API specification:** [OpenAPI 3.1](website/api/v1/openapi.yaml)  
**Full documentation:** [Project documentation](docs/PROJECT_DOCUMENTATION.md)

> **Deployment status (17 October 2026):** The site and API are hosted over HTTPS on HelioHost (Johnny). An Android APK built against the public API is distributed as a GitHub Actions artifact. Google sign-in, the matching user profile and saved-gem synchronisation between Android and the website were manually confirmed during the project walkthrough. These checks do not replace the complete test plan.

## Features

| Area | Features |
| --- | --- |
| Explore | Home page, featured gems, keyword search, trending categories, Surprise Me and Discover filters |
| Accounts | Registration, email/password login, Google sign-in, profile and account settings |
| Gems | Detailed place pages, location information, images, likes, sharing and user-owned edits |
| Community | Ratings and reviews, review likes and reports |
| Personalisation | Saved gems, private notes and user profile activity |
| Administration | Role-protected management of users, content moderation, reports, analytics and settings |
| Integration | Shared MySQL records accessible via both the website and Android REST client |

Some functions depend on deployment settings, moderation rules or a configured email delivery service; see the [test plan](docs/TEST_PLAN.md).

## Technology stack

| Component | Implementation |
| --- | --- |
| Website | PHP 8+, HTML, CSS, vanilla JavaScript |
| Backend | PHP REST API with bearer-token authentication |
| Database | MySQL / MariaDB, PDO prepared statements |
| Android | Kotlin, Jetpack Compose, Material 3, Navigation Compose, ViewModel/StateFlow |
| Android networking | Retrofit, OkHttp, Gson, Coil |
| Authentication | PHP session/Google OAuth for the website; Firebase Google identity linked to PHP API accounts for Android |
| Hosting | HelioHost Johnny, HTTPS, Plesk file management |
| CI / APK | GitHub Actions: PHP checks, MySQL integration checks, Android unit tests and APK builds |

### How both platforms share information

~~~text
Website (PHP pages) ------------------------------+
                                                  |
Android (Kotlin/Compose) -> HTTPS PHP REST API ----+-> MySQL / MariaDB
                       /api/v1/                       (shared accounts,
                                                        gems, reviews, saves)
Google OAuth -> website account/session
Firebase Google sign-in -> /auth/firebase -> same MySQL account/API session
~~~

The Android app does **not** use Firebase Firestore as its primary data store. Firebase verifies the Google identity; the PHP backend manages app accounts, API tokens and application data.

## Repository layout

~~~text
MzansiGems/
├── website/                  PHP website, API, schema and integration tests
│   ├── api/v1/               Shared REST API and OpenAPI specification
│   ├── assets/               Styles, scripts, brand SVGs
│   ├── config/               Environment-driven PHP configuration
│   ├── database/             Schema, seed data and migrations
│   └── tests/                Website/API integration checks
├── HiddenGemsApp/            Android Studio / Gradle project
│   ├── app/src/main/         Kotlin application and Android resources
│   ├── app/src/test/         Android unit tests
│   └── USER_GUIDE.md         Android end-user instructions
├── docs/                     Documentation, testing and deployment notes
├── .github/workflows/        CI and downloadable APK artifacts
└── README.md                 You are here
~~~

## Run the website locally

### Docker (easiest for a new, empty development database)

From the repository root:

~~~bash
cd website
docker compose up --build
~~~

Then visit http://localhost:8080 and check http://localhost:8080/api/v1/health.

The Compose setup creates and seeds an **empty** MySQL volume on its first startup. For Google sign-in, supply your own development OAuth/Firebase configuration as described in [website/README.md](website/README.md).

### XAMPP

Install PHP and MySQL through XAMPP, place the `website/` source under your Apache document root, create a **new empty** `hidden_gems` database and import `website/database/install.sql`. Set database connection details through the environment used by PHP, and ensure `website/uploads/` can be written by the web server.

**Important:** `install.sql` is a destructive initial schema/seed script. **Never run it against an existing live or populated database.** Back up data first, and apply only the migration appropriate to your existing schema.

See [website/README.md](website/README.md) for more local configuration details and [safe deployment instructions](docs/PHONE_ANYWHERE_DEPLOYMENT.md) for an existing installation.

## Build and run Android

Requirements: Android Studio, Android SDK 37 and a JDK 21 installation for the build workflow. Open `HiddenGemsApp/` in Android Studio and sync Gradle.

The Android project has an emulator-oriented default API URL. To point a build at the **live HTTPS server**, use:

~~~bash
cd HiddenGemsApp
./gradlew testDebugUnitTest assembleDebug -PAPI_BASE_URL=https://vutivivukeya544.helioho.st/api/v1/
~~~

The output is `HiddenGemsApp/app/build/outputs/apk/debug/app-debug.apk`. For local Google sign-in, configure Firebase according to [the Android README](HiddenGemsApp/README.md), including the package name and the SHA-1 of the certificate signing **your** APK.

A normal local APK may use a different signing certificate from GitHub's downloadable APK. If Google sign-in fails after installing a locally built APK, verify the certificate's registered SHA-1; do not change the live website or database to fix a signing mismatch.

### Download the phone-ready APK

1. Open [GitHub Actions](https://github.com/Kmakoro/MzansiGems/actions/workflows/android-ci.yml).
2. Open a successful run on `main`.
3. Under **Artifacts**, download `mzansi-gem-anywhere-apk`.
4. Extract the ZIP and install the contained APK on a supported Android device.

This is a **signed debug APK for testing**, not a Play Store release. The public-API artifact is built only when the repository variable `PUBLIC_API_BASE_URL` is configured, the HTTPS API health check passes and the required signing/Firebase setup is present. The separate `mzansi-gem-debug-apk` may be configured for emulator development and must not be assumed to work on mobile data.

Do not uninstall your working installation just to try another build. Back up important data and check package/signature compatibility first.

## Verification and quality

**Manually reported working on 17 October 2026:** website and Android Google sign-in, the correct profile name on both, updated branding/Google buttons and a saved gem created in Android appearing on the website.

**Automated checks defined in GitHub Actions:** Android unit tests and APK compilation, PHP syntax, seeded MySQL checks and shared-database API integration. Refer to the [Actions runs](https://github.com/Kmakoro/MzansiGems/actions) for the status of a specific commit; do not assume every manual scenario was automatically tested.

The complete outstanding test matrix and evidence to collect are in [docs/TEST_PLAN.md](docs/TEST_PLAN.md) and [docs/FINAL_SUBMISSION_CHECKLIST.md](docs/FINAL_SUBMISSION_CHECKLIST.md).

## Security and operating notes

- Keep database passwords, Google OAuth secrets, Firebase configuration, signing keys and live environment files **out of Git**.
- Use HTTPS and a dedicated database user. Review hosting backups before changing files or applying migrations.
- The live website's files are maintained through Plesk. **A GitHub merge does not automatically deploy website files to HelioHost.**
- Seeded demo accounts are **development only**. Disable/remove demo access and replace default credentials for publicly accessible systems.
- Configure reliable outbound email before relying on verification/password-reset delivery.
- Never publish user data or private authentication tokens in screenshots, logs or documentation.

## Documentation

- [Full project documentation](docs/PROJECT_DOCUMENTATION.md) — purpose, requirements, architecture, database, workflows, testing and maintenance
- [Website setup and configuration](website/README.md)
- [Android developer guide](HiddenGemsApp/README.md)
- [Android end-user guide](HiddenGemsApp/USER_GUIDE.md)
- [API reference (OpenAPI)](website/api/v1/openapi.yaml)
- [Phone-anywhere deployment and recovery](docs/PHONE_ANYWHERE_DEPLOYMENT.md)
- [Verification plan](docs/TEST_PLAN.md)
- [Submission checklist](docs/FINAL_SUBMISSION_CHECKLIST.md)
- [Task 2 compliance mapping](TASK2_COMPLIANCE.md)

---

**Project:** MzansiGems · **Audience:** South African residents and visitors discovering community-recommended places.