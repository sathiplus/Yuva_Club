import assert from 'node:assert/strict';
import { readdir, readFile } from 'node:fs/promises';

const root = new URL('../../', import.meta.url);
const stylesheetVersion = 'release-1.0.2-20260802';
const serviceWorker = await readFile(new URL('service-worker.js', root), 'utf8');
const cachedAppMatch = serviceWorker.match(/['"]\/(assets\/app\.js\?v=[^'"]+)['"]/);
assert.ok(cachedAppMatch, 'service worker must cache the versioned public app asset');
const demoRequestAppAsset = cachedAppMatch[1];
const releaseAppAsset = `assets/app.js?v=${stylesheetVersion}`;
const demoRequestPages = new Set(['index.html', 'programs.html', 'partners.html', 'contact.html']);
const appSource = await readFile(new URL('assets/app.js', root), 'utf8');
assert.match(appSource, /service-worker\.js\?v=\d+/, 'public app must register a versioned service worker');
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
  const expectedAppAsset = demoRequestPages.has(name) ? demoRequestAppAsset : releaseAppAsset;
  assert.ok(source.includes(`${prefix}assets/site.css?v=${stylesheetVersion}`), `${name} has stale site.css`);
  assert.ok(source.includes(`${prefix}assets/public-site.css?v=${stylesheetVersion}`), `${name} has stale public-site.css`);
  assert.ok(source.includes(`${prefix}${expectedAppAsset}`), `${name} has the wrong public app.js version`);
  assert.ok(!source.includes('public-site.css?v=1'), `${name} retains the obsolete stylesheet version`);
}

for (const name of ['registration.php', 'portal-login.php', 'parent-login.php', 'admin-login.php', 'leaderboard.php']) {
  const source = await readFile(new URL(name, root), 'utf8');
  assert.ok(source.includes(`assets/public-site.css?v=${stylesheetVersion}`), `${name} has stale public-site.css`);
}

console.log(`PASS ${rootPages.length + topicPages.length + 5} public routes use the approved stylesheet and public app assets`);
