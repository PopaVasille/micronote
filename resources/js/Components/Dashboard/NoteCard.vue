<script setup>
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

const props = defineProps({
    note: Object,
});

const emit = defineEmits(['toggleFavorite', 'toggleCompleted', 'openNoteDetails']);

const formatDate = (dateString) => {
    const date = new Date(dateString);
    return date.toLocaleDateString('ro-RO', { day: 'numeric', month: 'short', year: 'numeric' });
};

const noteTypeClass = (noteType) => {
    return {
        'task': 'bg-red-500',
        'idea': 'bg-blue-500',
        'shopping_list': 'bg-green-500',
        'event': 'bg-purple-500',
        'reminder': 'bg-orange-500',
    }[noteType] || 'bg-gray-500';
};

const noteTypeText = (noteType) => {
    return {
        'task': t('common.task'),
        'idea': t('common.idea'),
        'shopping_list': t('common.shopping'),
        'event': t('common.event'),
        'reminder': t('common.reminder'),
    }[noteType] || t('common.note');
};
</script>

<template>
    <div @click="emit('openNoteDetails', note)"
         class="p-4 rounded-lg bg-white border border-gray-200 hover:shadow-md transition-shadow cursor-pointer">
        <div class="flex items-center justify-between mb-2">
            <div class="flex items-center">
                <span :class="['w-3 h-3 rounded-full', noteTypeClass(note.note_type)]"></span>
                <span class="ml-2 text-xs font-medium text-gray-500">{{ noteTypeText(note.note_type) }}</span>
            </div>

            <!-- Action buttons -->
            <div class="flex items-center space-x-2">
                <!-- Completed toggle button -->
                <button @click.stop="emit('toggleCompleted', note)" class="focus:outline-none"
                        :title="t('dashboard.mark_as_completed')">
                    <svg v-if="note.is_completed" xmlns="http://www.w3.org/2000/svg"
                         class="h-5 w-5 text-green-500 hover:text-green-600" viewBox="0 0 20 20"
                         fill="currentColor">
                        <path fill-rule="evenodd"
                              d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                              clip-rule="evenodd"/>
                    </svg>
                    <svg v-else xmlns="http://www.w3.org/2000/svg"
                         class="h-5 w-5 text-gray-400 hover:text-green-500" fill="none"
                         viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </button>

                <!-- Favorite toggle button -->
                <button @click.stop="emit('toggleFavorite', note)" class="focus:outline-none"
                        :title="t('dashboard.add_to_favorites')">
                    <svg v-if="note.is_favorite" xmlns="http://www.w3.org/2000/svg"
                         class="h-4 w-4 text-yellow-500 fill-yellow-500 hover:text-yellow-600"
                         viewBox="0 0 20 20" fill="currentColor">
                        <path
                            d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                    </svg>
                    <svg v-else xmlns="http://www.w3.org/2000/svg"
                         class="h-4 w-4 text-gray-400 hover:text-yellow-500" fill="none"
                         viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                    </svg>
                </button>
            </div>
        </div>

        <!-- Title -->
        <h3 :class="['font-medium mb-2', note.is_completed ? 'line-through text-gray-500' : '']">
            {{ note.title || note.content.substring(0, 40) }}
        </h3>

        <!-- Content -->
        <p :class="['text-sm text-gray-600 mb-2', note.is_completed ? 'line-through text-gray-400' : '']">
            {{ note.content }}
        </p>

        <!-- Shopping list content -->
        <div v-if="note.note_type === 'shopping_list' && note.metadata && note.metadata.items" class="mb-2">
            <div v-for="(item, itemIndex) in note.metadata.items" :key="itemIndex" class="flex items-center mb-1">
                <div :class="['w-4 h-4 rounded border', item.completed ? 'border-green-500 bg-green-500' : 'border-gray-300']" class="flex items-center justify-center">
                    <svg v-if="item.completed" xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 text-white" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <span :class="['ml-2 text-sm', item.completed ? 'line-through text-gray-500' : '']">{{ item.text }}</span>
            </div>
        </div>

        <!-- Completed Status -->
        <div v-if="note.is_completed" class="mb-2">
            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 mr-1" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                {{ t('common.completed') }}
            </span>
        </div>

        <!-- Footer -->
        <div class="flex justify-between items-center mt-3 text-xs">
            <span class="text-gray-500">{{ formatDate(note.created_at) }}</span>
            <div v-if="note.metadata && note.metadata.reminder_at" class="flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span class="ml-1 text-blue-500">{{ formatDate(note.metadata.reminder_at) }}</span>
            </div>
        </div>
    </div>
</template>
