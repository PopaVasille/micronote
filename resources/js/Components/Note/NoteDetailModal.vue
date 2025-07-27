<script setup>
import { ref, watch, computed } from 'vue';
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
    show: Boolean,
    note: Object,
});

const emit = defineEmits(['close', 'noteUpdated']);

const isEditing = ref(false);

// Folosim useForm de la Inertia pentru a gestiona starea formularului de editare
const form = useForm({
    id: null,
    title: '',
    content: '',
});

// Când prop-ul `note` se schimbă (când se deschide modalul), actualizăm formularul
watch(() => props.note, (newNote) => {
    if (newNote) {
        form.id = newNote.id;
        form.title = newNote.title;
        form.content = newNote.content;
    } else {
        form.reset();
        isEditing.value = false;
    }
});

// Computed pentru tipul de notiță formatat
const noteTypeDisplay = computed(() => {
    if (!props.note) return '';

    const typeMap = {
        'task': 'Task',
        'idea': 'Idee',
        'shopping_list': 'Cumpărături',
        'event': 'Eveniment',
        'reminder': 'Reminder',
        'contact': 'Contact',
        'recipe': 'Rețetă',
        'bookmark': 'Bookmark',
        'measurement': 'Măsurătoare'
    };

    return typeMap[props.note.note_type] || 'Notiță';
});

// Computed pentru culoarea badge-ului tipului de notiță
const noteTypeBadgeClass = computed(() => {
    if (!props.note) return 'bg-gray-100 text-gray-700';

    const colorMap = {
        'task': 'bg-red-100 text-red-700',
        'idea': 'bg-blue-100 text-blue-700',
        'shopping_list': 'bg-green-100 text-green-700',
        'event': 'bg-purple-100 text-purple-700',
        'reminder': 'bg-orange-100 text-orange-700',
        'contact': 'bg-indigo-100 text-indigo-700',
        'recipe': 'bg-pink-100 text-pink-700',
        'bookmark': 'bg-cyan-100 text-cyan-700',
        'measurement': 'bg-yellow-100 text-yellow-700'
    };

    return colorMap[props.note.note_type] || 'bg-gray-100 text-gray-700';
});

const startEditing = () => {
    isEditing.value = true;
};

const cancelEditing = () => {
    // Resetează formularul la valorile originale ale notiței
    form.reset();
    isEditing.value = false;
};

const closeModal = () => {
    emit('close');
};

// Funcția de salvare
const saveChanges = () => {
    if (!props.note) return;

    // Trimitem cererea de actualizare către backend
    form.post(route('notes.update', props.note.id), {
        preserveScroll: true,
        onSuccess: () => {
            // Tot ce trebuie să facem la succes este să închidem modalul.
            // Lista de notițe din Dashboard se va actualiza automat.
            isEditing.value = false;
            closeModal();
        },
        onError: (errors) => {
            console.error('Update failed:', errors);
            // Poți afișa erorile utilizatorului aici dacă dorești.
        }
    });
};

// Funcție pentru formatarea datei
const formatDate = (dateString) => {
    const date = new Date(dateString);
    return date.toLocaleString('ro-RO', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};
</script>

<template>
    <!-- Modal Overlay -->
    <div v-if="show" class="fixed inset-0 z-50 overflow-y-auto">
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-black/50 backdrop-blur-sm transition-opacity" @click="closeModal"></div>

        <!-- Modal Content -->
        <div class="flex min-h-screen items-center justify-center p-2 sm:p-4">
            <div class="relative w-full max-w-2xl mx-auto bg-white rounded-xl shadow-2xl transform transition-all max-h-[95vh] overflow-hidden">
                <!-- Header -->
                <div class="flex items-center justify-between p-4 sm:p-6 border-b border-gray-200 sticky top-0 bg-white z-10">
                    <div class="flex items-center space-x-3">
                        <!-- Type Badge -->
                        <span v-if="note" :class="[
                            'inline-flex items-center px-3 py-1 rounded-full text-sm font-medium',
                            noteTypeBadgeClass
                        ]">
                            {{ noteTypeDisplay }}
                        </span>

                        <!-- Favorite & Completed Indicators -->
                        <div class="flex items-center space-x-2">
                            <span v-if="note?.is_favorite" class="text-yellow-500" title="Favorit">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 fill-current" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                </svg>
                            </span>

                            <span v-if="note?.is_completed" class="text-green-500" title="Finalizat">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 fill-current" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                            </span>
                        </div>
                    </div>

                    <!-- Close Button -->
                    <button @click="closeModal"
                            class="text-gray-400 hover:text-gray-600 transition-colors p-2 rounded-lg hover:bg-gray-100 touch-manipulation">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <!-- Body -->
                <div class="p-4 sm:p-6 overflow-y-auto max-h-[calc(95vh-8rem)]">
                    <!-- View Mode -->
                    <div v-if="!isEditing && note" class="space-y-6">
                        <!-- Title -->
                        <div>
                            <h2 :class="[
                                'text-2xl font-bold text-gray-900 leading-tight',
                                note.is_completed ? 'line-through text-gray-500' : ''
                            ]">
                                {{ note.title || 'Notiță fără titlu' }}
                            </h2>
                        </div>

                        <!-- Content -->
                        <div class="prose max-w-none">
                            <p :class="[
                                'text-gray-700 leading-relaxed whitespace-pre-wrap text-base',
                                note.is_completed ? 'line-through text-gray-500' : ''
                            ]">
                                {{ note.content }}
                            </p>
                        </div>

                        <!-- Shopping List Items (dacă există) -->
                        <div v-if="note.note_type === 'shopping_list' && note.metadata?.items" class="space-y-2">
                            <h4 class="text-sm font-medium text-gray-500 uppercase tracking-wide">Liste de cumpărături</h4>
                            <div class="space-y-2">
                                <div v-for="(item, index) in note.metadata.items" :key="index"
                                     class="flex items-center space-x-3 p-2 rounded-lg bg-gray-50">
                                    <div :class="[
                                        'w-4 h-4 rounded border-2 flex items-center justify-center',
                                        item.completed ? 'bg-green-500 border-green-500' : 'border-gray-300'
                                    ]">
                                        <svg v-if="item.completed" xmlns="http://www.w3.org/2000/svg"
                                             class="h-3 w-3 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                                        </svg>
                                    </div>
                                    <span :class="[
                                        'text-sm',
                                        item.completed ? 'line-through text-gray-500' : 'text-gray-700'
                                    ]">
                                        {{ item.text }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Metadata -->
                        <div class="bg-gray-50 rounded-lg p-4 space-y-2">
                            <div class="text-sm text-gray-600">
                                <span class="font-medium">Creat:</span> {{ formatDate(note.created_at) }}
                            </div>
                            <div v-if="note.updated_at !== note.created_at" class="text-sm text-gray-600">
                                <span class="font-medium">Actualizat:</span> {{ formatDate(note.updated_at) }}
                            </div>
                            <div v-if="note.metadata?.reminder_at" class="text-sm text-blue-600">
                                <span class="font-medium">Reminder:</span> {{ formatDate(note.metadata.reminder_at) }}
                            </div>
                        </div>
                    </div>

                    <!-- Edit Mode -->
                    <form v-if="isEditing && note" @submit.prevent="saveChanges" class="space-y-6">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Editare Notiță</h3>
                        </div>

                        <!-- Title Input -->
                        <div>
                            <label for="edit-title" class="block text-sm font-medium text-gray-700 mb-2">
                                Titlu
                            </label>
                            <input
                                id="edit-title"
                                type="text"
                                v-model="form.title"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                                placeholder="Introdu un titlu pentru notiță..."
                            />
                            <p v-if="form.errors.title" class="text-sm text-red-600 mt-1">{{ form.errors.title }}</p>
                        </div>

                        <!-- Content Textarea -->
                        <div>
                            <label for="edit-content" class="block text-sm font-medium text-gray-700 mb-2">
                                Conținut
                            </label>
                            <textarea
                                id="edit-content"
                                rows="8"
                                v-model="form.content"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 resize-none transition-colors"
                                placeholder="Scrie conținutul notiței..."
                                required
                            ></textarea>
                            <p v-if="form.errors.content" class="text-sm text-red-600 mt-1">{{ form.errors.content }}</p>
                        </div>
                    </form>
                </div>

                <!-- Footer -->
                <div class="px-4 sm:px-6 py-3 sm:py-4 bg-gray-50 border-t border-gray-200 rounded-b-xl sticky bottom-0">
                    <div class="flex items-center justify-between">
                        <!-- Left side - Secondary actions -->
                        <div class="flex items-center space-x-2">
                            <button
                                @click="closeModal"
                                class="px-3 py-2 sm:px-4 sm:py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors touch-manipulation"
                            >
                                <span class="hidden sm:inline">Închide</span>
                                <span class="sm:hidden">←</span>
                            </button>
                        </div>

                        <!-- Right side - Primary actions -->
                        <div class="flex items-center space-x-2 sm:space-x-3">
                            <!-- View Mode Actions -->
                            <template v-if="!isEditing">
                                <button
                                    @click="startEditing"
                                    class="inline-flex items-center px-3 py-2 sm:px-4 sm:py-2 text-sm font-medium text-white bg-gradient-to-r from-primary to-secondary rounded-lg hover:from-primary-dark hover:to-secondary-dark focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-all shadow-sm touch-manipulation"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 sm:mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                    <span class="hidden sm:inline">Editare</span>
                                </button>
                            </template>

                            <!-- Edit Mode Actions -->
                            <template v-else>
                                <button
                                    @click="cancelEditing"
                                    type="button"
                                    class="px-3 py-2 sm:px-4 sm:py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-colors touch-manipulation"
                                >
                                    <span class="hidden sm:inline">Anulează</span>
                                    <span class="sm:hidden">✕</span>
                                </button>
                                <button
                                    @click="saveChanges"
                                    type="submit"
                                    :disabled="form.processing"
                                    class="inline-flex items-center px-3 py-2 sm:px-4 sm:py-2 text-sm font-medium text-white bg-gradient-to-r from-primary to-secondary rounded-lg hover:from-primary-dark hover:to-secondary-dark focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed transition-all shadow-sm touch-manipulation"
                                >
                                    <svg v-if="form.processing" class="animate-spin -ml-1 mr-1 sm:mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 sm:mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    <span class="hidden sm:inline">{{ form.processing ? 'Se salvează...' : 'Salvează' }}</span>
                                    <span class="sm:hidden">{{ form.processing ? '...' : '✓' }}</span>
                                </button>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* Tema de culori personalizată - aceleași ca în Dashboard */
:root {
    --color-primary: #10b981;
    --color-primary-light: #4ade80;
    --color-primary-dark: #059669;
    --color-secondary: #38bdf8;
    --color-secondary-light: #7dd3fc;
    --color-secondary-dark: #0284c7;
}

.from-primary {
    --tw-gradient-from: var(--color-primary);
    --tw-gradient-stops: var(--tw-gradient-from), var(--tw-gradient-to, rgba(16, 185, 129, 0));
}

.to-secondary {
    --tw-gradient-to: var(--color-secondary);
}

.hover\:from-primary-dark:hover {
    --tw-gradient-from: var(--color-primary-dark);
}

.hover\:to-secondary-dark:hover {
    --tw-gradient-to: var(--color-secondary-dark);
}

/* Animații personalizate */
.modal-enter-active, .modal-leave-active {
    transition: all 0.3s ease;
}

.modal-enter-from, .modal-leave-to {
    opacity: 0;
    transform: scale(0.95) translateY(-10px);
}

/* Stil pentru scrollbar */
::-webkit-scrollbar {
    width: 6px;
}

::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 3px;
}

::-webkit-scrollbar-thumb {
    background: #d1d5db;
    border-radius: 3px;
}

::-webkit-scrollbar-thumb:hover {
    background: #9ca3af;
}
</style>
