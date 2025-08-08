<script setup>
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import UserMenu from './UserMenu.vue';

const { t } = useI18n();

const props = defineProps({
    currentFilter: String,
    modelValue: String, // Formerly searchQuery
    user: Object,
});

const emit = defineEmits(['toggleSidebar', 'update:modelValue']);


const pageTitle = computed(() => {
    switch (props.currentFilter) {
        case 'all':
            return t('common.all_notes');
        case 'favorite':
            return t('common.favorites');
        case 'reminder':
            return t('common.reminders');
        default:
            return props.currentFilter.charAt(0).toUpperCase() + props.currentFilter.slice(1);
    }
});

const updateSearchQuery = (event) => {
    emit('update:modelValue', event.target.value);
};

</script>

<template>
    <header class="bg-white border-b border-gray-200 p-4 flex items-center justify-between">
        <div class="flex items-center">
            <button @click="emit('toggleSidebar')" class="md:hidden mr-4 text-gray-500 hover:text-gray-700">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd"
                          d="M3 5a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM3 10a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM3 15a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z"
                          clip-rule="evenodd"/>
                </svg>
            </button>
            <h2 class="text-lg font-semibold mr-3">{{ pageTitle }}</h2>

            <div class="relative bg-gray-100 rounded-lg hidden sm:block">
                <input
                    :value="modelValue"
                    @input="updateSearchQuery"
                    type="text"
                    :placeholder="t('common.search')"
                    class="py-2 pl-9 pr-4 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 bg-gray-100 w-32 md:w-64"
                />
                <svg xmlns="http://www.w3.org/2000/svg" class="absolute left-3 top-3 text-gray-500 h-4 w-4"
                     fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </div>
        </div>

        <UserMenu :user="user" :search-query="modelValue" @update:searchQuery="updateSearchQuery" />
    </header>
</template>
