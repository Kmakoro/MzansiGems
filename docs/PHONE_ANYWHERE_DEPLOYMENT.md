# Make MzansiGems Android work on phones anywhere

## What is required

The Android app and PHP website already use the **same PHP REST API + MySQL schema**. For phones on any Wi-Fi or mobile network, the server must have a **public HTTPS address**. `localhost`, `127.0.0.1`, `10.0.2.2`, and `192.168.x.x` are not public endpoints.

The final architecture is:

```text
Android (any supported Android phone on Wi-Fi/mobile data)
  -> https://YOUR-DOMAIN.example/api/v1/
       -> PHP backend -> ONE MySQL database
Website https://YOUR-DOMAIN.example/
       -> same PHP backend/database
Firebase Authentication -> Google identity ONLY -> PHP /auth/firebase -> same MySQL users
```

The current Android code requires Android 6.0+ (API 23+) and Google Play services for the Credential Manager Google flow. iOS devices require a separate app.

## First repair the local XAMPP database mismatch

The local error `Table 'hidden_gems.gems' doesn't exist` means PHP is connected to the `hidden_gems` database but the `gems` table is missing *there*. This is not fixed by changing the Android app.

1. Back up your existing databases in phpMyAdmin before changing anything.
2. Open `http://localhost/phpmyadmin/`.
3. Expand the **hidden_gems** database and check whether both `users` and `gems` exist. If `gems` is missing, check whether a second database, such as `hidden_gems_backend`, contains the expected `users` and `gems` tables.
4. The website uses `DB_NAME` from its Apache/PHP environment, falling back to `hidden_gems`, in `website/config/config.php`. **Both the website and API must use the same DB_HOST/DB_PORT/DB_NAME/DB_USER/DB_PASS**.
5. If an existing database has correct tables and your current data, point Apache's PHP environment at *that database*, then restart Apache. Do not simply rename database references in many PHP files.
6. If this is a new, empty installation, use phpMyAdmin to import `website/database/install.sql` into a clean local MySQL instance. **WARNING:** The script starts with `DROP TABLE IF EXISTS`; importing it into a populated database destroys existing tables/data. Never import it into a database containing data you need.
7. To inspect from the Mac Terminal without modifying anything, use:
   ```bash
   cd /Applications/XAMPP/xamppfiles/htdocs/MzansiGems
   /Applications/XAMPP/xamppfiles/bin/php website/scripts/check_database.php
   ```
8. Test `http://localhost/MzansiGems/website/api/v1/health` (or the matching XAMPP URL for your actual project folder). It should report `"status":"healthy"`. Then test `index.php`.

Note that `website/.env.example` is a **template**. The PHP app reads server environment variables with `getenv()`; merely putting values into an `.env` file will not configure PHP automatically.

## Production deployment

Choose PHP/MySQL hosting with a fixed public domain or subdomain, HTTPS/TLS, suitable PHP extensions (PDO MySQL, cURL, mbstring, fileinfo), persistent MySQL, and persistent uploads. A Mac running XAMPP is only a development server; turning off the Mac would otherwise take the API offline.

1. Back up your local MySQL database via phpMyAdmin's Export feature.
2. Provision one MySQL database on the host. Import your existing schema/data into it, rather than running destructive `install.sql` over user data. For older versions, apply the reviewed migration `website/database/migrate_task2_compliance.sql` after backing up.
3. Deploy the PHP `website/` folder to the hosting document root.
4. Configure server environment variables (hosting panel/Apache configuration; keep credentials outside public GitHub):

   ```text
   APP_ENV=production
   APP_URL=https://YOUR-DOMAIN.example
   DB_HOST=<database-host>
   DB_PORT=3306
   DB_NAME=<one-shared-database>
   DB_USER=<hosting-database-user>
   DB_PASS=<hosting-database-password>
   FIREBASE_WEB_API_KEY=<the Firebase Web API key for project mzansigems>
   ```

   The `FIREBASE_WEB_API_KEY` is the Firebase project *web API key*, **not** your Google Web Client ID, service-account private key, or GitHub signing key. The API verifies Firebase ID tokens with Firebase's Identity Toolkit endpoint.
5. Configure the website's Google OAuth client ID/secret separately if you also use Google login on the website. Update allowed callback/redirect URLs for your new domain. Do not use localhost callbacks in production.
6. Ensure the host can write `uploads/` and that private configuration files cannot be served over HTTP.
7. Open `https://YOUR-DOMAIN.example/api/v1/health` in a browser on another network; it should return a successful JSON health response. Also test `/api/v1/gems`.
8. Enable and verify HTTPS before handing out an Android build.

## GitHub Actions: APK for any supported Android phone

With the production API working:

1. Open GitHub repository **Settings > Secrets and variables > Actions > Variables**.
2. Click **New repository variable**.
3. Name: `PUBLIC_API_BASE_URL`.
4. Value: your actual HTTPS URL **ending with a slash**. Example: `https://YOUR-DOMAIN.example/api/v1/` (not `localhost`).
5. Save. Go to **Actions > Mzansi Gem CI > Run workflow** on `main`, or trigger a new push.
6. GitHub checks your public endpoint's JSON health response before building the phone APK.
7. In the successful run, download `mzansi-gem-anywhere-apk` under **Artifacts**. Extract the ZIP and install the APK on your Android phone.

The existing `mzansi-gem-debug-apk` artifact is the **emulator/development build**. Without a configured `PUBLIC_API_BASE_URL`, it still points to `10.0.2.2` and is **not** suitable for phones on arbitrary networks. **Do not use it to demonstrate production connectivity**.

Debug APKs use the Firebase-registered debug signing certificate stored as a GitHub secret. This is for coursework testing. A public release should use a separately managed release key.

## Verify the shared database in production

Use **the same** database for the deployed PHP site and REST API. On a real Android phone:

1. Sign in with Google. Firebase Auth should show the user; MySQL's `users` table should contain the corresponding user linked by `firebase_uid`.
2. Edit your profile or save a gem on Android.
3. Reload the website while signed in as the same account. The same change should appear.
4. Create or review a gem on the website and refresh Android Discover.
5. Test the phone once on Wi-Fi and once on cellular/mobile data.

Automated GitHub CI already verifies that API profile/settings/likes/saves share the website MySQL database in a **test environment**; these final steps verify your **real deployment**.

## Troubleshooting

- **Website error: table hidden_gems.gems doesn't exist:** the selected database lacks its schema; check phpMyAdmin and server DB_NAME, do not drop populated tables.
- **API Offline on physical phone:** verify the APK was built from `mzansi-gem-anywhere-apk` with a public HTTPS API URL.
- **Google sign-in is unavailable:** confirm SHA-1 + the Firebase GitHub secrets + public PHP `FIREBASE_WEB_API_KEY`, and inspect API logs.
- **Firebase login authenticates but no MySQL session:** verify `firebase_uid` migration and PHP backend key.
- **Website and app show different data:** check the two clients' actual PHP API hosts and database settings; don't use two separate MySQL servers.
