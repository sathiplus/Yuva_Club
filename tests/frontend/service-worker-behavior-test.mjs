import assert from 'node:assert/strict';
import vm from 'node:vm';
import { readFile } from 'node:fs/promises';

const root = new URL('../../', import.meta.url);
const source = await readFile(new URL('service-worker.js', root), 'utf8');
const origin = 'https://release2.example.test';
const listeners = new Map();
const cacheStores = new Map();
const fetched = [];

const responseText = async (response) => response.clone().text();
const cacheApi = (name) => {
  if (!cacheStores.has(name)) cacheStores.set(name, new Map());
  const store = cacheStores.get(name);
  return {
    async addAll() {},
    async put(request, response) {
      const key = typeof request === 'string' ? request : new URL(request.url).pathname;
      store.set(key, response.clone());
    },
  };
};

cacheStores.set('yuva-club-demo-request-v1', new Map([
  ['/programs.html', new Response('OLD PROGRAMS')],
]));

const context = vm.createContext({
  URL,
  Request,
  Response,
  Error,
  Promise,
  console,
  self: {
    location: { origin },
    addEventListener(type, callback) { listeners.set(type, callback); },
    skipWaiting() {},
    clients: { claim() {} },
  },
  caches: {
    open: async (name) => cacheApi(name),
    keys: async () => [...cacheStores.keys()],
    delete: async (name) => cacheStores.delete(name),
    match: async (request) => {
      const key = typeof request === 'string' ? request : new URL(request.url).pathname;
      for (const store of cacheStores.values()) {
        if (store.has(key)) return store.get(key).clone();
      }
      return undefined;
    },
  },
  fetch: async (request) => {
    fetched.push({ url: request.url, cache: request.cache });
    return new Response(request.cache === 'reload' ? 'NEW HTML' : 'STALE HTTP CACHE', {
      status: 200,
      headers: { 'Content-Type': 'text/html' },
    });
  },
});

vm.runInContext(source, context, { filename: 'service-worker.js' });

let installWork;
listeners.get('install')({ waitUntil(work) { installWork = work; } });
await installWork;

const currentCache = cacheStores.get('yuva-club-demo-request-v2');
assert.ok(currentCache, 'new service-worker cache must be created');
assert.equal(await responseText(currentCache.get('/programs.html')), 'NEW HTML', 'fresh HTML must seed the new cache');
assert.ok(
  fetched.filter(({ url }) => url.endsWith('.html')).every(({ cache }) => cache === 'reload'),
  'every precached HTML request must bypass the browser HTTP cache',
);

let activationWork;
listeners.get('activate')({ waitUntil(work) { activationWork = work; } });
await activationWork;
assert.equal(cacheStores.has('yuva-club-demo-request-v1'), false, 'activation must remove the old cache');

let navigationResponse;
const navigationRequest = new Request(`${origin}/programs.html`);
Object.defineProperty(navigationRequest, 'mode', { value: 'navigate' });
listeners.get('fetch')({
  request: navigationRequest,
  respondWith(work) { navigationResponse = work; },
});
const response = await navigationResponse;
assert.equal(await response.text(), 'NEW HTML', 'normal navigation must return fresh network HTML');
assert.equal(fetched.at(-1).cache, 'reload', 'normal navigation must bypass stale browser HTTP cache');
assert.equal(await responseText(currentCache.get('/programs.html')), 'NEW HTML', 'fresh navigation must update the service-worker cache');

console.log('PASS service worker rejects stale HTTP-cache HTML during install and navigation');
