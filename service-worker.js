const CACHE_NAME = 'yuva-club-app-v10';
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
  '/assets/site.css?v=20260714-app-landing',
  '/assets/app.js?v=20260714-app-landing',
  '/assets/logo.png',
  '/assets/app-icon-180.png',
  '/assets/app-icon-192.png',
  '/assets/app-icon-512.png',
  '/assets/app-icon-maskable-512.png',
  '/assets/home-hero.png',
  '/assets/topics-source.png'
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
      Promise.all(keys.filter((key) => key !== CACHE_NAME).map((key) => caches.delete(key)))
    )
  );
  self.clients.claim();
});

self.addEventListener('fetch', (event) => {
  const request = event.request;
  const url = new URL(request.url);

  if (request.method !== 'GET' || url.origin !== self.location.origin) {
    return;
  }

  if (request.mode === 'navigate') {
    event.respondWith(
      fetch(request)
        .then((response) => {
          const copy = response.clone();
          caches.open(CACHE_NAME).then((cache) => cache.put(request, copy));
          return response;
        })
        .catch(() => caches.match(request).then((cached) => cached || caches.match('/index.html') || caches.match('/offline.html')))
    );
    return;
  }

  if (url.pathname.endsWith('.php')) {
    event.respondWith(
      fetch(request)
    );
    return;
  }

  event.respondWith(
    fetch(request)
        .then((response) => {
          const copy = response.clone();
          caches.open(CACHE_NAME).then((cache) => cache.put(request, copy));
          return response;
        })
      .catch(() => caches.match(request))
  );
});
