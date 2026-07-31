import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';

const root = new URL('../../', import.meta.url);
const releaseVersion = 'release-1.0.1-20260731';

const index = await readFile(new URL('index.html', root), 'utf8');
assert.ok(index.includes(`assets/site.css?v=${releaseVersion}`), 'index.html should use the release-1.0.1 site.css version');
assert.ok(index.includes(`assets/public-site.css?v=${releaseVersion}`), 'index.html should use the release-1.0.1 public-site.css version');
assert.ok(index.includes(`assets/app.js?v=${releaseVersion}`), 'index.html should use the release-1.0.1 app.js version');
assert.ok(!index.includes('assets/public-site.css?v=1'), 'index.html must not reference public-site.css?v=1');

console.log('PASS release-1.0.1 cache version contract checks');
