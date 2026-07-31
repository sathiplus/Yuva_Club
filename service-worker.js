const CACHE_NAME = 'yuva-club-release-1-0-1-v1';
const VERSIONED_CSS_JS = 'release-1.0.1-20260731';
const STATIC_ASSETS = [
  '/index.html',
  '/app.html',
  '/programs.html',
  '/challenges.html',
  '/safety.html',
  '/curriculum.html',
  '/stories.html',
  '/resources.html',
  '/offline.html',
  '/manifest.webmanifest',
  '/assets/site.css?v=' + VERSIONED_CSS_JS,
  '/assets/public-site.css?v=' + VERSIONED_CSS_JS,
  '/assets/app.js?v=' + VERSIONED_CSS_JS,
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
  '/assets/student-ai-coach-illustration.svg'
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

  if (isPhpRequest) {
    event.respondWith(
      fetch(request)
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
