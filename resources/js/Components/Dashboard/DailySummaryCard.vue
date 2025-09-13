<script setup>
import { ref, onMounted, computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { router } from '@inertiajs/vue3';

const { t } = useI18n();

const emit = defineEmits(['filterTasks', 'filterEvents']);

// State
const isLoading = ref(true);
const summaryData = ref(null);
const error = ref(null);

// Computed
const hasData = computed(() => summaryData.value && (summaryData.value.tasks_today > 0 || summaryData.value.events_today > 0));

const priorityIcon = (priority) => {
    return {
        3: '🔥', // High priority
        2: '⚡', // Medium priority
        1: '▫️', // Normal priority
    }[priority] || '▫️';
};

const formatTime = (timeStr) => {
    if (!timeStr) return '';
    return `(${timeStr})`;
};

// Methods
const fetchSummaryData = async () => {
    try {
        isLoading.value = true;
        error.value = null;
        
        const response = await fetch(route('api.summary.daily'), {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        
        if (!response.ok) {
            throw new Error('Failed to fetch summary data');
        }
        
        summaryData.value = await response.json();
    } catch (err) {
        error.value = err.message;
        console.error('Error fetching daily summary:', err);
    } finally {
        isLoading.value = false;
    }
};

const handleViewAllTasks = () => {
    emit('filterTasks');
    // Optionally filter the main notes list to show tasks
    router.get(route('dashboard'), { filter: 'task' }, { 
        preserveState: true,
        preserveScroll: true 
    });
};

const handleViewAllEvents = () => {
    emit('filterEvents');
    // Optionally filter the main notes list to show events
    router.get(route('dashboard'), { filter: 'event' }, { 
        preserveState: true,
        preserveScroll: true 
    });
};

// Lifecycle
onMounted(() => {
    fetchSummaryData();
});
</script>

<template>
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <!-- Header -->
        <div class="bg-gradient-to-r from-blue-50 to-indigo-50 px-4 sm:px-6 py-4 border-b border-gray-100">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-900 flex items-center">
                    <span class="text-xl mr-2">📅</span>
                    <span class="hidden sm:inline">{{ t('dashboard.daily_summary') }}</span>
                    <span class="sm:hidden">Azi</span>
                </h2>
                <div class="text-xs text-gray-500">
                    {{ new Date().toLocaleDateString('ro-RO', { 
                        weekday: 'short', 
                        day: 'numeric', 
                        month: 'short' 
                    }) }}
                </div>
            </div>
        </div>

        <!-- Content -->
        <div class="p-4 sm:p-6">
            <!-- Loading State -->
            <div v-if="isLoading" class="space-y-3">
                <div class="animate-pulse">
                    <div class="h-4 bg-gray-200 rounded w-3/4 mb-3"></div>
                    <div class="space-y-2">
                        <div class="h-3 bg-gray-200 rounded"></div>
                        <div class="h-3 bg-gray-200 rounded w-5/6"></div>
                    </div>
                </div>
            </div>

            <!-- Error State -->
            <div v-else-if="error" class="text-center py-6">
                <div class="text-gray-400 mb-2">⚠️</div>
                <p class="text-sm text-gray-500">{{ t('errors.loading_summary') }}</p>
                <button @click="fetchSummaryData" 
                        class="mt-2 text-xs text-blue-600 hover:text-blue-700 underline">
                    {{ t('common.try_again') }}
                </button>
            </div>

            <!-- No Data State -->
            <div v-else-if="!hasData" class="text-center py-6">
                <div class="text-4xl mb-2">🎉</div>
                <p class="text-sm font-medium text-gray-700 mb-1">{{ t('dashboard.no_tasks_today') }}</p>
                <p class="text-xs text-gray-500">{{ t('dashboard.enjoy_free_time') }}</p>
            </div>

            <!-- Data State -->
            <div v-else class="space-y-4">
                <!-- Summary Stats -->
                <div class="grid grid-cols-2 gap-3 sm:gap-4">
                    <!-- Tasks Today -->
                    <div v-if="summaryData.tasks_today > 0" 
                         @click="handleViewAllTasks"
                         class="bg-red-50 rounded-lg p-3 cursor-pointer hover:bg-red-100 transition-colors">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs font-medium text-red-600 mb-1">{{ t('dashboard.tasks_today') }}</p>
                                <p class="text-lg font-bold text-red-700">{{ summaryData.tasks_today }}</p>
                            </div>
                            <div class="text-red-500 text-lg">✅</div>
                        </div>
                    </div>

                    <!-- Events Today -->
                    <div v-if="summaryData.events_today > 0" 
                         @click="handleViewAllEvents"
                         class="bg-purple-50 rounded-lg p-3 cursor-pointer hover:bg-purple-100 transition-colors">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs font-medium text-purple-600 mb-1">{{ t('dashboard.events_today') }}</p>
                                <p class="text-lg font-bold text-purple-700">{{ summaryData.events_today }}</p>
                            </div>
                            <div class="text-purple-500 text-lg">🗓️</div>
                        </div>
                    </div>
                </div>

                <!-- Top Items Preview -->
                <div class="space-y-3">
                    <!-- Top Tasks -->
                    <div v-if="summaryData.items.tasks.length > 0">
                        <h3 class="text-sm font-medium text-gray-700 mb-2 flex items-center">
                            <span class="text-red-500 mr-1">✅</span>
                            {{ t('dashboard.priority_tasks') }}
                        </h3>
                        <div class="space-y-1">
                            <div v-for="task in summaryData.items.tasks.slice(0, 3)" 
                                 :key="task.id"
                                 class="flex items-center justify-between py-1.5 px-2 bg-gray-50 rounded-lg text-sm">
                                <div class="flex items-center flex-1 min-w-0">
                                    <span class="mr-2">{{ priorityIcon(task.priority) }}</span>
                                    <span class="truncate flex-1">{{ task.title }}</span>
                                </div>
                                <span v-if="task.due_time" class="text-xs text-gray-500 ml-2">
                                    {{ formatTime(task.due_time) }}
                                </span>
                            </div>
                        </div>
                        <button v-if="summaryData.tasks_today > 3" 
                                @click="handleViewAllTasks"
                                class="text-xs text-blue-600 hover:text-blue-700 mt-2">
                            + {{ summaryData.tasks_today - 3 }} {{ t('dashboard.more_tasks') }}
                        </button>
                    </div>

                    <!-- Top Events -->
                    <div v-if="summaryData.items.events.length > 0">
                        <h3 class="text-sm font-medium text-gray-700 mb-2 flex items-center">
                            <span class="text-purple-500 mr-1">🗓️</span>
                            {{ t('dashboard.upcoming_events') }}
                        </h3>
                        <div class="space-y-1">
                            <div v-for="event in summaryData.items.events.slice(0, 3)" 
                                 :key="event.id"
                                 class="flex items-center justify-between py-1.5 px-2 bg-gray-50 rounded-lg text-sm">
                                <div class="flex items-center flex-1 min-w-0">
                                    <span class="text-purple-500 mr-2">🔸</span>
                                    <span class="truncate flex-1">{{ event.title }}</span>
                                </div>
                                <span v-if="event.event_time" class="text-xs text-gray-500 ml-2">
                                    {{ formatTime(event.event_time) }}
                                </span>
                            </div>
                        </div>
                        <button v-if="summaryData.events_today > 3" 
                                @click="handleViewAllEvents"
                                class="text-xs text-blue-600 hover:text-blue-700 mt-2">
                            + {{ summaryData.events_today - 3 }} {{ t('dashboard.more_events') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>