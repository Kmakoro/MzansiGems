# Mzansi Gem Android App - User Guide

## Sign in
You can create an account with email and password, sign in with email/password, or use **Continue with Google** after Firebase Google Authentication is configured.

## Discover gems
Use Home or Discover to browse hidden places. Search and filter by city, vibe, budget and activity type.

## Save, like and review
Signed-in users can save and like gems, keep private notes, and create/edit/delete their own reviews.

## Share a gem
Use Share to submit a place and optional images. The submission is sent through the same PHP REST API used by the website and stored in the same MySQL database.

## Profile and settings
Profile > Settings lets you edit profile information, notification/privacy preferences, password, logout and account deletion.

## Help & Support
The Settings screen includes a Help & Support section with quick guidance and troubleshooting.

## Google SSO
The Android app uses Firebase Authentication for Google identity only. After Google/Firebase authenticates the user, the Firebase ID token is sent to the PHP API. The API links it to the matching MySQL user and returns the normal Mzansi Gem bearer token. Website and Android therefore continue to share the same account and data.

## Real phone
For local testing, put the phone and computer on the same Wi-Fi. Use the computer LAN IP in API_BASE_URL. Do not use 10.0.2.2 on a physical phone.

Example with XAMPP:
```bash
./gradlew assembleDebug -PAPI_BASE_URL=http://192.168.1.20/website/api/v1/
```

Example with Docker on port 8080:
```bash
./gradlew assembleDebug -PAPI_BASE_URL=http://192.168.1.20:8080/api/v1/
```
