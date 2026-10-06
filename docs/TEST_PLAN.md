# Task 2 Test Plan - Mzansi Gem

Use this checklist on the final build and keep screenshots/video evidence for the submission.

| Test | Expected result | Evidence to capture |
|---|---|---|
| Launch app on physical Android phone | App opens without crash and Home loads | Phone screenshot/video |
| Invalid email login | Validation message; no crash | Screenshot |
| Wrong password login | API returns friendly error | Screenshot |
| Valid email/password login | User enters Home and profile is available | Video |
| Google SSO | Google account chooser opens; user signs in; matching MySQL account is used | Firebase Users + app + MySQL evidence |
| Register valid user | User is created; verification behaviour matches server setting | Video/database |
| Email verification deep link | Android app opens and verifies token | Video |
| Password reset deep link | Android app opens reset form and accepts valid new password | Video |
| Share invalid gem | Required/length/rating validation prevents submit | Screenshot |
| Share valid gem | Gem reaches API/MySQL and website sees the same record | App + website + DB |
| Save/unsave gem | Saved state changes on both clients | App + website |
| Submit/edit review | Review is stored and visible through shared API | App + website |
| Edit profile/settings | Changes persist and appear on website | App + website |
| Offline API | App displays error/offline status without crash | Screenshot |
| GitHub Actions | Android tests, APK build, PHP syntax and database checks all pass | Actions screenshot |
| Database counts | Every required table has at least 10 seeded records | CI log/phpMyAdmin |
| Release networking | Release/main config blocks cleartext HTTP | Code/CI evidence |

## Automated tests
Android JUnit tests cover authentication validation, password-reset validation and Share-a-Gem validation. GitHub Actions also compiles the app and builds the APK.

## Final physical-device pass
Before recording the demo, run every relevant row above on the exact physical phone used for the demonstration.
