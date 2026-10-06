# Mzansi Gem Android App - User Guide

Mzansi Gem helps users discover, save, review and share lesser-known places around South Africa. The Android app and website use the same PHP REST API and MySQL database, so changes made in either client are stored in the same system.

## 1. Home
The Home screen shows featured gems, search, categories and the Surprise Me feature. Tap a gem card to open its detail screen.

## 2. Discover
Use Discover to search and filter by:
- keyword;
- city;
- vibe;
- budget;
- activity type; and
- sorting option.

Tap a result to see its description, location, images, reviews, likes and save state.

## 3. Create an account
Open Sign up and enter:
- full name;
- email;
- city;
- password; and
- password confirmation.

Invalid values are rejected before submission. If email verification is enabled, the app tells you to verify the email before signing in.

## 4. Sign in
Users can sign in with email/password or **Continue with Google**.

Google sign-in uses Firebase Authentication only for identity. The app sends the Firebase ID token to the PHP API, which links it to the existing MySQL user and creates the normal Mzansi Gem API session. This keeps the Android app and website synchronized.

## 5. Forgot/reset password
Tap **Forgot password?** on the sign-in screen. The email may contain:
- a website reset link; and
- an Android link beginning with `mzansigem://auth/reset-password`.

Opening the Android link launches the app and displays the password-reset form.

## 6. Email verification
When email verification is enabled, the verification email can also contain an Android link beginning with `mzansigem://auth/verify-email`. Opening it launches the app and verifies the account through the API.

## 7. Share a Mzansi Gem
Open **Share** and complete the required fields:
- place name;
- description;
- location;
- city;
- one to three vibes;
- budget level;
- activity type;
- operating hours;
- rating; and
- up to three images.

The form validates required fields and length/rating rules before submission. New or edited content may return to moderation.

## 8. Saved gems
Tap Save on a gem to add it to **Saved**. You can add a private note and remove the gem later.

## 9. Reviews and likes
Open a gem to submit a rating/review. Users can edit or delete their own reviews. Likes and review likes are stored through the shared API.

## 10. Profile
Profile shows posted gems, saved gems, reviews, level/points and administrator access where applicable.

## 11. Settings
Profile > Settings contains:
- full name, email, city and bio;
- notification preferences;
- privacy preferences;
- password change;
- Help & Support;
- logout; and
- account deletion.

## 12. Help & Support
The Settings screen includes quick help for Discover, Share, Saved, Google sign-in, synchronization and physical-phone development.

## 13. Real Android phone
For local development:
1. Put the phone and development computer on the same Wi-Fi.
2. Start XAMPP/Apache or Docker.
3. Find the computer LAN IP, for example `192.168.1.20`.
4. Build the debug app with that IP.

XAMPP example:

```bash
./gradlew assembleDebug -PAPI_BASE_URL=http://192.168.1.20/website/api/v1/
```

Docker example:

```bash
./gradlew assembleDebug -PAPI_BASE_URL=http://192.168.1.20:8080/api/v1/
```

Do not use `10.0.2.2` on a physical phone. That address is for the Android emulator.

## 14. Troubleshooting
- **API Offline:** verify Apache/Docker is running and the phone can reach the computer IP.
- **Google SSO not configured:** complete `firebase.properties`, Firebase Authentication, and the PHP `FIREBASE_WEB_API_KEY`.
- **Google account picker fails:** verify the Android package name and SHA-1 in Firebase.
- **Website changes not appearing in Android:** confirm both clients point to the same PHP API/MySQL database.
- **Android changes not appearing on the website:** confirm the Android `API_BASE_URL` points to the same server/database used by the website.
