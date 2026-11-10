# MzansiGems — Suspension reasons, appeals and messaging

This feature is staged on the feature/suspension-appeals-messaging branch and is not deployed to the live website.

## Deploy to existing HelioHost / Plesk website

1. First make a FULL Plesk backup and a separate SQL export in phpMyAdmin.
2. Select the existing MzansiGems database in phpMyAdmin. Run ONLY database/migrate_suspension_appeals.sql. It adds two tables without dropping or modifying existing user records.
3. Deploy the updated PHP files from this branch together. The modified files are:
   - includes/bootstrap.php and includes/functions.php
   - includes/suspensions.php (new)
   - login.php and google-callback.php
   - suspension-appeal.php (new) and suspension-history.php (new)
   - profile.php, admin/users.php, admin/appeals.php (new), admin/index.php, includes/admin-header.php
   - api/v1/controllers.php
4. Test with a non-admin TEST account: suspend with a written reason, attempt password and Google login, view the reason, submit an appeal, reply as an administrator, reinstate, and confirm the account can log in again.
5. Check that the member's previous gems, saves, and reviews are unchanged. The prior messages should appear under Profile > Account review history.
6. Test Android as a separate platform: when suspended, it should show the API reason in a dialog and allow the member to open the website to appeal.

DANGER: database/install.sql contains DROP TABLE statements. It is for a new TEST DATABASE only; NEVER run it on your live HelioHost database.

## How it works

- A suspended user cannot use normal logged-in website or Android endpoints. Existing remember-me and API tokens are revoked.
- Following successful password or Google authentication, the website grants only a separate, restricted appeal session (not ordinary login), valid for 24 hours.
- The member sees a clear suspension reason and private message history. The member can submit 6 messages per hour, each between 10 and 2,000 characters.
- Administrators can reply or reinstate the account. A reinstated member must log in again. Their previous content and case history remain intact.
- Accounts suspended before the feature existed are given a private case with a generic reason when they next authenticate; administrators can replace it with a specific explanation.
- Email is NOT required for the appeal workflow. Consider notification emails only after HelioHost SPF/DKIM delivery issues have been resolved.
- Android admins are required to enter a suspension reason; Android members are directed to the website for the private appeal thread.

## Integration test

On a disposable local database imported from database/install.sql, run:

    php website/tests/suspension_appeals_integration.php

The test verifies a required reason, token revocation, private appeal permissions, administrator reply and restoration of account status. It cleans up its temporary member.

## Suggested future improvements

- Alerts/badges for unread replies and an internal target response time
- Appeal resolution outcome categories with audited administrator actions
- Warnings and timed suspensions for repeat minor violations
- Carefully controlled evidence attachments, with privacy, file-size and retention limits
- Email notification after SMTP/DNS authentication is confirmed reliable
