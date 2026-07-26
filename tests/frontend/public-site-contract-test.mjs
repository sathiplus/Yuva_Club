import assert from 'node:assert/strict';
import { readdir, readFile, stat } from 'node:fs/promises';

const root = new URL('../../', import.meta.url);
const publicPages = [
  'index.html',
  'programs.html',
  'challenges.html',
  'curriculum.html',
  'stories.html',
  'resources.html',
  'app.html',
  'safety.html',
  'offline.html',
];
const phpPages = [
  'registration.php',
  'portal-login.php',
  'parent-login.php',
  'admin-login.php',
  'leaderboard.php',
];
const topicNames = (await readdir(new URL('pages/', root)))
  .filter((name) => name.endsWith('.html'));
const auditedSources = [];

assert.ok(topicNames.length > 100, 'Topic page inventory is unexpectedly incomplete');

for (const name of publicPages) {
  const source = await readFile(new URL(name, root), 'utf8');
  auditedSources.push([name, source]);
  assert.ok(source.includes('assets/public-site.css?v=1'), `${name} lacks public design system`);
  assert.ok(source.includes('class="public-skip-link"'), `${name} lacks skip navigation`);
  assert.ok(source.includes('id="main-content"'), `${name} lacks main skip target`);
  assert.match(source, /<html lang="en">/);
  assert.match(source, /<title>[^<]+<\/title>/);
}

for (const name of topicNames) {
  const source = await readFile(new URL(`pages/${name}`, root), 'utf8');
  auditedSources.push([`pages/${name}`, source]);
  assert.ok(source.includes('../assets/public-site.css?v=1'), `${name} lacks public design system`);
  assert.ok(source.includes('class="public-skip-link"'), `${name} lacks skip navigation`);
  assert.ok(source.includes('id="main-content"'), `${name} lacks main skip target`);
}

for (const name of phpPages) {
  const source = await readFile(new URL(name, root), 'utf8');
  auditedSources.push([name, source]);
  assert.ok(source.includes('public-site.css?v=1'), `${name} lacks public design system`);
  assert.ok(source.includes('class="public-skip-link"'), `${name} lacks skip navigation`);
  assert.ok(source.includes('id="main-content"'), `${name} lacks main skip target`);
}

const headerSources = await Promise.all(
  publicPages.slice(0, -1).map((name) => readFile(new URL(name, root), 'utf8'))
);
const expectedNavigation = [
  'index.html',
  'programs.html',
  'challenges.html',
  'curriculum.html',
  'resources.html',
  'stories.html',
  'leaderboard.php',
  'app.html',
  'safety.html',
  'registration.php',
  'portal-login.php',
  'parent-login.php',
  'admin-login.php',
];
for (const [index, source] of headerSources.entries()) {
  for (const href of expectedNavigation) {
    assert.ok(source.includes(`href="${href}"`), `${publicPages[index]} navigation misses ${href}`);
  }
}

const css = await readFile(new URL('assets/public-site.css', root), 'utf8');
assert.ok(css.includes('@media (max-width: 720px)'));
assert.ok(css.includes('@media (prefers-reduced-motion: reduce)'));
assert.ok(css.includes('.public-skip-link:focus'));
assert.ok(!/https?:\/\//.test(css), 'Public CSS must not use external runtime dependencies');

for (const asset of ['assets/logo.png', 'assets/yuva-symbol.png', 'assets/public-site.css']) {
  assert.ok((await stat(new URL(asset, root))).isFile(), `Missing local asset: ${asset}`);
}

for (const [name, source] of auditedSources) {
  const references = [...source.matchAll(/(?:href|src)="([^"]+)"/g)]
    .map((match) => match[1])
    .filter((value) =>
      value !== ''
      && !value.startsWith('#')
      && !value.startsWith('http:')
      && !value.startsWith('https:')
      && !value.startsWith('mailto:')
      && !value.startsWith('data:')
    );
  for (const reference of references) {
    const cleanReference = reference.split('#')[0].split('?')[0];
    if (cleanReference === '') continue;
    const target = new URL(cleanReference, new URL(name, root));
    assert.ok((await stat(target)).isFile(), `${name} has a broken local reference: ${reference}`);
  }
}

console.log(`PASS ${publicPages.length + phpPages.length + topicNames.length} public user-facing pages`);
console.log('PASS shared navigation, branding, and local Design System V1 assets');
console.log('PASS public links and local asset references');
console.log('PASS skip navigation, responsive behavior, and reduced motion');
console.log('PASS no external runtime CSS dependency');
