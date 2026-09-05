# Beta invitation email delivery

No migration. Uses Migration 15 invitations, their filtered unique index, the campaign transaction lock, and subscription audit. Existing redemption rules and provider configuration are unchanged.

The Master Admin issue action resolves the approved, active Student's SQL account email. It commits one hashed invitation, records a safe delivery attempt, and calls the existing `send_yuva_email` abstraction once. The code exists transiently in memory and in the intended email only; no Admin session/flash contains it. The existing six-character hint is retained. The email contains no Parent details or other Student data.

The canonical `app_url()` must be one of the two official HTTPS origins. The CTA is `/portal.php#app-profile`, without a token or query string. The Student signs in, opens Profile if redirected to Home, and submits the emailed code through the existing authenticated/CSRF-protected form. No GET redemption or tokenized link is introduced.

Provider acceptance is not mailbox receipt. The UI distinguishes creation, provider acceptance, and failed/unknown delivery. Audit stores actor, invitation GUID, action and time, never provider payloads/errors. A failure or crash must not cause automatic resend: an unrevoked invitation blocks repeat issuance even after expiry. Explicitly revoke the unused invitation, then issue a replacement. Used invitations cannot be revoked by this action or reissued in the same campaign.

This is an at-most-once send attempt, not guaranteed exactly-once delivery. A crash between commit and send can leave an undelivered invitation. A provider timeout can mean delivery is uncertain. A crash/audit failure after acceptance can leave only an attempted audit record. Operators check status and use explicit revocation/reissue, never reconstruct or persist the secret. There is no queue or automatic retry.

Tests cover canonical URL/recipient/content/privacy, rejected roles, replay, concurrent issue requests on separate SQL connections, mail false/exception outcomes, explicit revoke/reissue, wrong/invalid/expired/revoked/disabled/reused codes, and one Premium grant on successful redemption. SQL tests refuse non-local/non-disposable databases and remove their own fixture rows in FK order; CI destroys its database and container after failure too.

Release2 validation is authorized against the shared database only for the retained internal family. New production application deployment and PR merge require separate authorization. Record real receipt and production code-redemption separately from CI transport simulation. Beta launch is not authorized by this work.
