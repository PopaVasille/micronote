// resources/js/utils/offlineStorage.js
export default {
    /**
     * Deschide baza de date IndexedDB
     */
    openDB() {
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
    },

    /**
     * Salvează o notiță în storage-ul local pentru sincronizare ulterioară
     */
    async saveNoteForSync(noteData, csrf) {
        try {
            const db = await this.openDB();
            const transaction = db.transaction(['pendingNotes'], 'readwrite');
            const store = transaction.objectStore('pendingNotes');

            await store.add({
                data: noteData,
                csrf,
                timestamp: Date.now()
            });

            // Încearcă să programeze o sincronizare
            if ('serviceWorker' in navigator && 'SyncManager' in window) {
                const registration = await navigator.serviceWorker.ready;
                await registration.sync.register('syncNotes');
            }

            return true;
        } catch (error) {
            console.error('Eroare la salvarea notiței pentru sincronizare:', error);
            return false;
        }
    },

    /**
     * Verifică dacă există notițe în așteptare pentru sincronizare
     */
    async hasPendingNotes() {
        try {
            const db = await this.openDB();
            const transaction = db.transaction(['pendingNotes'], 'readonly');
            const store = transaction.objectStore('pendingNotes');
            const count = await store.count();

            return count > 0;
        } catch (error) {
            console.error('Eroare la verificarea notițelor în așteptare:', error);
            return false;
        }
    }
};
