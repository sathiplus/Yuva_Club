# Pre-mobile security and operations hotfix — Release2 gate

Base: 5b4a89348ebb994d8d2a5e0c69ec06a9a9846859, production deployment b8ddc041-bb76-49b8-8d6d-da15cfaf2276. No database migration.

## Public service worker

The previous navigation fallback cached successful PHP path-info responses. Only exact reviewed public asset URLs can now be cached. Responses must be successful, unredirected, same-URL, and not private/no-store. Activation deletes earlier YUVA caches before claiming clients. Unlisted routes never read or write a service-worker cache. The new registration requests version 17.

Existing clients must receive and activate the new worker before its protection applies. Offline clients with an old worker cannot be remotely purged while disconnected. Verify update/activation on an existing installation during Release2; do not claim historical device caches were already removed by this local change.

## One retention execution path

Production packaging excludes tools/, which removed the old required target. The authoritative CLI orchestration now lives at backend/jobs/media-retention.php and still invokes the existing MediaRetentionService. tools/media-retention.php is only a developer compatibility wrapper. The scheduled entry resolves the packaged runner relative to its own location. No absolute Azure-only source path is required.

The scheduler retains its existing daily singleton settings and execution default. Explicit --dry-run overrides that default; unknown or conflicting flags fail before application bootstrap. Tests invoke the actual extracted release artifact with tools/ absent. Existing eligibility, consent, processing protection, deletion, metadata and audit logic are unchanged. No production retention execution is authorized by this hotfix.

Release2: verify YUVA_MEDIA_RETENTION_DAYS=30 and run `php /home/site/wwwroot/App_Data/jobs/triggered/yuva-media-retention/run.php --dry-run` first. This creates normal runtime locks/bootstrap state but deletes no media. Inspect only aggregate counts in shared evidence. Review scheduled execution separately: deployment restores an already enabled destructive schedule, so Release2 approval must explicitly cover resuming it.

## Canonical origin

Canonical production origin: https://www.yuvaclub.app. The existing .htaccess canonical redirect and PHP canonical metadata establish www as authoritative. Both official domains may serve entry traffic; generated application links use www.

The stale .net value comes from runtime APP_URL configuration, overriding the previous correct source default. Production source now uses the canonical origin regardless of that stale value. public_base_url() now delegates to app_url(), so Host and forwarded headers cannot change email, reset, activation, registration or organization invitation links. APP_URL remains configurable for explicitly non-production environments.

Release2 must also change the Azure App Service APP_URL application setting to https://www.yuvaclub.app and verify it in the PHP runtime. This live configuration change is intentionally pending approval; it has not been performed locally. Do not change unrelated settings. Verify Student/Parent reset and activation link origins without disclosing tokens. Beta campaign/entitlement logic is unchanged; this baseline does not include the newer invitation-email implementation.

No production deployment, Azure-setting mutation, production retention execution, or mobile implementation is part of local validation.
