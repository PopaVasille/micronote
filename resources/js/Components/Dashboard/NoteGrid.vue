<script setup>
import NoteCard from './NoteCard.vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

defineProps({
    notes: Array,
    isLoading: Boolean,
});

const emit = defineEmits(['toggleFavorite', 'toggleCompleted', 'openNoteDetails']);

const handleToggleFavorite = (note) => {
    emit('toggleFavorite', note);
};

const handleToggleCompleted = (note) => {
    emit('toggleCompleted', note);
};

const handleOpenNoteDetails = (note) => {
    emit('openNoteDetails', note);
};
</script>

<template>
    <div>
        <!-- Loading indicator -->
        <div v-if="isLoading" class="flex justify-center items-center h-64">
            <svg class="animate-spin h-8 w-8 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none"
                 viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                        stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor"
                      d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
        </div>

        <!-- No notes message -->
        <div v-else-if="!notes || notes.length === 0" class="flex flex-col items-center justify-center h-64">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-gray-400" fill="none"
                 viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
            </svg>
            <p class="mt-4 text-gray-500">{{ t('dashboard.no_notes_placeholder') }}</p>
            <button class="mt-4 px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600">
                {{ t('dashboard.add_first_note') }}
            </button>
        </div>

        <!-- Notes Grid -->
        <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <NoteCard
                v-for="note in notes"
                :key="note.id"
                :note="note"
                @toggleFavorite="handleToggleFavorite"
                @toggleCompleted="handleToggleCompleted"
                @openNoteDetails="handleOpenNoteDetails"
            />
        </div>
    </div>
</template>
