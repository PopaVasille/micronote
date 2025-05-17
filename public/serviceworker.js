// public/serviceworker.js
const CACHE_NAME = 'micronote-v1';
const staticResources = [
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
// Resurse dinamice care vor fi actualizate frecvent
const dynamicResources = [
    '/api/notes'
];
// La instalarea Service Worker
self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(cache => {
                console.log('Cache deschis');
                return cache.addAll(staticResources);
            })
            .then(() => self.skipWaiting()) // Forțează Service Worker-ul să devină activ imediat
    );
});

// Activarea Service Worker-ului (curățarea vechilor cache-uri)
self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys().then(cacheNames => {
            return Promise.all(
                cacheNames
                    .filter(cacheName => cacheName !== CACHE_NAME)
                    .map(cacheName => caches.delete(cacheName))
            );
        }).then(() => self.clients.claim()) // Preia controlul asupra paginilor deschise
    );
});
// Strategia de caching pentru cereri
self.addEventListener('fetch', event => {
    // Exclude cererile non-GET
    if (event.request.method !== 'GET') {
        return;
    }

    // Exclude cererile către API care modifică date (POST, PUT, DELETE)
    if (event.request.url.includes('/api/') && !dynamicResources.some(resource => event.request.url.includes(resource))) {
        return;
    }

    event.respondWith(
        caches.match(event.request)
            .then(cachedResponse => {
                // Cache First Strategy pentru resurse statice
                if (cachedResponse && staticResources.some(resource => event.request.url.includes(resource))) {
                    return cachedResponse;
                }

                // Network First Strategy pentru resurse dinamice
                return fetch(event.request)
                    .then(response => {
                        // Clonăm răspunsul, deoarece poate fi utilizat o singură dată
                        const responseToCache = response.clone();

                        // Verificăm dacă răspunsul este valid
                        if (response.status === 200) {
                            caches.open(CACHE_NAME)
                                .then(cache => {
                                    cache.put(event.request, responseToCache);
                                });
                        }

                        return response;
                    })
                    .catch(() => {
                        // Dacă rețeaua eșuează, încercăm să returnăm din cache
                        return cachedResponse ||
                            // Sau putem returna o pagină de rezervă pentru ruta /dashboard
                            (event.request.url.includes('/dashboard') ? caches.match('/dashboard') : null);
                    });
            })
    );
});

// Gestionarea sincronizării în fundal
self.addEventListener('sync', event => {
    if (event.tag === 'syncNotes') {
        event.waitUntil(syncNotes());
    }
});

// Implementarea funcției de sincronizare (simplificată)
async function syncNotes() {
    // Verifică datele nepostate în IndexedDB
    const db = await openDB();
    const pendingNotes = await db.getAll('pendingNotes');

    // Dacă există notițe în așteptare, încercăm să le trimitem la server
    if (pendingNotes.length > 0) {
        for (const note of pendingNotes) {
            try {
                const response = await fetch('/api/notes', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': note.csrf
                    },
                    body: JSON.stringify(note.data)
                });

                if (response.ok) {
                    // Dacă trimiterea a reușit, ștergem notița din IndexedDB
                    await db.delete('pendingNotes', note.id);
                }
            } catch (error) {
                console.error('Eroare la sincronizarea notiței:', error);
            }
        }
    }
}

// Funcție simplificată pentru deschiderea bazei de date IndexedDB
function openDB() {
    return new Promise((resolve, reject) => {
        const request = indexedDB.open('MicroNoteDB', 1);

        request.onupgradeneeded = e => {
            const db = e.target.result;
            if (!db.objectStoreNames.contains('pendingNotes')) {
                db.createObjectStore('pendingNotes', { keyPath: 'id', autoIncrement: true });
            }
        };

        request.onsuccess = e => resolve(e.target.result);
        request.onerror = e => reject(e.target.error);
    });
}
