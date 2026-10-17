# MzansiGems — Project Documentation

**Version:** 1.0 documentation snapshot  
**Date:** 17 October 2026  
**Repository:** https://github.com/Kmakoro/MzansiGems  
**Live website:** https://vutivivukeya544.helioho.st/  
**Public REST API:** https://vutivivukeya544.helioho.st/api/v1/  
**Application platforms:** Responsive web and native Android

> This document describes the implementation present in the project repository and clearly distinguishes **implemented features**, **manually confirmed behaviour** and **further evidence still required**. It is not a claim that every screen, security control or deployment path has passed a full acceptance test.

## 1. Executive summary

MzansiGems is a South African community discovery platform designed to help residents and visitors find lesser-known local attractions, food spots, cultural experiences and other interesting places. The solution consists of a PHP/MySQL website, a Kotlin Android application and a shared REST API. Users can explore recommended locations, manage accounts, submit reviews, save places for later and contribute new gems. Moderators and administrators have role-restricted management functions.

The central design decision is the **single shared application database**. The web experience and Android app do not maintain separate records of users, saved places or reviews. Instead, the Android app communicates with PHP API endpoints, while the website uses the same MySQL data model. Google sign-in uses different frontend mechanisms on each platform but ultimately resolves to the same system account.

The website and HTTPS API are hosted on HelioHost Johnny, and a specially configured Android debug APK is built in GitHub Actions for internet access. On 17 October 2026, the user reported successful Google sign-in on both clients, correct profile names and cross-platform saved-gem synchronisation.

## 2. Problem statement and motivation

Local destinations can be difficult to discover if they are not well promoted or featured on larger travel platforms. MzansiGems addresses this problem with a community-based discovery service that combines place listings, search/filtering and contributions from local users.

**Project goals:**

1. Offer an accessible, responsive South African discovery website.
2. Provide an Android application with a consistent visual identity and core functions.
3. Store the same users, gems, reviews and saves across both platforms.
4. Support ordinary email/password access and Google identity.
5. Apply validation, permissions and moderation to user-generated content.
6. Provide documented setup, automated checks and a demonstrable deployment.

## 3. Users and functional scope

| User type | Typical capabilities |
| --- | --- |
| Visitor | Browse public home, featured content, discover search and place details |
| Registered member | Sign in, edit profile, save gems, leave reviews, like content, share gems and manage personal data |
| Administrator | Open protected admin views, manage users, moderate submissions/reviews/reports and inspect statistics/settings |

The repository includes the following feature areas:

- **Home and discovery:** hero search, featured gems, categories, Surprise Me, keyword/city/vibe/budget/activity filters and sorting.
- **Accounts:** sign-up, login/logout, Google sign-in, profile/settings, password reset and optional email verification.
- **Gems:** detail screens, galleries, directions/location, likes, saved status, editing and reporting.
- **Community:** ratings, reviews, review likes and user-generated place submissions with images.
- **Administration:** dashboards, user moderation, content/report management and platform settings.
- **Mobile:** Android-native navigation, the same main feature areas and API-backed account data.

The repository contains application logic for these areas; individual edge cases remain subject to the acceptance tests in `docs/TEST_PLAN.md`.

## 4. Technology and design

| Layer | Technology | Purpose |
| --- | --- | --- |
| Web presentation | PHP templates, HTML, CSS and JavaScript | Responsive navigation, forms, public and authenticated screens |
| Server | PHP 8+ | Validation, security checks, website requests and REST API |
| Persistence | MySQL / MariaDB with PDO | Durable shared relational data |
| Mobile | Kotlin, Jetpack Compose and Material 3 | Native Android interface |
| Mobile state | ViewModel, StateFlow and DataStore | UI state and selected local preferences/session data |
| Mobile communication | Retrofit, OkHttp, Gson | HTTPS requests and JSON handling |
| Mobile images | Coil | Remote image loading |
| Google website identity | Google OAuth | Browser-based Google account login |
| Google Android identity | Firebase Authentication | Verify a Google identity before exchanging for an application API token |
| Source control and CI | GitHub and GitHub Actions | Collaboration, automated checks and APK artifacts |
| Public hosting | HelioHost Johnny with Plesk | PHP website/API and database service |

### 4.1 High-level architecture

~~~mermaid
flowchart LR
    Web["Web browser / PHP website"] --> PHP["PHP application"]
    Android["Android app / Kotlin"] --> API["HTTPS REST API /api/v1"]
    API --> PHP
    PHP --> DB[("Shared MySQL database")]
    GoogleWeb["Google OAuth"] --> Web
    GoogleMobile["Google / Firebase Auth"] --> Android
~~~

**Important distinctions:**

- Firebase Authentication is used for mobile Google identity, **not** as the project's main application database.
- The website uses browser/session-based authentication and its configured OAuth callback.
- The Android app obtains a PHP API bearer token and sends it to protected endpoints.
- The GitHub source repository and live Plesk files are separate; source updates do not automatically deploy to hosting.

### 4.2 Typical saved-gem data flow

~~~text
Member selects Save in Android
  -> Android Retrofit request
  -> POST /api/v1/gems/{id}/save with a valid bearer token
  -> PHP checks the user and updates MySQL saved_gems
  -> The member opens Saved on the website using the same account
  -> Website reads the same MySQL record
  -> The saved gem is visible in both places
~~~

This workflow was manually confirmed on 17 October 2026.

## 5. Relational database design

The initialization script is `website/database/install.sql`. It defines fourteen primary tables:

| Table | Main purpose |
| --- | --- |
| `users` | Member identity, roles, profiles and preferences |
| `email_verification_tokens` | Verification-token records |
| `remember_tokens` | Remember-me authentication tokens |
| `api_tokens` | Mobile/API bearer-token records |
| `password_resets` | Password recovery tokens |
| `gems` | Place listings and moderation/ownership fields |
| `gem_images` | Images associated with gems |
| `reviews` | User ratings and review content |
| `saved_gems` | User-to-gem saved relationships and private notes |
| `gem_likes` | Member likes on gems |
| `review_likes` | Member likes on reviews |
| `reports` | Community reports for moderation |
| `settings` | Application-level settings |
| `activity_log` | Recorded application actions |

Conceptually, members create gems and reviews; saved items and likes are relationships between members and content; an image belongs to a gem. Full column definitions, constraints and indexes are specified in the SQL script. The live production schema should be verified separately before presenting an ERD as production evidence.

**Database safety:** `website/database/install.sql` is for a **new, empty environment**. It can replace tables/data and must **never** be imported into an existing populated or hosted database. Use an appropriate reviewed migration and a verified backup for schema upgrades.

## 6. REST API

The PHP API implementation is in `website/api/v1/index.php` and `controllers.php`. The formal API description is in [OpenAPI 3.1](../website/api/v1/openapi.yaml).

Representative endpoints:

| Method | Path | Function |
| --- | --- | --- |
| GET | `/health` | Verify API and database availability |
| POST | `/auth/register` | Register an account |
| POST | `/auth/login` | Login with email/password |
| POST | `/auth/firebase` | Exchange Firebase identity for the shared application's API session |
| GET | `/auth/me` | Return authenticated account details |
| GET | `/categories` | Get filter categories |
| GET | `/gems` | Browse/search gem listings |
| GET | `/gems/{id}` | Retrieve gem details |
| POST | `/gems` | Submit a new gem |
| POST | `/gems/{id}/save` | Change saved status |
| PUT/PATCH/POST | `/gems/{id}/note` | Update a private saved-gem note |
| POST | `/gems/{id}/reviews` | Add a review |
| GET | `/saved` | View authenticated saved gems |
| GET/PUT/PATCH | `/profile` | View/update account information |
| POST | `/reports` | Submit a report |
| GET | `/admin/dashboard` | Administrator overview |

Additional routes implement likes, review edits, content deletion, settings, moderation and analytics. Some server routes include HTTP methods not enumerated in the OpenAPI excerpt; treat the PHP route definitions as the implementation source.

Public endpoint example:

~~~bash
curl --fail https://vutivivukeya544.helioho.st/api/v1/health
~~~

The health response includes a successful status when the API and SQL connection are available. Do not include bearer tokens, password reset links or private user data in demonstration screenshots.

## 7. Authentication and account linking

### 7.1 Website

The browser app supports email/password sessions and Google OAuth. The Google login pages are `website/login.php` and `website/register.php`; related OAuth processing is in `website/google-auth.php` and `website/google-callback.php`. Browser OAuth client ID/secret and the authorised callback URI must match the deployment's Google Cloud configuration.

### 7.2 Android

The app uses Firebase Authentication and Google Credential Manager. After a Google account is selected:

1. Firebase authenticates the Google identity and returns an ID token.
2. Android sends that token to `POST /api/v1/auth/firebase`.
3. PHP checks the token and finds or links the corresponding MySQL user.
4. PHP issues the API bearer token used for subsequent authenticated operations.

The Android package identifier is `za.co.hiddengems.app`. Successful Google sign-in requires the correct Firebase project configuration, web client identifier and SHA-1 fingerprint for the certificate **actually signing the installed APK**. A locally generated debug key may differ from GitHub Actions' configured signing key.

### 7.3 Synchronisation requirement

For a user's profile and saved gems to match across platforms, both clients must access the same backend/database and sign in to the same underlying account. Using Firebase does **not** mean gems or reviews are stored in Firebase.

## 8. Interface and branding

MzansiGems uses a warm peach/orange palette, rounded cards and high-contrast teal/orange wordmark. The current brand assets include:

- `website/assets/images/brand-icon.svg` — South African outline with an orange location pin;
- `website/assets/images/google-g.svg` — multicolour Google icon for browser authentication;
- `website/assets/images/food-drinks.svg` — updated food-category icon;
- `HiddenGemsApp/app/src/main/res/drawable/ic_launcher_foreground.xml` — Android brand mark; and
- `HiddenGemsApp/app/src/main/res/drawable/ic_google_g.xml` — Android Google icon.

On 17 October 2026, screenshots were reviewed showing the updated branding and aligned Google login buttons on the website and Android app. A full device-size accessibility review is not yet documented.

## 9. Local development and installation

### 9.1 Website via Docker

Prerequisites: Docker Desktop, internet access for image downloads and available ports.

~~~bash
git clone https://github.com/Kmakoro/MzansiGems.git
cd MzansiGems/website
docker compose up --build
~~~

Open `http://localhost:8080/`. A first-time empty MySQL volume is initialised by the mounted SQL seed script. This is for **development only**, not a way to restore/update hosted production data.

For XAMPP, use the matching Apache and MySQL services and configure PHP environment values from `website/.env.example`. PHP reads values using `getenv`; do not assume an arbitrary `.env` file is automatically loaded by XAMPP. Refer to [the website README](../website/README.md).

### 9.2 Android

Open `HiddenGemsApp/` in Android Studio. The code targets Android SDK 37 and supports Android API 23 or later.

~~~bash
cd HiddenGemsApp
./gradlew testDebugUnitTest assembleDebug -PAPI_BASE_URL=https://vutivivukeya544.helioho.st/api/v1/
~~~

Expected debug artifact: `app/build/outputs/apk/debug/app-debug.apk`.

Firebase values can be provided using the untracked `firebase.properties` file (see `firebase.properties.example`) or the supported Gradle/environment variables. Register the correct package and signing SHA-1 before testing Google login.

For emulator-only local development, `10.0.2.2` refers to the host machine; do not use it for an APK intended to run on a real phone away from the development computer.

## 10. Current public deployment and safe maintenance

The deployed website is reachable at `https://vutivivukeya544.helioho.st/` and the PHP API at `https://vutivivukeya544.helioho.st/api/v1/`. Hosting uses HelioHost Johnny with Plesk file management. The Android APK for general network access is built against that public API.

**Safe website change procedure:**

1. Identify exactly which source files need changing.
2. Back up the corresponding files and, for any database work, create a verified database backup.
3. In Plesk, upload a small staging ZIP into a separate temporary folder rather than unpacking an entire repository into the live site.
4. Replace **only** the reviewed application files in the website document root.
5. Leave live environment values, OAuth settings, database connections, upload data and other server-specific configuration untouched.
6. Check affected pages, the API health endpoint and both Google sign-in paths.
7. If a change fails, restore the backed-up files and diagnose before proceeding.

**GitHub and hosting are not connected by an automatic web deployment pipeline.** Updating the README or merging a pull request does not alter live PHP or database data.

A separate guide covers database recovery, public API configuration and networking: [Phone-anywhere deployment](PHONE_ANYWHERE_DEPLOYMENT.md).

## 11. Continuous integration and Android distribution

The workflow is `.github/workflows/android-ci.yml`. Its implementation includes:

- Android unit tests with Gradle;
- Android debug APK assembly;
- certificate/signature verification for configured Firebase signing on non-PR runs;
- PHP syntax checks;
- seeded MySQL table presence/count checks;
- shared-database REST integration tests;
- public HTTPS health validation when `PUBLIC_API_BASE_URL` is set; and
- artifact upload.

The workflow publishes `mzansi-gem-debug-apk` for debug/emulator development and, when conditions are met, `mzansi-gem-anywhere-apk` for a phone using the publicly hosted API. These artifacts are test/development APKs, **not** a signed release distributed through Google Play.

Workflows are path-filtered to application/workflow changes. A documentation-only commit may not automatically start this pipeline; see the [Actions history](https://github.com/Kmakoro/MzansiGems/actions) for the status of individual builds.

## 12. Verification record (17 October 2026)

The following is a **walkthrough record**, not a blanket statement that the entire platform was formally tested.

| Scenario | Evidence source | Status |
| --- | --- | --- |
| Website login/register Google button alignment | Screenshots supplied during walkthrough | Visually confirmed |
| Website header branding and colour | Screenshots supplied during walkthrough | Visually confirmed |
| Website Google sign-in and correct profile | User performed live flow and confirmed outcome | Passed (user-reported) |
| Android branding and Google button | Android screenshots supplied during walkthrough | Visually confirmed |
| Android Google sign-in and correct profile name | User performed flow and confirmed outcome | Passed (user-reported) |
| Save a gem on Android; find it on website | User performed cross-platform check | Passed (user-reported) |
| Android invalid input, sign-up and password-reset cases | Listed in test plan; not confirmed in this walkthrough | Not yet independently evidenced |
| Admin access, moderation and account deletion | Implemented in repository; not confirmed in this walkthrough | Not yet independently evidenced |
| Accessibility/performance/security penetration tests | No comprehensive evidence provided in this walkthrough | Not yet independently evidenced |
| CI tests and build | Workflow configured in repository; run logs are separate evidence | See GitHub Actions for run-specific results |

For formal submission, attach dated screenshots, device information, reproducible test steps, expected vs actual results and links to genuine CI runs.

See [the test plan](TEST_PLAN.md), [final submission checklist](FINAL_SUBMISSION_CHECKLIST.md), [Task 2 mapping](../TASK2_COMPLIANCE.md) and [Azure DevOps evidence guide](AZURE_DEVOPS_EVIDENCE.md).

## 13. Security, privacy and operational considerations

The source implements several foundational safeguards such as PDO prepared statements, password hashing, CSRF controls in website form handling, authenticated API tokens, role-based administrator checks, input validation and server-side upload checks.

Recommended operational checks before broader public use:

1. Keep OAuth client secrets, API keys, DB credentials, signing certificates and real `.env` values private.
2. Maintain HTTPS and restrict database access.
3. Remove/disable seed demo credentials and review user roles in production.
4. Verify backup restoration and test database migrations on a disposable copy first.
5. Set up reliable email delivery before relying on recovery/verification workflows.
6. Review rate limiting, audit logs, upload sanitisation, data retention, CSP and dependency patching.
7. Follow up any identified privacy, accessibility or security issues with a documented fix and regression test.

Implementation of selected controls does not certify the website as independently security-audited.

## 14. Known boundaries and possible enhancements

- **Android vs iOS:** The repository provides a native Android application. An APK cannot run natively on an iPhone; iOS would require a separate app or another supported distribution approach.
- **Deployment:** Plesk website changes are manual, and care is needed to keep hosting in sync with source control.
- **Email:** Production mail/SMTP delivery should be validated separately.
- **Testing:** User-reported cross-platform success is encouraging; complete functional, accessibility and device-matrix testing is still desirable.
- **Releases:** A production signing/release process and store distribution would be future work.
- **Possible features:** Accessibility refinements, stronger automated end-to-end tests, image optimisation, monitoring, search relevance improvements and more robust content moderation.

## 15. Conclusion

MzansiGems demonstrates a coherent full-stack approach to discovering and sharing South African places through two user interfaces. Its core architectural strength is the shared MySQL backend: website sessions and Firebase-based Android Google sign-in connect to the same application accounts, allowing content and saved places to synchronise. The October 2026 walkthrough confirmed the main authentication/branding improvements and a representative save-sync action. Remaining evidence should be collected against the test plan before describing the complete system as formally verified.

## 16. Project source and supporting guides

This documentation was grounded in the repository's PHP code, Android project, SQL script, GitHub workflow and existing guides, together with the reported manual checks:

- [Repository and current README](../README.md)
- [Website source/configuration](../website/)
- [Android source](../HiddenGemsApp/)
- [MySQL schema](../website/database/install.sql)
- [REST route definitions](../website/api/v1/index.php)
- [OpenAPI description](../website/api/v1/openapi.yaml)
- [GitHub Actions workflow](../.github/workflows/android-ci.yml)
- [Android user guide](../HiddenGemsApp/USER_GUIDE.md)
- [Test plan](TEST_PLAN.md)
- [Production/networking notes](PHONE_ANYWHERE_DEPLOYMENT.md)

**Documentation control:** When code, database structure or the hosting environment changes, update this file and the README together. Do not put credentials, user records or private identifiers in either document.