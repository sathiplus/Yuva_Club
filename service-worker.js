const CACHE_NAME = 'yuva-club-public-only-v3';
const HTML_ASSETS = [
  '/index.html',
  '/programs.html',
  '/resources.html',
  '/about.html',
  '/faq.html',
  '/partners.html',
  '/safety.html',
  '/privacy.html',
  '/terms.html',
  '/contact.html',
  '/offline.html',
];
const STATIC_ASSETS = [
  '/manifest.webmanifest',
  '/assets/site.css?v=release-1.0.2-20260802',
  '/assets/public-site.css?v=release-1.0.2-20260802',
  '/assets/app.js?v=demo-request-v2',
  '/assets/website-v3-hero.webp',
  '/assets/logo.png',
  '/assets/app-icon-180.png',
  '/assets/app-icon-192.png',
  '/assets/app-icon-512.png',
  '/assets/app-icon-maskable-512.png',
  '/icons/icon-192.png',
  '/icons/icon-512.png',
  '/icons/maskable-icon-192.png',
  '/icons/maskable-icon-512.png',
  '/icons/apple-touch-icon.png',
  '/icons/favicon-32x32.png',
  '/assets/logo-public.webp',
  '/assets/student-hero-illustration.svg',
  '/assets/student-ai-coach-illustration.svg',
  '/assets/student-action-clipboard.svg',
  '/assets/student-leadership-journey-illustration.svg',
  '/assets/student-presentation-illustration.svg'
];

// Only exact, reviewed public URLs may enter persistent storage. Queries are
// part of the allowlist: a dynamic response must not masquerade as an asset.
const PUBLIC_URLS = new Set([...HTML_ASSETS, ...STATIC_ASSETS].map(path => new URL(path, self.location.origin).href));
function isPublicRequest(request) {
  return request.method === 'GET' && PUBLIC_URLS.has(request.url);
}
function cacheableResponse(request, response) {
  return response && response.ok && response.type !== 'opaque'
    && !response.redirected && response.url === request.url
    && !/no-store|private/i.test(response.headers.get('Cache-Control') || '');
}
async function publicResponse(request) {
  try {
    const response = await fetch(new Request(request, { cache: 'reload' }));
    if (cacheableResponse(request, response)) {
      const cache = await caches.open(CACHE_NAME);
      await cache.put(request, response.clone());
    }
    return response;
  } catch (error) {
    const cache = await caches.open(CACHE_NAME);
    const cached = await cache.match(request);
    if (cached) return cached;
    if (request.mode === 'navigate') {
      const offline = await cache.match('/offline.html');
      if (offline) return offline;
    }
    throw error;
  }
}
self.addEventListener('install', event => {
  event.waitUntil((async () => {
    // An unavailable optional public asset must not prevent a privacy update.
    await Promise.allSettled([...PUBLIC_URLS].map(url => publicResponse(new Request(url))));
    await self.skipWaiting();
  })());
});
self.addEventListener('activate', event => {
  event.waitUntil((async () => {
    // Purge the old cache, including private path-info entries, before claiming.
    await Promise.all((await caches.keys())
      .filter(key => key.startsWith('yuva-club-') && key !== CACHE_NAME)
      .map(key => caches.delete(key)));
    await self.clients.claim();
  })());
});
self.addEventListener('fetch', event => {
  if (!isPublicRequest(event.request)) {
    // Never consult persistent storage or fall back to cached private content.
    return;
  }
  event.respondWith(publicResponse(event.request));
});
