# Task 2 Compliance Checklist - Mzansi Gem

## Shared mobile/website architecture
The Android app and website use the same PHP REST API and MySQL database. Firebase is used only for Google identity on Android. After Google/Firebase authentication, the PHP API links the Firebase UID to the existing MySQL user and issues the normal Mzansi Gem bearer token. Gems, reviews, saved items, settings, moderation and profiles therefore remain synchronized between Android and the website.

## Task 2 coverage

### 1. App runs on a mobile device
- Native Android app using Kotlin, Jetpack Compose and Material 3.
- Minimum Android SDK 23.
- Debug-only LAN HTTP policy supports a physical phone during local XAMPP/Docker testing.
- Release/main network policy blocks cleartext traffic and expects HTTPS.
- Real-device build instructions are in the README and USER_GUIDE.

### 2. Sign in
- Email/password registration and login.
- Passwords are hashed on the PHP server with `password_hash()` and verified with `password_verify()`.
- Optional email verification is supported before an Android API session is issued.
- Password-reset links support both website and Android app deep links.
- Google SSO is implemented with Firebase Authentication and Android Credential Manager.
- `POST /api/v1/auth/firebase` verifies the Firebase token and links/creates the matching MySQL account.

### 3. Settings
Android settings include profile information, notification/privacy preferences, password change, logout, account deletion, and Help & Support.

### 4-5. API creation and Android API integration
- Shared REST API in `website/api/v1/`.
- OpenAPI documentation in `website/api/v1/openapi.yaml`.
- Android uses Retrofit + OkHttp with bearer-token authentication.
- Website and Android both operate on the same MySQL data.

### 6. Database structure and table design
- Relational MySQL schema with primary keys, foreign keys, unique constraints and indexes.
- `firebase_uid` links Firebase identity to the existing `users` table.
- Fresh `install.sql` seed data contains at least 10 records in every Task 2 database table.
- Existing installations have migration/seed scripts.

### 7. User interface
- Material 3 Compose UI.
- Consistent navigation, forms, loading/error feedback and labelled controls.
- Login, registration, password-reset and Share-a-Gem input validation prevent invalid submissions.

### 8. User documentation
- In-app Help & Support.
- `HiddenGemsApp/USER_GUIDE.md`.
- `docs/TEST_PLAN.md`.
- `docs/FINAL_SUBMISSION_CHECKLIST.md`.

### 9. GitHub, README and automated testing
- Multiple GitHub commits and pull-request workflow.
- `.github/workflows/android-ci.yml` automatically:
  - runs Android JUnit tests;
  - builds the debug APK;
  - uploads the APK as an Actions artifact;
  - checks PHP syntax;
  - creates and seeds MySQL;
  - verifies every required table has at least 10 records; and
  - verifies the Firebase identity column exists.
- Unit tests cover login, registration, password-reset and Share-a-Gem validation.

## External evidence still requiring the team's real accounts/devices
Source code cannot truthfully create this evidence on its own:
- Firebase project values and Android SHA-1 registration.
- A successful Google sign-in using the team's real Firebase project.
- Physical Android phone test evidence.
- Hosted API/database evidence if required by the lecturer.
- Azure DevOps work-item/board/history evidence from the actual project process.
- Team meeting evidence.
- Final professional demonstration video.
