# MzansiGems — Task 3 final submission and controlled deployment

> Working document for the group. **Do not submit unverified test results, invent interview evidence, or deploy directly from a draft pull request.** The website and app must be independently verified after deployment.

## Source of assessment requirements
Module manual: XISD6329w, section 9.3 (Task 3 – Project Report), and rubric pages 40–42, supplied in screenshots on 10 November 2026. The group has already presented; the lecturer's Task 2 remarks and nine follow-up questions must be reflected in the final individual reports.

## Required Task 3 submission items

- [ ] A single final report per team member. Combine the previous 3 documents in one file; submit individually on ARC.
- [ ] Full names of all team members, group name, project link, title page, submission date.
- [ ] Declaration of Authenticity (Annexure A), signed by the submitting member; do not forge signatures.
- [ ] Task 1 and Task 2 feedback with a **before / change made / test evidence / current result** table.
- [ ] Brief project development explanation and design purpose, user audience, client needs, features and implementation choices.
- [ ] Clear **labelled screenshots** of both Android and website (same feature where possible).
- [ ] Peer evaluations (Annexure B) completed by actual peers.
- [ ] Reflective self-assessment (Annexure C) written by the student.
- [ ] GitHub repository URL and links to relevant commits / successful workflow runs.
- [ ] Comprehensive README on the repo: project purpose, design choices, architecture, GitHub/GitHub Actions, screenshots and real release notes identifying innovative additions since the prototype.
- [ ] If AI tools were used, a short **factual** write-up, maximum 500 words: which tools, what was generated/modified, prompts/examples, verification and human responsibility.
- [ ] Android Kotlin source code uploaded to GitHub (not a ZIP); appropriate code comments and references.
- [ ] Runtime/application logging to show errors and diagnosis without logging secrets, passwords, tokens or reset links.
- [ ] Voice-over feature demonstration video, with an unlisted YouTube URL (or another accessible video link) added to README.
- [ ] Signed APK, accurate screenshots, and evidence of upload to Google Play Console if applicable; a debug APK alone is **not** production-ready.
- [ ] PowerPoint of website + Android app designs, features and bug-fix evidence. **Every member must present at least one topic.**
- [ ] Evidence of both responsive website and Android app running on a physical device without crashes on the final code.
- [ ] Final check against the complete rubric, not only the screenshots (which do not show every row).

## Confirmed implementation areas (from source code, NOT live results)

**Website:** PHP + MySQL, responsive discovery filters, gem submissions, rating/reviews, saves, admin moderation.  
**Android:** Kotlin + Jetpack Compose with Retrofit REST API, Google identity flow, saving and reactions.  
**Data:** both clients use the shared `/api/v1/` and MySQL database.

## Final engineering review branch

GitHub draft PR: https://github.com/Kmakoro/MzansiGems/pull/14

- One server-side password policy for all register/reset/change routes. A 12-character minimum and common-password rejection are enforced at the API as well as on website forms.
- Legacy weak credentials require password reset. The reset-required database flag blocks existing web sessions and bearer tokens after the account is flagged.
- A persistent per-email + IP throttle limits repeated sign-in guesses while allowing distinct accounts to authenticate.
- City filters are dynamic from approved gems, and website/Android can enter towns beyond the old fixed list.
- Admin moderation shows searchable and paginated approved gems with edit links, preserving approved status after edits.
- Android API handling guards missing payloads, 502/503/504 messages, network timeouts, and double-tapped save/reaction events.
- Mailing logs dispatch failures, and can use an optional verified Brevo sender and API key. **An actual mailbox test is required.**
- Auth token last-used writes are reduced to avoid write contention on every API request.

## HelioHost/Plesk deployment — exact safe order

1. Log into Plesk. **Download a full backup of both website files (including `uploads/gems/`) and the MySQL database through phpMyAdmin Export.** Do not send passwords or admin credentials to a chat.
2. Copy a non-production/staging deployment and verify that your app uses the correct API URL. Prefer a separate database or a backup restore.
3. Confirm `APP_URL=https://vutivivukeya544.helioho.st` and `APP_ENV=production` in hosting environment settings. Do not put secrets into GitHub.
4. Configure a real sender for email: create a working mailbox under Plesk > Mail and set `MAIL_FROM` to that verified address. Optional provider: `BREVO_API_KEY` (secret stored only on the server) with a verified sender. On HelioHost, arrange SPF/DKIM/DMARC if delivery fails. Test **actual receipt** before enforcing legacy-account resets.
5. Make sure admins have real reachable email addresses. Seeded `.local` demo emails are not deliverable; move them to real verified addresses and reset weak passwords before public deployment.
6. On an **existing populated** live database, run **only** `website/database/migrate_final_auth_throttle.sql` from phpMyAdmin SQL **once**, after backing up. It creates the throttle table and adds the `password_reset_required` column. **NEVER run `database/install.sql` on a live installation; it drops data.**
7. Publish the reviewed `website/` PHP files through Plesk File Manager, preserving live environment files, uploads and database. GitHub merges do not update HelioHost automatically.
8. Run a staging smoke test: website home, login/register/forgot/reset, city filters, admin edits, share, comments, save, likes. Check Plesk logs.
9. Verify `https://vutivivukeya544.helioho.st/api/v1/health` returns success and `/api/v1/categories` contains the approved gem cities. Test normal mobile-data connection.
10. Build and sign Android against `https://vutivivukeya544.helioho.st/api/v1/`, preserving the registered Firebase signing SHA-1. Capture fresh Google sign-in, save, reaction and upload screenshots on a physical Android device.
11. Record **actual** before/after performance results and screenshots. Do not claim a 100% uptime guarantee on Johnny shared hosting.

## Why the 502 page cannot be hidden in PHP

A 502 returned by nginx before reaching PHP is outside the application's control. HelioHost documents periodic Apache restarts on Johnny/Tommy that can cause 502s. Investigate the Plesk/domain logs around the timestamp; test `/api/v1/health` alongside the admin page. If it is a hosting restart, only a hosting change (for example moving to another plan) can mitigate its frequency. PHP can return structured messages only for requests that actually reach PHP.

- HelioHost: https://wiki.helionet.org/502_Bad_Gateway
- Plesk: https://support.plesk.com/hc/en-us/articles/12378119195159-Some-websites-are-not-accessible-with-502-Bad-Gateway-or-504-Gateway-Time-out-11-Resource-temporarily-unavailable-while-connecting-to-upstream

## Required verification matrix (capture evidence, not estimates)

| Scenario | Website | Android | Server/API | Evidence to save |
| --- | --- | --- | --- | --- |
| Register with `1234` | shows validation | shows validation | HTTP 422 | screenshot and response |
| Register/reset with strong password | registration/verification | registration/reset deeplink | 201/200 | test account and mail receipt |
| Legacy weak login | reset-required message | reset-required message | HTTP 403, no token | before/after test account |
| Forgot password | displays generic instruction | requests reset email | hashed one-hour reset token | received email, successful reset, expired/reused token rejected |
| Repeated guesses | slow repeated attempts only for target account | same server policy | HTTP 429 after threshold | log & two-account control |
| Concurrent correct logins | independent sessions | independent bearer tokens | successes, no timeouts | load-test p50/p95, 50x rates |
| Approve and edit gem | admin edit approved record | updated gem visible | approved remains approved | before/after screenshots |
| Add unfamiliar town | submit new town | enter custom town | city visible after approval | filter by city, vibe, budget, activity |
| Save/unsave, likes, review likes | counter and state sync | no app crash | correct rows and counts | Android screen recording + DB |
| 502/timeout | error page may be hosting-generated | informative offline/server message | correlation with Plesk logs | timestamps and log entries |

## Evidence-based research and business case

Ask the client to confirm the original problem, current alternative tools, priority audience, an example use case and willingness to pay. A **proposed** model is promoted placement paid by local businesses, with users searching free; it must remain a hypothesis until the client validates it. Android specifically adds on-the-go saving, reacting and location discovery, with the same account visible on the website.

## Mandatory release notes

Document changes since the prototype in README with links to commits/PR, architecture diagrams, named innovations and actual tests. Do not use the same release notes as proof of deployment.

## Still needs group-owned materials

- Exact team names, individual peer/self evaluation and authenticity signature.
- Client interview notes, client acceptance/feedback, dated feature evidence and proposed revenue model approval.
- Final video link, PowerPoint, screenshots and app store/Play Console evidence.
- Plesk domain logs, live mail and mobile-data verification.
- Local device crash logs from Android Studio Logcat, if any crashes remain after updating the APK.
