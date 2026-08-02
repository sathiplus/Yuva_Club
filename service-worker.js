const CACHE_NAME = 'yuva-club-release-1-0-2-v1';
const STATIC_ASSETS = [
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
  '/manifest.webmanifest',
  '/assets/site.css?v=release-1.0.2-20260802',
  '/assets/public-site.css?v=release-1.0.2-20260802',
  '/assets/app.js?v=release-1.0.2-20260802',
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

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => cache.addAll(STATIC_ASSETS))
  );
  self.skipWaiting();
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((keys) =>
      Promise.all(
        keys
          .filter((key) => key.startsWith('yuva-club-') && key !== CACHE_NAME)
          .map((key) => caches.delete(key))
      )
    )
  );
  self.clients.claim();
});

self.addEventListener('fetch', (event) => {
  const request = event.request;
  const url = new URL(request.url);
  const path = url.pathname.toLowerCase();
  const isSwRequest = path.endsWith('/service-worker.js');
  const isPhpRequest = path.endsWith('.php');
  const isNavigationRequest = request.mode === 'navigate';
  const isHtml = path.endsWith('.html') || isNavigationRequest;
  const isCss = path.endsWith('.css');
  const isJavaScript = path.endsWith('.js');
  const shouldCacheOnSuccess = isNavigationRequest || isHtml || isCss || isJavaScript || isSwRequest;

  if (request.method !== 'GET' || url.origin !== self.location.origin) {
    return;
  }

  if (isPhpRequest) {
    event.respondWith(fetch(request));
    return;
  }

  if (isNavigationRequest) {
    event.respondWith(
      fetch(request)
        .then((response) => {
          if (response && response.ok) {
            const copy = response.clone();
            caches.open(CACHE_NAME).then((cache) => cache.put(request, copy));
          }
          return response;
        })
        .catch(() => caches.match(request).then((cached) => cached || caches.match('/index.html') || caches.match('/offline.html')))
    );
    return;
  }

  event.respondWith(
    fetch(request)
        .then((response) => {
          if (shouldCacheOnSuccess && response && response.ok) {
            const copy = response.clone();
            caches.open(CACHE_NAME).then((cache) => cache.put(request, copy));
          }
          return response;
        })
      .catch(() => caches.match(request))
  );
});
