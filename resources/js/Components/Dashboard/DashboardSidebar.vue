<script setup>
import { useI18n } from 'vue-i18n';
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const { t } = useI18n();

const props = defineProps({
    showSidebar: Boolean,
    currentFilter: String,
    notesCount: Number,
});

const emit = defineEmits(['toggleSidebar', 'openCreateModal', 'filterChanged']);

const applyFilter = (filter) => {
    emit('filterChanged', filter);
};

const notesUsagePercentage = computed(() => {
    if (!props.notesCount || props.notesCount < 0) {
        return 0;
    }
    const percentage = (props.notesCount / 200) * 100;
    return Math.min(percentage, 100); // Cap at 100%
});

</script>

<template>
    <div :class="[showSidebar ? 'block' : 'hidden', 'w-64 bg-white border-r border-gray-200 h-screen sticky top-0 overflow-y-auto md:block flex flex-col']">
        <div>
            <div class="p-4 flex items-center justify-between">
                <div class="flex items-center">
                    <div class="w-8 h-8 rounded-md bg-gradient-to-r from-primary to-secondary flex items-center justify-center text-white font-bold">
                        M
                    </div>
                    <h1 class="ml-2 text-xl font-bold">MicroNote</h1>
                </div>
                <button @click="emit('toggleSidebar')" class="md:hidden text-gray-500 hover:text-gray-700">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                </button>
            </div>

            <!-- User Stats -->
            <div class="mx-4 my-2 p-3 rounded-lg bg-gray-100">
                <p class="text-sm font-medium">{{ t('dashboard.plan_free') }}</p>
                <div class="mt-1 flex items-center">
                    <div class="w-full bg-gray-300 rounded-full h-2.5">
                        <div class="bg-gradient-to-r from-primary to-secondary h-2.5 rounded-full" :style="{ width: notesUsagePercentage + '%' }"></div>
                    </div>
                    <span class="ml-2 text-sm">{{ notesCount }}/200</span>
                </div>
            </div>

            <!-- New Note Button -->
            <div class="px-4 mt-4">
                <button @click="emit('openCreateModal')" class="w-full bg-gradient-to-r from-primary to-secondary hover:from-primary-dark hover:to-secondary-dark text-white py-2 px-4 rounded-lg flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd"/></svg>
                    <span class="ml-2">{{ t('common.new_note') }}</span>
                </button>
            </div>

            <!-- Navigation -->
            <nav class="mt-6 px-4">
                <div class="mb-2 text-sm font-medium text-gray-500">{{ t('dashboard.navigation') }}</div>
                <ul>
                    <li>
                        <a href="#" @click.prevent="applyFilter('all')" :class="[currentFilter === 'all' ? 'bg-gray-200' : 'hover:bg-gray-100', 'flex items-center py-2 px-3 rounded-lg']">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-secondary" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            <span class="ml-2">{{ t('common.all_notes') }}</span>
                        </a>
                    </li>
                    <li>
                        <a href="#" @click.prevent="applyFilter('favorite')" :class="[currentFilter === 'favorite' ? 'bg-gray-200' : 'hover:bg-gray-100', 'flex items-center py-2 px-3 rounded-lg']">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-secondary" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>
                            <span class="ml-2">{{ t('common.favorites') }}</span>
                        </a>
                    </li>
                     <li>
                        <a href="#" @click.prevent="applyFilter('reminder')" :class="[currentFilter === 'reminder' ? 'bg-gray-200' : 'hover:bg-gray-100', 'flex items-center py-2 px-3 rounded-lg']">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-secondary" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <span class="ml-2">{{ t('common.reminders') }}</span>
                        </a>
                    </li>
                    <li>
                        <a href="#" @click.prevent="applyFilter('shopping_list')" :class="[currentFilter === 'shopping_list' ? 'bg-gray-200' : 'hover:bg-gray-100', 'flex items-center py-2 px-3 rounded-lg']">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-secondary" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                            <span class="ml-2">{{ t('common.shopping_lists') }}</span>
                        </a>
                    </li>
                    <li>
                        <a href="#" @click.prevent="applyFilter('completed')" :class="[currentFilter === 'completed' ? 'bg-gray-200' : 'hover:bg-gray-100', 'flex items-center py-2 px-3 rounded-lg']">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-secondary" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <span class="ml-2">{{ t('common.completed') }}</span>
                        </a>
                    </li>
                </ul>
            </nav>

            <!-- Tags -->
            <div class="mt-6 px-4">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-sm font-medium text-gray-500">{{ t('common.tags') }}</span>
                    <button class="text-gray-500 hover:text-gray-700">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    </button>
                </div>
                <ul>
                    <li>
                        <a href="#" @click.prevent="applyFilter('task')" :class="[currentFilter === 'task' ? 'bg-gray-200' : 'hover:bg-gray-100', 'flex items-center py-2 px-3 rounded-lg']">
                            <span class="w-3 h-3 rounded-full bg-red-500"></span>
                            <span class="ml-2">{{ t('common.task') }}</span>
                        </a>
                    </li>
                    <li>
                        <a href="#" @click.prevent="applyFilter('idea')" :class="[currentFilter === 'idea' ? 'bg-gray-200' : 'hover:bg-gray-100', 'flex items-center py-2 px-3 rounded-lg']">
                            <span class="w-3 h-3 rounded-full bg-blue-500"></span>
                            <span class="ml-2">{{ t('common.idea') }}</span>
                        </a>
                    </li>
                    <li>
                        <a href="#" @click.prevent="applyFilter('shopping_list')" :class="[currentFilter === 'shopping_list' ? 'bg-gray-200' : 'hover:bg-gray-100', 'flex items-center py-2 px-3 rounded-lg']">
                            <span class="w-3 h-3 rounded-full bg-green-500"></span>
                            <span class="ml-2">{{ t('common.shopping') }}</span>
                        </a>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Footer -->
        <div class="p-4 mt-auto border-t border-gray-200">
            <div class="flex items-center justify-between">
                <button class="text-gray-500 hover:text-gray-700">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
                </button>
                <Link :href="route('profile.edit')" class="text-gray-500 hover:text-gray-700 transition-colors" :title="t('common.profile')">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                </Link>
                <a href="#" class="text-blue-500 hover:text-blue-600 text-sm font-medium">
                    {{ t('common.upgrade') }}
                </a>
            </div>
        </div>
    </div>
</template>