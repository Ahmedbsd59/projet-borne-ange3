// Service Worker — BorneInteract PWA
const CACHE = 'borneinteract-v4';
const ASSETS = [
  '/application_mobile/',
  '/application_mobile/index.html',
  '/application_mobile/manifest.json',
];

self.addEventListener('install', e => {
  e.waitUntil(
    caches.open(CACHE).then(c => c.addAll(ASSETS)).then(() => self.skipWaiting())
  );
});

self.addEventListener('activate', e => {
  e.waitUntil(
    caches.keys().then(keys =>
      Promise.all(keys.filter(k => k !== CACHE).map(k => caches.delete(k)))
    ).then(() => self.clients.claim())
  );
});

// Network first pour les API, cache first pour les assets
self.addEventListener('fetch', e => {
  if (e.request.url.includes('/api/')) {
    // Toujours réseau pour les données
    e.respondWith(fetch(e.request).catch(() => new Response('{"success":false,"message":"Hors ligne"}', {
      headers: { 'Content-Type': 'application/json' }
    })));
  } else if (e.request.url.includes('/application_mobile/index.html') || e.request.url.endsWith('/application_mobile/')) {
    // Network first pour index.html (toujours la version la plus récente)
    e.respondWith(
      fetch(e.request).then(res => {
        const clone = res.clone();
        caches.open(CACHE).then(c => c.put(e.request, clone));
        return res;
      }).catch(() => caches.match(e.request))
    );
  } else {
    // Cache first pour les autres assets (fonts, manifest…)
    e.respondWith(
      caches.match(e.request).then(cached => cached || fetch(e.request))
    );
  }
});
