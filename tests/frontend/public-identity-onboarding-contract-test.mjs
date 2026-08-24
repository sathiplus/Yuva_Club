import fs from 'node:fs';
import assert from 'node:assert/strict';

const registration = fs.readFileSync(new URL('../../registration.php', import.meta.url), 'utf8');
const login = fs.readFileSync(new URL('../../portal-login.php', import.meta.url), 'utf8');
const onboarding = fs.readFileSync(new URL('../../student-identity-onboarding.php', import.meta.url), 'utf8');
const portalLib = fs.readFileSync(new URL('../../portal-lib.php', import.meta.url), 'utf8');
const css = fs.readFileSync(new URL('../../assets/public-identity-onboarding.css', import.meta.url), 'utf8');

assert.match(registration, /Preferred Name/);
assert.doesNotMatch(registration, /name="preferred_name"[^>]*(?:YUVA Handle|public_handle)/);
assert.match(registration, /After registration, you can choose a YUVA Handle and Avatar for challenges and leaderboards\./);
assert.match(login, /student_identity_onboarding_required\(\$studentId\)/);
assert.match(login, /redirect_to\('student-identity-onboarding\.php'\)/);
assert.match(onboarding, /Create Your YUVA Identity/);
assert.match(onboarding, /Permanent YUVA ID/);
assert.match(onboarding, /Choose how you want the YUVA community to recognize you in challenges and leaderboards\. Your personal contact information stays private\./);
assert.match(onboarding, /public_identity_service\(\)->updateOwn/);
assert.match(onboarding, /PublicStudentIdentity::AVATARS/);
assert.match(onboarding, /verify_csrf_token/);
assert.match(onboarding, /complete_student_identity_onboarding\(\$yuvaId, 'skipped'\)/);
assert.match(onboarding, /your permanent YUVA ID will be used as your public fallback/i);
assert.doesNotMatch(onboarding, /(?:Student Email|Parent Email|Date of Birth|Phone|Address)/i);
assert.match(portalLib, /'identity_onboarding_required' => true/);
assert.match(portalLib, /\(\$account\['identity_onboarding_required'\] \?\? false\) === true/);
assert.match(portalLib, /'identity_onboarding_outcome'\] = \$outcome/);
assert.match(css, /@media\(max-width:760px\)/);
console.log('Public identity onboarding contract: PASS');
