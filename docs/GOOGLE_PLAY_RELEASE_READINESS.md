# MzansiGems — Google Play release readiness

Reviewed: 12 November 2026. This is a **pre-release audit**, not a claim that the app is ready to publish.

## Already configured (verified in source)

- Android application ID: `za.co.hiddengems.app`; `minSdk 23`, `compileSdk 37`, `targetSdk 37`.
- Target SDK meets Google Play's August 2026 new-app minimum of API 36 (see official [target API policy](https://support.google.com/googleplay/android-developer/answer/11926878)).
- Main Android network security configuration blocks cleartext HTTP; the public API URL in `HiddenGemsApp/gradle.properties` uses HTTPS.
- Existing CI checks Android unit tests, a **debug APK**, PHP syntax and database integration. Debug APK success does **not** prove a release App Bundle is signed.
- The app has an in-app **Delete account** action; its operation and an external web deletion route still require policy verification.

## Before publishing — required work

- [ ] **Release signing and AAB.** Generate and securely back up a separate upload key. Configure release signing through Android Studio's *Generate Signed App Bundle / APK* flow. Never commit the keystore, passwords, `firebase.properties`, or private credentials. Choose **Android App Bundle (AAB)** and test upload to an internal Play testing track. Existing CI artifacts are debug APKs only.
- [ ] **Verify the release API endpoint.** The Gradle script has a fallback to `http://10.0.2.2/website/api/v1/` for local debug. Confirm the final release build uses `https://vutivivukeya544.helioho.st/api/v1/` and does not contain the emulator fallback. Do not ship a release with a localhost/LAN backend.
- [ ] **Release Google sign-in.** Register the release/app-signing certificate SHA-1 and SHA-256 in Firebase/Google Cloud and test Google sign-in in a Play-installed build. Existing debug SHA certificates do not automatically cover release installs.
- [ ] **HTTPS Android App Links.** `AndroidManifest.xml` currently declares `hiddengems.local` and `10.0.2.2` as verified web-link hosts; the production HelioHost domain is not in that filter. Decide whether website reset/verification links should open Android. If yes, register the production HTTPS host and publish a matching `/.well-known/assetlinks.json` for the **Play App Signing** certificate, then test on a real device. Custom `mzansigem://` links remain a separate route.
- [ ] **Sensitive local backup.** `AndroidManifest.xml` sets `android:allowBackup="true"`. Audit the session/token storage and exclude authentication credentials from cloud/device transfer, or turn backup off if appropriate; test restore behaviour.
- [ ] **Account deletion policy.** The app supports registration and has an in-app Delete account action. Google Play also requires a publicly accessible account-deletion web resource; verify the website provides a functional, discoverable request path and enter its URL in Play Console. Review what user records are deleted or legitimately retained. [Official guidance](https://support.google.com/googleplay/android-developer/answer/13327111).
- [ ] **Privacy and Data safety.** Publish an accurate privacy policy, complete Play Console's Data safety form, and disclose collection/use of identifiers, email/profile information, user-generated content, and any authentication/analytics data as applicable. Audit actual SDK activity rather than guessing.
- [ ] **Release versioning.** Current `versionCode = 1`, `versionName = "1.0.0"`; increment `versionCode` for subsequent uploads.
- [ ] **Final device tests.** Test a signed internal-track install on a physical device, Google sign-in, ordinary login, password reset/deep links, registration, likes/saves sync, browsing, offline Retry (Discover and details), account deletion, and restoration of network access. Review startup, accessibility, crashes and performance.
- [ ] **Play Console assets.** Confirm ownership of package ID, app icon, feature graphic, screenshots, support contact, content rating, category, audience, ads declaration, store text and release notes.

## How to make a test release bundle

1. Pull the approved `main` branch and verify `API_BASE_URL` points to the production HTTPS endpoint.
2. In Android Studio select **Build → Generate Signed App Bundle / APK → Android App Bundle**.
3. Create or select a **separate** upload key using local secure storage; do **not** send it to ChatGPT or commit it.
4. Build a release AAB; install through Play Console's **Internal testing** track, which provides the correct Play signing context.
5. Complete the above checks before a production rollout.

Do not assume a passing debug build or working emulator automatically means the release is Play-ready.
