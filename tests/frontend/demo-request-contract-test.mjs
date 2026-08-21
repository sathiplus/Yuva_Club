import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';

const root = new URL('../../', import.meta.url);
const page = await readFile(new URL('demo-request.php', root), 'utf8');
for (const field of ['organization_name','organization_type','contact_name','email','phone','city_state','student_count','student_age_range','program_interest','preferred_contact_time','message']) {
  assert.ok(page.includes(`name="${field}"`), `Demo form missing ${field}`);
}
for (const contract of ['csrf_field()', 'form_started_at', 'name="website"', 'assets/public-site.css', 'assets/demo-request.css', 'Thank you. Your request was received.']) {
  assert.ok(page.includes(contract), `Demo page missing ${contract}`);
}
for (const file of ['index.html','programs.html','partners.html','contact.html']) {
  const source = await readFile(new URL(file, root), 'utf8');
  if (/Request a (School or Organization Demo|demo through YUVA Club)/i.test(source)) {
    assert.ok(source.includes('href="demo-request.php"'), `${file} demo CTA must use the dedicated route`);
  }
}
assert.doesNotMatch(page, /create_(organization|admin)|INSERT\s+INTO\s+organizations/i);
console.log('Demo request frontend contract tests passed.');
