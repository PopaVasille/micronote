// public/serviceworker.js
const CACHE_NAME = 'micronote-v1';
const urlsToCache = [
    '/',
    '/dashboard',
    '/css/app.css',
    '/js/app.js',
    '/manifest.json',
    '/icons/icon-72x72.png',
    '/icons/icon-96x96.png',
    '/icons/icon-128x128.png',
    '/icons/icon-144x144.png',
    '/icons/icon-152x152.png',
    '/icons/icon-192x192.png',
    '/icons/icon-384x384.png',
    '/icons/icon-512x512.png'
];

// La instalarea Service Worker
self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(cache => {
                console.log('Cache deschis');
                return cache.addAll(urlsToCache);
            })
    );
});

// La fetch - verifică cache-ul mai întâi
self.addEventListener('fetch', event => {
    event.respondWith(
        caches.match(event.request)
            .then(response => {
                if (response) {
                    return response; // Returnează resursa din cache
                }
                return fetch(event.request); // Altfel, fă cererea la server
            })
    );
});
