<script setup>
import { useI18n } from 'vue-i18n';
import { computed } from 'vue';

const { t } = useI18n();

const props = defineProps({
    currentFilter: String,
    sortDirection: String,
});

const emit = defineEmits(['filterChanged', 'sortChanged']);

const applyFilter = (filter) => {
    emit('filterChanged', filter);
};

const toggleSort = () => {
    const newDirection = props.sortDirection === 'desc' ? 'asc' : 'desc';
    emit('sortChanged', newDirection);
};

const sortIcon = computed(() => {
    return props.sortDirection === 'desc'
        ? 'M3 4h13M3 8h9m-9 4h9m5-4v12m0 0l-4-4m4 4l4-4' // Newest first
        : 'M3 4h13M3 8h9m-9 4h6m4 8V4m0 0l-4 4m4-4l4 4'; // Oldest first
});

const sortText = computed(() => {
    return props.sortDirection === 'desc' ? t('dashboard.newest') : t('dashboard.oldest');
});

</script>

<template>
    <div class="mb-4 flex flex-wrap items-center gap-2">
        <span class="text-sm text-gray-500">{{ t('dashboard.filter_by') }}</span>
        <button @click="applyFilter('all')"
                :class="[currentFilter === 'all' ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 hover:bg-gray-200 text-gray-700', 'py-1 px-3 rounded text-sm']">
            {{ t('dashboard.all') }}
        </button>
        <button @click="applyFilter('task')"
                :class="[currentFilter === 'task' ? 'bg-red-100 text-red-700' : 'bg-gray-100 hover:bg-gray-200 text-gray-700', 'py-1 px-3 rounded text-sm flex items-center']">
            <span class="w-2 h-2 rounded-full bg-red-500 mr-1"></span>
            {{ t('common.task') }}
        </button>
        <button @click="applyFilter('idea')"
                :class="[currentFilter === 'idea' ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 hover:bg-gray-200 text-gray-700', 'py-1 px-3 rounded text-sm flex items-center']">
            <span class="w-2 h-2 rounded-full bg-blue-500 mr-1"></span>
            {{ t('common.idea') }}
        </button>
        <button @click="applyFilter('shopping_list')"
                :class="[currentFilter === 'shopping_list' ? 'bg-green-100 text-green-700' : 'bg-gray-100 hover:bg-gray-200 text-gray-700', 'py-1 px-3 rounded text-sm flex items-center']">
            <span class="w-2 h-2 rounded-full bg-green-500 mr-1"></span>
            {{ t('common.shopping') }}
        </button>
        <button @click="applyFilter('reminder')"
                :class="[currentFilter === 'reminder' ? 'bg-orange-100 text-orange-700' : 'bg-gray-100 hover:bg-gray-200 text-gray-700', 'py-1 px-3 rounded text-sm flex items-center']">
            <span class="w-2 h-2 rounded-full bg-orange-500 mr-1"></span>
            {{ t('common.reminder') }}
        </button>
        <button @click="applyFilter('completed')"
                :class="[currentFilter === 'completed' ? 'bg-green-100 text-green-700' : 'bg-gray-100 hover:bg-gray-200 text-gray-700', 'py-1 px-3 rounded text-sm flex items-center']">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 mr-1" viewBox="0 0 20 20"
                 fill="currentColor">
                <path fill-rule="evenodd"
                      d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                      clip-rule="evenodd"/>
            </svg>
            {{ t('common.completed') }}
        </button>
        <div class="ml-auto">
            <button @click="toggleSort"
                class="flex items-center text-sm bg-gray-100 hover:bg-gray-200 text-gray-700 py-1 px-3 rounded">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none"
                     viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          :d="sortIcon"/>
                </svg>
                {{ sortText }}
            </button>
        </div>
    </div>
</template>
