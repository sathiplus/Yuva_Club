import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';

const root = new URL('../../', import.meta.url);
const sw = await readFile(new URL('service-worker.js', root), 'utf8');
const app = await readFile(new URL('assets/app.js', root), 'utf8');

assert.ok(sw.includes("const CACHE_NAME = 'yuva-club-demo-request-v1'"), 'Cache name must invalidate pre-demo-request public pages');
assert.ok(app.includes("serviceWorker.register('/service-worker.js?v=15')"), 'Service-worker registration must request the demo-request cache version');
assert.ok(sw.includes('/assets/site.css?v=release-1.0.2-20260802'), 'service worker must precache versioned site.css');
assert.ok(sw.includes('/assets/public-site.css?v=release-1.0.2-20260802'), 'service worker must precache versioned public-site.css');
assert.ok(sw.includes('/assets/app.js?v=demo-request-v1'), 'service worker must precache the demo-request app.js version');
assert.ok(!sw.includes('/assets/site.css?v=20260714-pwa-install-icon'), 'stale versioned site.css URL should be removed');
assert.ok(!sw.includes('/assets/app.js?v=20260714-pwa-install-icon'), 'stale versioned app.js URL should be removed');
assert.ok(!sw.includes('yuva-club-app-v14'), 'old cache name should be removed');
assert.ok(sw.includes('if (isNavigationRequest)'), 'navigation must keep network-first behavior');
assert.ok(sw.includes('if (isPhpRequest)'), 'php requests must stay network-only');
assert.ok(sw.indexOf('if (isPhpRequest)') < sw.indexOf('if (isNavigationRequest)'), 'PHP must bypass navigation caching');
assert.ok(sw.includes('if (response && response.ok)'), 'successful network responses must be required before caching');
assert.ok(sw.includes("const isSwRequest = path.endsWith('/service-worker.js');"), 'SW file requests must be explicitly identified');
assert.ok(sw.includes("const isCss = path.endsWith('.css');"), 'css requests must be explicitly identified');
assert.ok(sw.includes("const isJavaScript = path.endsWith('.js');"), 'javascript requests must be explicitly identified');
assert.ok(sw.includes("response.ok) {"), 'network-first caching should only store successful responses');
assert.ok(sw.includes(".filter((key) => key.startsWith('yuva-club-')"), 'activation should purge old YUVA caches');

const htaccess = await readFile(new URL('.htaccess', root), 'utf8');
assert.ok(htaccess.includes('service-worker\\.js'), '.htaccess should include service worker path');
assert.ok(htaccess.includes('Cache-Control "no-cache, no-store, must-revalidate"'), 'service worker should enforce revalidation headers');

console.log('PASS service worker cache versioning, routing, and cache lifecycle checks');
