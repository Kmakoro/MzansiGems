# Final assessment: authentication and performance evidence

## Changes under review
- Central policy: `website/includes/password_policy.php` (minimum 12 characters, 1024-byte cap and known-common-password rejection).
- Server enforcement: `website/api/v1/controllers.php` for API registration, login, password reset, change password; `website/register.php`, `website/login.php`, `website/reset-password.php`, `website/settings.php` for website paths.
- Android mirrors registration/reset validation in `AuthValidation.kt`; **server validation remains authoritative**.
- A verified legacy password that fails the current policy cannot create a new session and displays a password-reset message. Existing bearer/remember tokens are revoked when that weak login is detected. **Existing already-active PHP sessions are not automatically revoked**; an explicit database-backed `password_reset_required` flag and middleware enforcement are needed for a complete migration of all legacy sessions.
- Shared IP+email attempt key: up to 8 incorrect attempts per 15-minute window. This is intentionally a small, demonstrable control; production-grade abuse protection also needs proxy-aware IP handling, broad IP quotas and operational monitoring.

## Safe deployment order
1. Make an independent backup of the live database, website files and environment configuration.
2. Deploy migration `website/database/migrate_final_auth_throttle.sql` to the existing MySQL database (non-destructive). Do **not** run `install.sql` on live data.
3. Deploy the modified `website/` PHP code through HelioHost/Plesk. GitHub does **not** automatically deploy it.
4. Run `php website/tests/password_policy_test.php`, PHP lint, Android unit tests, and a staging end-to-end test.
5. Rebuild the Android APK with the public API base URL and distribute the **new** APK.
6. Exercise web and Android register/reset flows with `1234` and a valid passphrase. Capture HTTP 422 from direct `POST /api/v1/auth/register` and `POST /api/v1/auth/reset-password` (with a valid test reset token); do not post real tokens publicly.
7. Create a staging account with a deliberately weak *hashed* password and verify that logging in shows the reset-required message and does not issue a token. Confirm the account can request a reset link and sign in after setting a strong password.
8. Attempt 8 incorrect passwords for a disposable test account and confirm that a ninth attempt returns HTTP 429; then verify an independent account can still sign in. Do not test rate limits using production users.

## Concurrent login measurement (not yet performed)
Use disposable staging accounts and the same HTTP endpoint for both baselines. A tool such as k6 or Locust can run 1, 5, 10 and 20 concurrent virtual users for 30 seconds each. Do not repeatedly guess credentials: use one valid password per test account to keep throttling separate from throughput testing.

Capture: timestamp, concurrent users, total requests, successful logins, HTTP 401/429/500 count, timeouts, p50/p95 response times, hosting CPU/process limits, MySQL slow query log (if permitted), PHP error-log timing lines `MzansiGems auth_login outcome=... elapsed_ms=...`, and Android OkHttp request timings.

The PHP timing entries include database lookup and password verification, not only database time. To identify database versus password hashing versus hosting delay, add separate instrumentation around each step and correlate by non-secret request ID. Do not claim speedups without before/after data. Test responsibly against a staging copy, never load-test HelioHost shared production hosting without permission.

## Research and business evidence (requires client input)
- Cite dated interview notes, needs-analysis forms, competitor feature comparison, and the signed-off requirement corresponding to each developed feature. Do not invent a client quote.
- Possible income model to **validate with the client**: paid promoted placements by participating local businesses; discovery remains free to users. Confirm pricing, payer, moderation and disclosure before calling it a chosen model.
- Android-specific demonstration: sign in on Android, discover and save a gem while away from a desktop, then show that saved gem under the same account on the responsive website. This demonstrates cross-platform data continuity; user research must establish the client value.

## Lecturer demonstration checklist
1. Explain whether `1234` was entered through registration, reset, or seeded data, supported by database audit/deployment records.
2. Show the central policy function, direct API rejection and web+Android registration/reset UI.
3. Show the reset-required message and explain the limitation regarding existing active sessions.
4. Show staging concurrency results and request timing evidence; do not present unrun tests as completed.
5. Demonstrate repeated-guess throttling and two legitimate independent accounts logging in.
6. Demonstrate one client need mapped to a built feature and a validated revenue hypothesis.
