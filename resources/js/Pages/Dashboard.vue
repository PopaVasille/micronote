<script setup>
import { useI18n } from 'vue-i18n';
import { Head, router, usePage } from '@inertiajs/vue3';
import { ref, onMounted, computed, onBeforeUnmount, watch } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import CreateNoteModal from '@/Components/Note/CreateNoteModal.vue';
import NoteDetailModal from '@/Components/Note/NoteDetailModal.vue';
import offlineStorage from '@/utils/offlineStorage';

import TelegramAlert from '@/Components/Dashboard/TelegramAlert.vue';
import OfflineStatusBanner from '@/Components/Dashboard/OfflineStatusBanner.vue';
import DashboardSidebar from '@/Components/Dashboard/DashboardSidebar.vue';
import DashboardHeader from '@/Components/Dashboard/DashboardHeader.vue';
import FilterToolbar from '@/Components/Dashboard/FilterToolbar.vue';
import NoteGrid from '@/Components/Dashboard/NoteGrid.vue';

const { t } = useI18n();
const page = usePage();

// State Management
const isLoading = ref(true);
const currentFilter = ref(page.props.filter || 'all');
const searchQuery = ref(page.props.search || '');
const showSidebar = ref(true); // For mobile
const showCreateModal = ref(false);
const showNoteModal = ref(false);
const selectedNote = ref(null);
const isOffline = ref(false);
const hasPendingChanges = ref(false);

const notes = computed(() => page.props.notes || []);
const user = computed(() => page.props.auth.user);

// Methods
const fetchNotes = (filter = currentFilter.value, search = searchQuery.value) => {
    isLoading.value = true;
    router.get(route('dashboard'), {
        filter: filter,
        search: search,
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        onSuccess: (updatedPage) => {
            currentFilter.value = updatedPage.props.filter || filter;
        },
        onError: () => {
            console.error('A apărut o eroare la încărcarea notițelor.');
        },
        onFinish: () => {
            isLoading.value = false;
        }
    });
};

let debounceTimeout = null;
const debouncedSearch = () => {
    clearTimeout(debounceTimeout);
    debounceTimeout = setTimeout(() => {
        fetchNotes();
    }, 300);
};

const toggleFavorite = (note) => {
    router.post(route('notes.toggle-favorite', note.id), {}, {
        preserveState: true,
        preserveScroll: true,
    });
};

const toggleCompleted = (note) => {
    router.post(route('notes.toggle-completed', note.id), {}, {
        preserveState: true,
        preserveScroll: true,
    });
};

const updateOnlineStatus = async () => {
    isOffline.value = !navigator.onLine;
    if (navigator.onLine) {
        hasPendingChanges.value = await offlineStorage.hasPendingNotes();
        if (hasPendingChanges.value) {
            const registration = await navigator.serviceWorker.ready;
            await registration.sync.register('syncNotes');
            setTimeout(() => fetchNotes(currentFilter.value), 1000);
        }
    }
};

// Modal Handlers
const openNoteDetails = (note) => {
    selectedNote.value = note;
    showNoteModal.value = true;
};

const closeModal = () => {
    showNoteModal.value = false;
    selectedNote.value = null;
};

const handleNoteUpdate = (updatedNote) => {
    if (!notes.value) return;
    const index = notes.value.findIndex(n => n.id === updatedNote.id);
    if (index !== -1) {
        const updatedNotes = [...notes.value];
        updatedNotes[index] = updatedNote;
        page.props.notes = updatedNotes;
    }
    fetchNotes(); // Re-fetch to be sure
};

const handleNoteCreated = () => {
    showCreateModal.value = false;
    setTimeout(() => fetchNotes(currentFilter.value), 500);
};

const openCreateModal = () => {
    showCreateModal.value = true;
};

// Lifecycle Hooks
onMounted(() => {
    if (!page.props.notes) {
        fetchNotes(currentFilter.value);
    } else {
        isLoading.value = false;
    }
    window.addEventListener('online', updateOnlineStatus);
    window.addEventListener('offline', updateOnlineStatus);
    updateOnlineStatus();
});

onBeforeUnmount(() => {
    window.removeEventListener('online', updateOnlineStatus);
    window.removeEventListener('offline', updateOnlineStatus);
});

watch(searchQuery, debouncedSearch);

</script>

<template>
    <Head :title="t('common.notes')"/>
    <AuthenticatedLayout>
        <TelegramAlert :show="!user.telegram_id" />
        <OfflineStatusBanner :is-offline="isOffline" :has-pending-changes="hasPendingChanges" />

        <div class="flex h-screen bg-gray-50">
            <DashboardSidebar
                :show-sidebar="showSidebar"
                :current-filter="currentFilter"
                :notes-count="notes.length"
                @toggle-sidebar="showSidebar = !showSidebar"
                @open-create-modal="openCreateModal"
                @filter-changed="fetchNotes($event)"
            />

            <div class="flex-1 flex flex-col min-h-screen overflow-hidden">
                <DashboardHeader
                    :current-filter="currentFilter"
                    v-model:search-query="searchQuery"
                    :user="user"
                    @toggle-sidebar="showSidebar = !showSidebar"
                />

                <main class="flex-1 p-4 overflow-auto">
                    <FilterToolbar
                        :current-filter="currentFilter"
                        @filter-changed="fetchNotes($event)"
                    />
                    <NoteGrid
                        :notes="notes"
                        :is-loading="isLoading"
                        @toggle-favorite="toggleFavorite"
                        @toggle-completed="toggleCompleted"
                        @open-note-details="openNoteDetails"
                    />
                </main>
            </div>
        </div>

        <CreateNoteModal
            :show="showCreateModal"
            @close="showCreateModal = false"
            @noteCreated="handleNoteCreated"
        />
        <NoteDetailModal
            :show="showNoteModal"
            :note="selectedNote"
            @close="closeModal"
            @noteUpdated="handleNoteUpdate"
        />
    </AuthenticatedLayout>
</template>

