import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import vm from 'node:vm';

const root = new URL('../../', import.meta.url);
const script = await readFile(new URL('assets/password-visibility.js', root), 'utf8');

for (const page of ['portal-login.php', 'admin-login.php']) {
  const source = await readFile(new URL(page, root), 'utf8');
  assert.match(source, /<input[^>]+id="[^"]+"[^>]+type="password"/s, `${page} must default to a hidden password`);
  assert.match(source, /<button[^>]+type="button"[^>]+data-password-toggle/s, `${page} toggle must not submit the form`);
  assert.match(source, /aria-controls="[^"]+"/, `${page} toggle must identify its password field`);
  assert.match(source, /aria-label="Show password"/, `${page} toggle needs an accessible initial label`);
  assert.match(source, /aria-pressed="false"/, `${page} toggle must expose its initial state`);
  assert.ok(source.includes('assets/password-visibility.js'), `${page} must load the shared toggle behavior`);
  assert.ok(source.includes('assets/password-visibility.css?v=auth-ux-20260820'), `${page} must load the compact toggle styles without a stale cache`);
  assert.match(source, /class="password-toggle-icon" width="20" height="20"/, `${page} eye icon must have safe intrinsic dimensions`);
}

const studentLogin = await readFile(new URL('portal-login.php', root), 'utf8');
assert.doesNotMatch(studentLogin, /date[\s_-]*of[\s_-]*birth|\bDOB\b/i, 'Student login must remain Email + Password only');

const registration = await readFile(new URL('registration.php', root), 'utf8');
assert.match(registration, /id="account_password"[^>]+minlength="8"/, 'Student registration must expose the eight-character minimum');
assert.match(registration, /id="account_password_confirm"[^>]+minlength="8"/, 'Student registration confirmation must expose the eight-character minimum');

const reset = await readFile(new URL('reset-password.php', root), 'utf8');
assert.ok(reset.includes("$accountType === 'student' ? '8' : '12'"), 'Password reset must preserve distinct student and administrative minimums');

const admin = await readFile(new URL('admin.php', root), 'utf8');
assert.match(admin, /id="new_password"[^>]+minlength="12"/, 'Master Admin password changes must retain the twelve-character minimum');
assert.match(admin, /id="confirm_password"[^>]+minlength="12"/, 'Master Admin password confirmation must retain the twelve-character minimum');

const input = { tagName: 'INPUT', type: 'password', value: 'KeepMe!1' };
const label = { textContent: 'Show password' };
const attributes = new Map([
  ['data-password-toggle', 'test-password'],
  ['aria-controls', 'test-password'],
  ['aria-label', 'Show password'],
  ['aria-pressed', 'false'],
]);
let clickHandler = null;
const toggle = {
  getAttribute: (name) => attributes.get(name) ?? null,
  setAttribute: (name, value) => attributes.set(name, value),
  querySelector: (selector) => selector === '.password-toggle-label' ? label : null,
  addEventListener: (event, handler) => {
    if (event === 'click') clickHandler = handler;
  },
};
const document = {
  querySelectorAll: (selector) => selector === '[data-password-toggle]' ? [toggle] : [],
  getElementById: (id) => id === 'test-password' ? input : null,
};

vm.runInNewContext(script, { document });
assert.equal(typeof clickHandler, 'function', 'Toggle behavior must attach to the control');

clickHandler();
assert.equal(input.type, 'text');
assert.equal(input.value, 'KeepMe!1', 'Showing a password must not change its value');
assert.equal(attributes.get('aria-label'), 'Hide password');
assert.equal(attributes.get('aria-pressed'), 'true');
assert.equal(label.textContent, 'Hide password');

clickHandler();
assert.equal(input.type, 'password');
assert.equal(input.value, 'KeepMe!1', 'Hiding a password must not change its value');
assert.equal(attributes.get('aria-label'), 'Show password');
assert.equal(attributes.get('aria-pressed'), 'false');
assert.equal(label.textContent, 'Show password');

console.log('Password visibility contract tests passed.');
