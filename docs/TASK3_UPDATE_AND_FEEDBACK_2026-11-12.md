# MzansiGems – Task 3 Update Record and Lecturer Feedback Response

**Status:** Working Task 3 preparation document (not final compliance sign-off)  
**Review date:** 12 November 2026  
**Module:** XISD6329 / Work Integrated Learning 3B  
**Project:** APEX – MzansiGems  
**Source:** [GitHub repository](https://github.com/Kmakoro/MzansiGems) · [Live website](https://vutivivukeya544.helioho.st/) · [API](https://vutivivukeya544.helioho.st/api/v1/)  
**Scope:** PHP/MySQL website, Kotlin/Jetpack Compose Android client, shared PHP REST API/MySQL data, deployment and testing.

> **Important:** The original full Task 3 grading rubric and lecturer's marked feedback sheet must be attached and cross-checked before final submission. This summary is based on the previously supplied feedback discussion, project report, Task 2 mapping and merged PR evidence. It must not be described as proof of 100% assessment compliance.

## 1. Summary of improvements

The changes from 10–12 November 2026 responded to identified risks in sign-in reliability, weak account passwords, Android crashes/poor network feedback, and cross-platform consistency. The website and Android app continue to share the PHP REST API and MySQL database.

| PR | Change | Confirmation or remaining action |
| --- | --- | --- |
| [#16](https://github.com/Kmakoro/MzansiGems/pull/16) | Android admin suspension/appeal synchronisation with website, reason, cases, private replies and reinstatement | Merged; complete end-to-end walkthrough evidence still required |
| [#18](https://github.com/Kmakoro/MzansiGems/pull/18) | Clear personal like/save state on logout | Merged; user checked signed-out behaviour |
| [#19](https://github.com/Kmakoro/MzansiGems/pull/19) | Common 15+ character password policy across website, API and Android | Merged, PHP/Android tests |
| [#20](https://github.com/Kmakoro/MzansiGems/pull/20) | Require upper/lowercase, digit and symbol; reject numeric-only/repeated passwords | Merged; manual rejection/acceptance tests |
| [#21](https://github.com/Kmakoro/MzansiGems/pull/21) | Executable Gradle wrapper permission | Merged; Android unit test build succeeded with JDK 21 |
| [#22](https://github.com/Kmakoro/MzansiGems/pull/22) | Friendly offline / Try Again in Home and Discover, preserve cached gems | Merged; emulator retry success |
| [#23](https://github.com/Kmakoro/MzansiGems/pull/23) | Offline / Try Again on individual gem detail pages | Merged; emulator retry success |
| [#24](https://github.com/Kmakoro/MzansiGems/pull/24) | Remove XAMPP language from hosted API connection status; release checklist | Merged; connected status shown |
| [#25](https://github.com/Kmakoro/MzansiGems/pull/25) | Fix fatal website login error from missing `login_throttle_blocked()` function | Merged, correct files deployed, website login after password reset manually passed |
| [#27](https://github.com/Kmakoro/MzansiGems/pull/27) | Reuse website login throttle in API password login, with safe 429 responses and independent-account checks | PHP and Android CI passed; live API controller deployment and smoke test pending |

**Production deployment differs from GitHub merging:** PHP changes need targeted uploads to HelioHost `httpdocs/`. PR #25 was deployed by adding `includes/login_throttle.php` and aligning `login.php` after backups; subsequent user screenshots confirmed the live site reached `profile.php`.

## 2. Feedback response

| Lecturer's concern | Project response | Evidence status |
| --- | --- | --- |
| Insecure password such as `1234` / weak numeric password | 15+ characters; upper/lowercase, number, symbol; known/sequential/repeated patterns rejected on creation/change/reset | Implemented and manually checked; PHP/Android tests |
| Direct REST API bypass of front-end password rules | Server-side PHP policy used for matching endpoints | Source/CI; attach direct malformed request/response screenshots |
| Older user passwords still weak | Login remains compatible until a password is changed/reset | Policy for legacy-account remediation still to decide |
| Repeated password guessing | Website and API password-login code share IP/email and IP counters (PR #27) | Local API HTTP 401/429 and independent-user sign-in tests passed in CI; live HelioHost deployment and acceptance test still required |
| Concurrent login timeouts | Normal login/session tested; no quantitative concurrent load-test recovered | **Not yet evidenced** |
| App crashes, network errors | Recoverable offline state and Try Again on Discover and gem details, no false empty result | Manual emulator screenshots and new unit tests |
| Research gap / evidence of client need | Problem and comparison approach documented | **Need original survey/interviews and client meeting evidence** |
| Business viability / payer/revenue | Optional promoted listings/venue subscriptions proposed only as hypotheses | **Not validated; no live revenue claimed** |
| Website versus Android benefits | Responsive discoverability vs native touch-first convenience; one backend and data store | Architecture documented; measured usability results needed |

## 3. Architecture and functional coverage

Website (PHP/HTML/CSS/JavaScript) and Android (Kotlin, Compose, Retrofit/OkHttp) both use one server-side PHP application and MySQL/MariaDB database. Firebase/Google sign-in on Android resolves to the same application identity. Authentication/validation must be enforced on the server, not only in mobile/web forms.

Major features: registration, email/password and Google sign-in, password reset/change, profiles, gem discovery/detail, likes/saves, reviews, user contributions, moderation and suspension/appeals. Code availability must be distinguished from passing each acceptance test.

## 4. Observed verification

| Test | Evidence |
| --- | --- |
| Android rejects weak registration passwords (`12345678`, `1122334455667788`, missing-symbol/uppercase variants) | User-supplied November 2026 emulator screenshots |
| Strong test password accepted at registration | User-supplied emulator success screenshot |
| Android Change Password rejects weak values, accepts strong values; previous password fails and new password signs in | User-supplied emulator screenshots |
| Forgot password email delivered and website reset completed; new password signs in | Email and hosted website manual walkthrough; do not publish reset token |
| Website login after password reset and PR #25 | 12 November website `profile.php` screenshot; second full recovery run reported successful |
| Discover offline notice and Try Again, reconnect and recovered list | 12 November emulator screenshot pair |
| Gem detail offline notice and Try Again, reconnect and loaded `Truth Coffee Courtyard` | 12 November emulator screenshot sequence |
| Android/PHP CI | [GitHub Actions](https://github.com/Kmakoro/MzansiGems/actions): unit tests, debug APK build, PHP syntax/security and DB integration |
| Cross-platform saved gems and Google identity | Earlier manual verification and shared DB CI; attach final current video |

### Critical evidence still missing

- Real-device physical Android test and recorded demonstration.
- Measured **concurrent logins, timeouts, latency and error rates** on an authorised staging environment (do not stress-test HelioHost shared hosting without permission).
- Direct API weak-password rejection evidence and production API throttling verification (local API throttling integration test now passes; live behaviour is still unverified).
- Android-specific password-reset deep link demonstration.
- Full suspension → appeal → reply → reinstatement acceptance test.
- Client/user consultations, research findings, willingness-to-pay evidence and signed client UAT.
- Original Task 3 rubric/lecturer marking sheet, individual evaluations and actual Azure DevOps history.
- Signed release bundle/Play evidence **only when project owner authorises resumption**; currently paused.

## 5. Task 3 evidence / annexure checklist

| Deliverable | Status |
| --- | --- |
| Project report, architecture, implementation updates | Working report prepared; review and formatting pending team sign-off |
| Lecturer feedback response with original mark sheet | Paraphrased response prepared; original feedback/rubric required |
| GitHub repo/PR history and CI screenshots | Source available; capture dated build evidence |
| Database schema/ERD/OpenAPI, PK/FK and cardinality explanation | Code exists; update clear lecturer-facing figures |
| Acceptance-test results and screenshots | Core auth/offline tests collected; gaps listed above |
| Client validation and business model | Not yet evidenced |
| Video demo, presentation, README/release notes | Previous assets exist; update and validate URLs |
| Azure DevOps and group/individual evaluations | Original dated evidence and signed statements needed |
| AI-use disclosure | Must accurately describe selective assistance and independent team verification |
| Signed APK/AAB and Play Console | Paused; do not claim published release |

**Source guides:** [Project documentation](PROJECT_DOCUMENTATION.md), [Test plan](TEST_PLAN.md), [Task 2 compliance](../TASK2_COMPLIANCE.md), [Final submission checklist](FINAL_SUBMISSION_CHECKLIST.md), [Azure DevOps guide](AZURE_DEVOPS_EVIDENCE.md), [Google Play release audit](GOOGLE_PLAY_RELEASE_READINESS.md).

## 6. Prioritised next work

1. Obtain and attach the exact Task 3 grading rubric and lecturer feedback; revise this document clause-by-clause.
2. Perform a controlled **staging-only** multi-user login/timeout test with number of users, test script, p50/p95 latency, errors, server statistics and recovery.
3. Demonstrate direct API validation/rate-limit behaviour using non-sensitive test accounts.
4. Record real physical-phone Android behaviour and website–Android data synchronisation.
5. Collect genuine client and intended-user feedback; distinguish research results from revenue hypotheses.
6. Finish presentation, recorded walkthrough, UAT/DevOps/individual evaluation annexures and AI-use statement.
7. Revisit Play build/signing only when specifically instructed by the project owner.

## 7. Academic honesty / security notes

- Do not invent lecturer approvals, user interviews, performance measurements, payments, screenshots or signed-release status.
- Do not commit private keys, password-reset tokens, real passwords, database credentials or email contents to a public repository.
- The final team AI-use statement must accurately distinguish selective assistant help from group-led design, implementation, deployment and testing.
