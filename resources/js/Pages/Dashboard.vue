<script setup>
import { useI18n } from 'vue-i18n';
import { Head, router, usePage } from '@inertiajs/vue3';
import { ref, onMounted, computed, onBeforeUnmount, watch } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import CreateNoteModal from '@/Components/Note/CreateNoteModal.vue';
import NoteDetailModal from '@/Components/Note/NoteDetailModal.vue';
import offlineStorage from '@/utils/offlineStorage';

import MessagingPlatformsAlert from '@/Components/Dashboard/MessagingPlatformsAlert.vue';
import OfflineStatusBanner from '@/Components/Dashboard/OfflineStatusBanner.vue';
import DashboardSidebar from '@/Components/Dashboard/DashboardSidebar.vue';
import DashboardHeader from '@/Components/Dashboard/DashboardHeader.vue';
import FilterToolbar from '@/Components/Dashboard/FilterToolbar.vue';
import NoteGrid from '@/Components/Dashboard/NoteGrid.vue';
import UnifiedTasksRemindersCard from '@/Components/Dashboard/UnifiedTasksRemindersCard.vue';

const { t } = useI18n();
const page = usePage();

// State Management
const isLoading = ref(true);
const currentFilter = ref(page.props.filter || 'all');
const searchQuery = ref(page.props.search || '');
const sortDirection = ref(page.props.sort || 'desc');
const showSidebar = ref(false); // For mobile
const showCreateModal = ref(false);
const showNoteModal = ref(false);
const selectedNote = ref(null);
const isOffline = ref(false);
const hasPendingChanges = ref(false);

const notes = computed(() => page.props.notes || []);
const user = computed(() => page.props.auth.user);

// Methods
const fetchNotes = (filter = currentFilter.value, search = searchQuery.value, sort = sortDirection.value) => {
    isLoading.value = true;
    console.log('fetchNotes called with filter:', filter, 'search:', search, 'sort:', sort);
    router.get(route('dashboard'), {
        filter: filter,
        search: search,
        sort: sort,
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        onSuccess: (updatedPage) => {
            currentFilter.value = updatedPage.props.filter || filter;
            sortDirection.value = updatedPage.props.sort || sort;
        },
        onError: () => {
            console.error(t('errors.loadingNotes'));
        },
        onFinish: () => {
            isLoading.value = false;
        }
    });
};

const handleSortChange = (direction) => {
    sortDirection.value = direction;
    fetchNotes();
};

let debounceTimeout = null;
const debouncedSearch = () => {
    clearTimeout(debounceTimeout);
    debounceTimeout = setTimeout(() => {
        console.log('Debounced search triggered with:', searchQuery.value);
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
        if (hasPendingChanges.value && 'serviceWorker' in navigator) {
            try {
                const registration = await navigator.serviceWorker.ready;
                await registration.sync.register('syncNotes');
                setTimeout(() => fetchNotes(currentFilter.value), 1000);
            } catch (error) {
                console.warn('Service Worker sync failed:', error);
            }
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

// Summary card handlers
const handleReminderCompleted = () => {
    // Refresh the notes list when a reminder is completed
    setTimeout(() => fetchNotes(currentFilter.value), 1000);
};

const handleTaskCompleted = () => {
    // Refresh the notes list when a task is completed
    setTimeout(() => fetchNotes(currentFilter.value), 1000);
};

const handleTaskClicked = async (task) => {
    // Find the full note data for this task
    try {
        const response = await fetch(`/api/notes/${task.id}`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        
        if (response.ok) {
            const noteData = await response.json();
            openNoteDetails(noteData);
        } else {
            console.error('Failed to fetch note details');
        }
    } catch (error) {
        console.error('Error fetching note details:', error);
    }
};

const handleReminderClicked = async (reminder) => {
    // Find the full note data for this reminder
    try {
        const response = await fetch(`/api/notes/${reminder.note_id}`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        
        if (response.ok) {
            const noteData = await response.json();
            openNoteDetails(noteData);
        } else {
            console.error('Failed to fetch note details');
        }
    } catch (error) {
        console.error('Error fetching note details:', error);
    }
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
        <MessagingPlatformsAlert :show="!user.telegram_id && !user.whatsapp_id" />
        <OfflineStatusBanner :is-offline="isOffline" :has-pending-changes="hasPendingChanges" />

        <div class="flex h-screen bg-gray-50">
            <!-- Backdrop for mobile -->
            <div v-if="showSidebar" @click="showSidebar = false" class="fixed inset-0 z-20 bg-black bg-opacity-50 md:hidden"></div>
            
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
                    v-model="searchQuery"
                    :user="user"
                    @toggle-sidebar="showSidebar = !showSidebar"
                />

                <main class="flex-1 p-4 overflow-auto">
                    <!-- Summary Section - Mobile-First Design -->
                    <div class="mb-6">
                        <UnifiedTasksRemindersCard
                            @task-completed="handleTaskCompleted"
                            @reminder-completed="handleReminderCompleted"
                            @filter-tasks="currentFilter = 'task'"
                            @filter-reminders="currentFilter = 'reminder'"
                            @task-clicked="handleTaskClicked"
                            @reminder-clicked="handleReminderClicked"
                        />
                    </div>

                    <!-- Main Notes Section -->
                    <div class="space-y-4">
                        <FilterToolbar
                            :current-filter="currentFilter"
                            :sort-direction="sortDirection"
                            @filter-changed="fetchNotes($event)"
                            @sort-changed="handleSortChange"
                        />
                        <NoteGrid
                            :notes="notes"
                            :is-loading="isLoading"
                            @toggle-favorite="toggleFavorite"
                            @toggle-completed="toggleCompleted"
                            @open-note-details="openNoteDetails"
                            @open-create-modal="openCreateModal"
                        />
                    </div>
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