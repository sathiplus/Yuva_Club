# YUVA Club Student UI Version 1 — RC1 Production Readiness Test Plan

## Document Control

- Release: Student UI Version 1 RC1
- Branch: `codex/student-v1-release`
- Inspected commit: `7d5a6797e147b33b4b8002775426a922adee09df`
- Backend: Existing Azure/PHP application
- PHP target: 8.3, matching the deployment workflow
- Client targets: iOS Safari, Android Chrome, current desktop Chrome, Edge, Firefox, and Safari
- Scope: Home Dashboard, Practice Workspace, Presentation Center, AI Coach Studio, Leadership Journey, Achievements, and My Profile
- Native packaging: Out of scope for RC1; Phase 10 will package the validated Student V1 frontend with Capacitor

## Purpose

This plan determines whether Student V1 is safe and stable enough for staging phone testing and subsequent release approval. It verifies existing functionality and does not authorize feature development, production deployment, or native packaging.

## Current Source Inspection Summary

### Verified in source

- Five-item Home, Practice, Present, Progress, and Profile navigation exists in both mobile and desktop shells.
- AI Coach Studio is reachable from Practice and retains Practice as its active navigation destination.
- Topic, research, upload, report, Zoom, scheduler, certificate, AI review, rubric, leadership, achievement, and profile values remain PHP-bound.
- Topic, research, and safety-report forms contain CSRF fields and their handlers verify the token.
- Research upload retains its existing form field, multipart encoding, accepted extensions, per-student storage path, and protected download route.
- Scheduler embedding accepts only `https://scheduler.zoom.us/` URLs.
- Zoom actions open externally with `target="_blank"` and `rel="noopener"`.
- Students and parents cannot open a certificate unless its status is `Ready` or `Issued`; administrators retain preview access.
- AI Coach displays full review content only when status equals `Applied by Admin`.
- Draft errors and private administrative data are not rendered in Student V1.
- Safe-area, narrow-width, desktop rail, focus-visible, reduced-motion, and live-region rules are present.

### RC1 release blockers identified in source

#### RC1-B01 — Authenticated navigation can be cached

Severity: Critical

The service worker handles navigation requests before its PHP network-only rule. An authenticated `portal.php` response can therefore be written to Cache Storage and potentially remain viewable on a shared device after logout or loss of connectivity.

Required result before production approval:

- Authenticated PHP and portal navigation responses are network-only.
- Student HTML is never placed in Cache Storage.
- Logout is followed by confirmation that no student page can be recovered offline or from back/forward cache.
- Public offline fallback behavior remains functional.

#### RC1-B02 — Manifest does not launch Student V1

Severity: High for installable web testing

The current manifest uses `/` as `start_url` and retains shortcuts for the general website, registration, programs, and stories. It does not represent the Student V1 navigation model.

Required result before PWA/Add-to-Home-Screen approval:

- Confirm whether RC1 intentionally retains the general-site manifest.
- If Student V1 is the installable target, use an authenticated-safe student entry point and Student V1 shortcuts.
- Verify unauthenticated launch redirects safely to login and returns to Student Home after authentication.

#### RC1-B03 — PHP lint has not been executed locally

Severity: High

The release must pass PHP 8.3 lint for every changed PHP file before staging approval.

#### RC1-B04 — Upload hardening requires security validation

Severity: High

The current upload handler allowlists filename extensions and stores uploads outside the public site path, but source inspection did not find MIME/content validation or an explicit maximum file-size check. Existing behavior must be security-tested before production and reviewed against Azure/PHP upload limits.

## Release Entry Criteria

- [ ] RC1 branch and commit are identified and immutable for the test cycle.
- [ ] Staging uses HTTPS and is isolated from production writes.
- [ ] Staging has a dedicated student test account and representative approved records.
- [ ] Staging has test records for empty, pending, ready, and approved states.
- [ ] No real child/student personal information is used in screenshots or test evidence.
- [ ] PHP 8.3 is available in CI or the staging build environment.
- [ ] Browser console and Azure application logs are accessible to the test team.
- [ ] A rollback artifact for the current production commit is retained.

## Required Test Data

Prepare controlled staging records for:

1. A new student with no topic or research.
2. A student with a selected topic but no research.
3. A student with saved research and no upload.
4. A student with an approved upload.
5. A student with a personal Zoom session.
6. A student relying on the group Zoom session fallback.
7. A student with no AI review.
8. A student with an AI draft awaiting approval.
9. A student with an approved `Applied by Admin` AI review.
10. A student with certificate status `Not Ready`.
11. A student with certificate status `Ready`.
12. A student with certificate status `Issued`.
13. A student with badges, rubric results, and recognition notes.
14. A student with no badges, rubric results, or recognition notes.

## 1. Build and PHP Validation

| ID | Test | Expected result |
| --- | --- | --- |
| PHP-01 | Run `php -l portal.php` using PHP 8.3 | No syntax errors |
| PHP-02 | Run `php -l portal-lib.php` | No syntax errors |
| PHP-03 | Run `php -l certificate.php` | No syntax errors |
| PHP-04 | Run `php -l portal-submit-topic.php` | No syntax errors |
| PHP-05 | Run `php -l portal-submit-research.php` | No syntax errors |
| PHP-06 | Run `php -l portal-report-issue.php` | No syntax errors |
| PHP-07 | Run JavaScript syntax validation for `assets/student-app.js` | Passes without errors |
| PHP-08 | Validate CSS parsing and braces | No syntax or brace errors |
| PHP-09 | Load the authenticated portal with PHP error display disabled and logging enabled | No warnings, notices, or fatal errors in logs |
| PHP-10 | Exercise every empty and populated PHP state listed above | No undefined-key, type, or encoding warnings |

## 2. Authentication and Session Safety

| ID | Test | Expected result |
| --- | --- | --- |
| AUTH-01 | Open `portal.php` without a student session | Redirects to existing student login |
| AUTH-02 | Authenticate with an existing staging student account | Opens Student Home and retains the correct student ID |
| AUTH-03 | Attempt to access another student's download or certificate URL | Access is denied or redirected |
| AUTH-04 | Log out from desktop rail and My Profile | Session ends and protected pages require login |
| AUTH-05 | Use browser Back after logout | Protected student content is not displayed |
| AUTH-06 | Go offline after logout and revisit the portal | No cached student HTML is exposed |
| AUTH-07 | Close and reopen an installed or standalone session | Authentication state follows existing secure server-session behavior |
| AUTH-08 | Inspect cookies in staging security tools | Session cookie uses expected Secure, HttpOnly, and SameSite behavior |

## 3. Navigation

| ID | Test | Expected result |
| --- | --- | --- |
| NAV-01 | Verify mobile bottom navigation | Exactly Home, Practice, Present, Progress, Profile |
| NAV-02 | Verify desktop rail navigation | Same destinations in the same order |
| NAV-03 | Open each primary destination | Correct section appears and correct item has `aria-current="page"` |
| NAV-04 | Open AI Coach Studio from Practice | AI Coach opens and Practice remains active |
| NAV-05 | Open Achievements from Leadership Journey | Achievements opens and Progress remains active |
| NAV-06 | Use Home next action in each data state | Goes to Topic, Research, or Presentation Center as appropriate |
| NAV-07 | Use Presentation links from Home and legacy module | Opens Presentation Center without confusing subsection jumps |
| NAV-08 | Use browser Back and Forward across hash destinations | URL, focus, visible section, and active state remain synchronized |
| NAV-09 | Activate a link to the currently open hash | Focus moves to the destination heading and navigation remains stable |
| NAV-10 | Verify all internal hashes | No missing, duplicate, or ambiguous targets |

## 4. PHP Data Bindings

For every screen, compare the rendered value with its staging source record.

- [ ] Student display name and initials
- [ ] YUVA Club ID and membership group
- [ ] Points and tokens
- [ ] Leadership level, eligible rank, and challenge stage
- [ ] Attendance, service hours, and presentation count
- [ ] Selected topic, category, date, time, and status
- [ ] Research notes, sources, outline, questions, status, and upload name
- [ ] Personal session and group-session fallback data
- [ ] Official presentation rubric and judge feedback
- [ ] Earned badges and recognition notes
- [ ] Certificate status and title
- [ ] Profile identity, school, contact, preferences, safety agreements, and participation data

Expected result: every displayed value comes from the signed-in student's real PHP record or an approved honest empty state. No cross-student data or sample data appears.

## 5. Topic and Research Forms

| ID | Test | Expected result |
| --- | --- | --- |
| FORM-01 | Change topic category | Topic list refreshes from the existing PHP-generated map |
| FORM-02 | Submit a valid topic | Existing handler saves it and success feedback is announced |
| FORM-03 | Submit an already-taken topic | Existing conflict message is shown and announced |
| FORM-04 | Submit without required topic fields | Native/server validation prevents incomplete save |
| FORM-05 | Submit valid research fields | Values persist and status remains backend-controlled |
| FORM-06 | Submit with a missing required research field | Honest error appears; partial invalid data is not treated as complete |
| FORM-07 | Submit with a missing or expired CSRF token | Request is rejected and security message appears |
| FORM-08 | Refresh after successful submission | Duplicate write is not performed without a new POST |

## 6. Uploads

| ID | Test | Expected result |
| --- | --- | --- |
| UP-01 | Upload PDF, PPT, PPTX, DOC, DOCX, JPG, JPEG, and PNG test files | Allowed files save successfully within configured limits |
| UP-02 | Upload a disallowed extension | File is rejected and research text behavior matches the existing contract |
| UP-03 | Rename executable content to an allowed extension | Server-side validation safely rejects or quarantines it; record security outcome |
| UP-04 | Upload an oversized file | Controlled error; no partial/orphaned file and no PHP fatal error |
| UP-05 | Upload a filename containing spaces, Unicode, traversal sequences, and special characters | Stored name is sanitized and cannot escape the student directory |
| UP-06 | Replace an existing upload | New upload is shown and existing backend replacement behavior is preserved |
| UP-07 | Submit research without selecting a new file | Existing upload metadata remains intact |
| UP-08 | Download the signed-in student's upload | Authorized file downloads with correct filename and safe headers |
| UP-09 | Change download `id` to another student | Cross-student download is denied |
| UP-10 | Test camera/photo and Files pickers on iOS and Android | Selection and upload complete without WebView/browser failure |

Security sign-off is required for MIME detection, maximum size, storage permissions, download headers, and orphan cleanup.

## 7. Zoom and Scheduler

| ID | Test | Expected result |
| --- | --- | --- |
| ZOOM-01 | Student has a personal Zoom URL | Join Zoom opens the approved external destination |
| ZOOM-02 | Personal session exists without a Zoom URL | Honest “not posted” state appears |
| ZOOM-03 | No personal session; group Zoom exists | Group fallback is shown without claiming it is personal |
| ZOOM-04 | Test supported `/j/{meeting}` URL | Browser Join is generated correctly |
| ZOOM-05 | Test an unsupported Zoom URL shape | Browser Join is omitted rather than fabricated |
| ZOOM-06 | Scheduler contains an approved scheduler.zoom.us embed | Iframe and new-tab scheduler link work |
| ZOOM-07 | Scheduler uses a different domain or unsafe scheme | Embed is rejected and honest empty state appears |
| ZOOM-08 | Test iOS and Android external-link behavior | Zoom app or system browser opens intentionally; student portal session remains intact |
| ZOOM-09 | Return from Zoom to the portal | Correct app section and unsaved form state behave predictably |
| ZOOM-10 | Inspect iframe security behavior | No mixed content, blocked framing, console errors, or unexpected permissions |

## 8. Certificates

| ID | Test | Expected result |
| --- | --- | --- |
| CERT-01 | Certificate status is `Not Ready` | No active certificate link; honest approval message shown |
| CERT-02 | Directly open certificate URL while `Not Ready` | Student/parent is redirected; certificate is not rendered |
| CERT-03 | Certificate status is `Ready` | View and Open to Print actions appear |
| CERT-04 | Certificate status is `Issued` | View and Open to Print actions appear |
| CERT-05 | Administrator previews a non-ready certificate | Existing approved administrator preview remains available |
| CERT-06 | Change certificate `id` to another student | Authorization prevents access |
| CERT-07 | Print on desktop and mobile | Certificate layout is readable and contains only the intended student's data |

## 9. AI Coach Studio

| ID | Test | Expected result |
| --- | --- | --- |
| AI-01 | No research exists | “No research submitted” state appears |
| AI-02 | Research exists, no AI record | “No AI review created” state appears |
| AI-03 | Draft review exists | Awaiting-approval state appears; review content remains hidden |
| AI-04 | Review contains setup/server error | Student sees a safe unavailable state, not raw error details |
| AI-05 | Status is `Applied by Admin` | Approved review content appears |
| AI-06 | Approved review has missing optional fields | Honest fallback copy appears without PHP warnings |
| AI-07 | Inspect rendered output and page source for draft/private data | No private admin notes, prompts, raw errors, or unapproved output |
| AI-08 | Compare AI categories with official presentation rubric | They remain visibly and semantically separate |
| AI-09 | Verify approved token value | Matches the existing approved backend value; no invented award |

## 10. Responsive and Mobile Layout

Test portrait and landscape where supported.

| Width | Required coverage |
| --- | --- |
| 320px | Narrow Android/small viewport, no horizontal page overflow |
| 360px | Common Android viewport |
| 375px | iPhone viewport |
| 390px | Modern iPhone viewport |
| 430px | Large iPhone viewport |
| 768px | Tablet/small desktop transition |
| 900px | Mobile-to-desktop navigation breakpoint |
| 1280px+ | Desktop content maximum and rail behavior |

For every width:

- [ ] Hero text and illustration do not overlap.
- [ ] Four Home metrics remain readable.
- [ ] Buttons and links do not clip or overlap.
- [ ] Long names, schools, membership groups, topics, and filenames wrap safely.
- [ ] Form controls remain full-width and usable.
- [ ] Scheduler iframe remains contained.
- [ ] Future-feature cards align consistently.
- [ ] Bottom navigation never obscures content.
- [ ] iOS safe-area top and bottom insets are honored.
- [ ] Android on-screen keyboard does not hide the active control or submit button.
- [ ] No unintended horizontal page scrolling occurs.

## 11. Accessibility

| ID | Test | Expected result |
| --- | --- | --- |
| A11Y-01 | Navigate the complete app using keyboard only | Logical order, no traps, and visible focus throughout |
| A11Y-02 | Activate hash navigation by keyboard | Destination heading receives focus |
| A11Y-03 | Inspect active navigation | Exactly one primary destination has `aria-current="page"` |
| A11Y-04 | Trigger success, validation, upload, and security messages | Screen reader announces each appropriately |
| A11Y-05 | Inspect icon-only controls | Accessible names are present |
| A11Y-06 | Inspect decorative images/icons | Hidden from assistive technology |
| A11Y-07 | Test VoiceOver on iOS | Headings, controls, forms, states, and navigation are understandable |
| A11Y-08 | Test TalkBack on Android | Same requirements as VoiceOver |
| A11Y-09 | Measure text and control contrast | WCAG 2.1 AA for normal text and user-interface components |
| A11Y-10 | Zoom browser to 200% | No lost content, clipping, or unusable controls |
| A11Y-11 | Enable reduced motion | Nonessential animations and transitions are suppressed |
| A11Y-12 | Measure touch targets | Primary interactive targets meet the 48px application standard |
| A11Y-13 | Run automated axe/Lighthouse accessibility audit | No critical or serious violations without documented review |

## 12. Service Worker and Cache Safety

| ID | Test | Expected result |
| --- | --- | --- |
| SW-01 | Inspect Cache Storage after authenticated portal navigation | No authenticated PHP/portal HTML is cached |
| SW-02 | Log out, disconnect network, revisit portal URL | No student data is displayed |
| SW-03 | Use browser Back after logout | No cached authenticated page appears |
| SW-04 | Install a new worker version | New worker activates predictably and removes obsolete public caches |
| SW-05 | First visit with an empty cache | Public shell loads; Student V1 assets load online without partial styling |
| SW-06 | Offline public-page navigation | Approved public offline fallback appears |
| SW-07 | Offline protected-page navigation | Login/network-required state appears; no student snapshot fallback |
| SW-08 | Inspect cache contents | Only explicitly approved public static resources are present |
| SW-09 | Clear site data and reinstall | Clean installation works without stale assets |

RC1 cannot receive service-worker approval until RC1-B01 is resolved.

## 13. Manifest and Installation

| ID | Test | Expected result |
| --- | --- | --- |
| MAN-01 | Validate manifest JSON | Valid syntax and all icon files resolve |
| MAN-02 | Run browser installability audit | No manifest/icon/scope errors |
| MAN-03 | Launch from installed icon while logged out | Safe login flow, then Student Home |
| MAN-04 | Launch while logged in | Intended Student V1 start destination |
| MAN-05 | Test shortcuts | Labels and destinations match the approved product direction |
| MAN-06 | Inspect maskable icon on Android | No clipping in circular/squircle masks |
| MAN-07 | Inspect iOS home-screen icon and standalone mode | Correct icon, title, status bar, and safe areas |
| MAN-08 | Verify orientation behavior | Portrait preference does not break required device flows |

RC1-B02 must be resolved or explicitly accepted before installed-web-app approval. Native App Store packaging remains Phase 10.

## 14. Browser Compatibility Matrix

Test the current stable release and one prior major version where practical.

| Platform | Browser | Required tests |
| --- | --- | --- |
| Windows | Chrome | Full functional, keyboard, upload, Zoom, print |
| Windows | Edge | Full smoke, navigation, focus, SVG masks |
| Windows/macOS | Firefox | Layout, forms, uploads, CSS masks, external links |
| macOS | Safari | Layout, keyboard, certificate print, standalone-related metadata |
| iPhone/iPad | Safari | Full phone journey, safe areas, upload, Zoom, VoiceOver |
| Android | Chrome | Full phone journey, installability, upload, Zoom, TalkBack |
| Android | WebView/Capacitor evaluation | Phase 10 compatibility reconnaissance only; no RC1 native approval |

Verify support for:

- CSS Grid, `clamp()`, `:where()`, masks, `clip-path`, and safe-area environment variables.
- JavaScript optional chaining, `URL`, `IntersectionObserver`, `matchMedia`, and focus options.
- PHP-generated Unicode and HTML escaping.
- External window behavior and return-to-app behavior.

## 15. Performance and Reliability

- [ ] No missing CSS, JavaScript, SVG, logo, icon, or topic-image requests.
- [ ] No uncaught browser-console errors.
- [ ] No PHP warnings or Azure application errors.
- [ ] Initial authenticated load is acceptable on a throttled mobile connection.
- [ ] Long Student V1 page remains responsive while scrolling.
- [ ] Scheduler iframe does not block the rest of the application.
- [ ] Repeated hash navigation does not accumulate observers, focus targets, or history defects.
- [ ] Static assets use predictable cache behavior without caching private HTML.

## 16. End-to-End Student Journey

Run once on iPhone Safari and once on Android Chrome:

1. Log in using an existing staging student account.
2. Review Home data and announcements.
3. Choose and save a topic.
4. Submit research without a file.
5. Replace the research with an approved upload.
6. Review Presentation Center and test Zoom/scheduler behavior.
7. Review all AI Coach states, including an approved review.
8. Review Leadership Journey and leaderboard link.
9. Review Achievements with non-ready and ready certificates.
10. Review My Profile and logout.
11. Use Back and offline mode to confirm no student content remains accessible.

Expected result: the journey is coherent, data remains student-specific, all supported actions work, planned tools remain honest, and no private content persists after logout.

## Exit Criteria

Student V1 RC1 is production-ready only when:

- [ ] All Critical and High findings are resolved or formally accepted by product and security owners.
- [ ] RC1-B01 is resolved and cache-safety tests pass.
- [ ] RC1-B02 is resolved or explicitly deferred because native packaging, not PWA installation, is the release target.
- [ ] All six PHP files pass PHP 8.3 lint.
- [ ] Authentication, CSRF, upload, Zoom, certificate, and AI approval tests pass.
- [ ] iPhone Safari and Android Chrome end-to-end journeys pass.
- [ ] Keyboard, VoiceOver, TalkBack, contrast, and automated accessibility checks pass.
- [ ] No cross-student access or cached student data is observed.
- [ ] Browser console and Azure logs contain no release-blocking errors.
- [ ] A tested rollback artifact and procedure are available.
- [ ] Product owner approves screenshots and the complete student journey.

## Test Evidence Template

For every failed or conditionally passed test, record:

- Test ID
- Environment and device
- Browser and version
- Student fixture used
- Steps to reproduce
- Expected and actual result
- Screenshot/video reference without real student data
- Console/server log reference
- Severity
- Owner
- Fix or accepted-risk decision
- Retest date and result

## Final Approval

- Product owner: ____________________ Date: __________
- Engineering owner: ________________ Date: __________
- Security/privacy owner: ___________ Date: __________
- Accessibility reviewer: ___________ Date: __________
- Mobile QA owner: __________________ Date: __________
- Release manager: __________________ Date: __________