# Task 3: Mandatory password reset for legacy accounts — controlled rollout

**Status: review / staging only.** Do not upload PHP from this branch to HelioHost until the SQL migration is applied and verified, email recovery is confirmed, and a database backup has been made. This change is separate from the already deployed PR #27 API login throttle.

## Why this is necessary

New and reset passwords already follow the shared 15+ character policy (uppercase, lowercase, number, allowed symbol, nonpredictable patterns). Existing database rows contain **password hashes**, not plaintext passwords; it is impossible to know which older passwords are weak just by reading a hash. Consequently, the code identifies weak passwords **after successful password verification at the next email/password login**. An administrator may also flag an account explicitly with the database column following appropriate account-recovery procedures.

## Behaviour

1. A correct password that meets current rules works as before, unless the account was already flagged for mandatory reset.
2. A correct legacy password that fails current rules **does not create a login session or API token**.
3. The user is flagged (`users.password_reset_required=1`) and their existing Android API tokens and long-lived website remember tokens are revoked.
4. Existing website session cookies no longer authorize access while flagged; Android token checks also reject flagged users.
5. Website login provides an explicit **Reset my password** link. Android API returns HTTP 403 with instructions to use **Forgot password**; the Android login screen already provides that action.
6. The existing email reset mechanism sends a time-limited token. Only a **strong new password** accepted with a valid unused reset token clears the flag and restores sign-in. Password reset also invalidates old tokens.
7. Google login on both platforms is blocked while the persistent reset flag is set, to avoid bypassing the required reset. Unflagged Google-only accounts are unaffected.

The database migration itself does not lock users: new column defaults to `0`, and no existing password hashes are altered or examined.

## Deployment — do this only after review

1. Export and verify a full production SQL backup; back up all live PHP files and configuration.
2. **Check recovery access first**, especially administrator/seeded/demo accounts with non-deliverable addresses like `@mzansigem.local` or `@hiddengems.local`. Users with those addresses **cannot receive email password reset links**. Give those accounts a valid controlled email recovery route before enforcing mandatory resets; otherwise administrators and members may get locked out.
3. On a staging copy, confirm the currently working website and Android password-reset email reaches a **real mailbox**; test invalid/expired/reused links.
4. In phpMyAdmin, select the existing MzansiGems database, then run `website/database/migrate_legacy_password_reset.sql` **once**. It contains only `ALTER TABLE users ADD COLUMN password_reset_required ... DEFAULT 0`. Never run destructive `website/database/install.sql` on production.
5. Confirm `SHOW COLUMNS FROM users LIKE 'password_reset_required';` reports a non-null tinyint with default `0`.
6. Deploy *matching versions* of: `includes/password_policy.php`, `includes/functions.php`, `login.php`, `reset-password.php`, `settings.php`, `google-callback.php`, `api/v1/bootstrap.php`, and `api/v1/controllers.php`. The website and Android API already share the existing `includes/login_throttle.php`; **do not remove it**.
7. Smoke-test normal website+Android login with a **known-strong test account**; then a **disposable, email-recoverable legacy test account**. Confirm weak login returns a reset message (website and API HTTP 403), old tokens no longer work, a weak reset fails HTTP 422, a strong reset succeeds, reuse fails, and login works again.
8. Verify Google OAuth, admin access, appeal workflows, gem likes/saves, and login throttling still work. Save screenshots/video for lecturer evidence. Roll back PHP and database only with a planned, consistent recovery procedure if tests fail.

**Do not use a production user's password, weaken a real account, or load-test shared HelioHost.** Automated CI uses disposable local MySQL records only. The SQL migration has no rollback by default because the new column becomes application state once used; take backups and plan any rollback carefully.

## Tests / evidence

- PHP lint and shared server-side policy tests.
- Local MySQL/API integration verifies new strong registration; wrong guesses 401 then 429; a different account remains able to log in; weak verified legacy password is denied; persistent flag, API and remember token revocation; weak reset rejected; valid single-use strong reset clears flag; subsequent login succeeds.
- Android unit tests and debug APK compilation.
- Live hosting and physical-device verification **remain separate and must not be claimed without evidence**.

## Task 3 lecturer explanation

> Existing passwords cannot be audited from their hashes alone. We verify the current password during sign-in, reject accounts that still use weak credentials, revoke outstanding login credentials, and require a one-time email password reset to a strong password before granting a new session. The database migration adds a default-off flag without deleting user data. We document the real-world email recovery prerequisites, use isolated regression tests, and only deploy to production after backup and staged verification.
