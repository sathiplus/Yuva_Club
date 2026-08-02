import assert from 'node:assert/strict';
import { readdir, readFile } from 'node:fs/promises';

const root = new URL('../../', import.meta.url);
const releaseVersion = 'release-1.0.2-20260802';
const rootPages = [
  'index.html', 'programs.html', 'challenges.html', 'curriculum.html', 'stories.html',
  'resources.html', 'about.html', 'partners.html', 'faq.html', 'app.html',
  'safety.html', 'privacy.html', 'terms.html', 'contact.html', 'offline.html',
];
const topicPages = (await readdir(new URL('pages/', root)))
  .filter((name) => name.endsWith('.html'))
  .map((name) => `pages/${name}`);

for (const name of [...rootPages, ...topicPages]) {
  const source = await readFile(new URL(name, root), 'utf8');
  const prefix = name.startsWith('pages/') ? '../' : '';
  assert.ok(source.includes(`${prefix}assets/site.css?v=${releaseVersion}`), `${name} has stale site.css`);
  assert.ok(source.includes(`${prefix}assets/public-site.css?v=${releaseVersion}`), `${name} has stale public-site.css`);
  assert.ok(source.includes(`${prefix}assets/app.js?v=${releaseVersion}`), `${name} has stale app.js`);
  assert.ok(!source.includes('public-site.css?v=1'), `${name} retains the obsolete stylesheet version`);
}

for (const name of ['registration.php', 'portal-login.php', 'parent-login.php', 'admin-login.php', 'leaderboard.php']) {
  const source = await readFile(new URL(name, root), 'utf8');
  assert.ok(source.includes(`assets/public-site.css?v=${releaseVersion}`), `${name} has stale public-site.css`);
}

console.log(`PASS ${rootPages.length + topicPages.length + 5} public routes use ${releaseVersion}`);
