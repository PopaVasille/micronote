<script setup>
import { ref, onMounted, computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { router } from '@inertiajs/vue3';

const { t } = useI18n();

const emit = defineEmits(['reminderCompleted']);

// State
const isLoading = ref(true);
const reminders = ref([]);
const error = ref(null);
const completingReminderIds = ref(new Set());

// Computed
const hasReminders = computed(() => reminders.value.length > 0);

// Methods
const fetchActiveReminders = async () => {
    try {
        isLoading.value = true;
        error.value = null;
        
        const response = await fetch(route('api.reminders.active'), {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        
        if (!response.ok) {
            throw new Error('Failed to fetch active reminders');
        }
        
        reminders.value = await response.json();
    } catch (err) {
        error.value = err.message;
        console.error('Error fetching active reminders:', err);
    } finally {
        isLoading.value = false;
    }
};

const completeReminder = async (reminder) => {
    if (completingReminderIds.value.has(reminder.id)) {
        return; // Prevent double clicks
    }
    
    try {
        completingReminderIds.value.add(reminder.id);
        
        // Use router.post for better integration with Inertia.js and automatic CSRF handling
        router.post(route('api.reminders.complete', reminder.id), {}, {
            preserveState: true,
            preserveScroll: true,
            onBefore: () => {
                // Add optimistic update - remove reminder immediately for better UX
                reminders.value = reminders.value.filter(r => r.id !== reminder.id);
            },
            onSuccess: (page) => {
                // Emit event for parent component to refresh dashboard if needed
                emit('reminderCompleted', reminder);
                
                // Success message is handled by Laravel flash message
                console.log('Reminder completed successfully');
            },
            onError: (errors) => {
                console.error('Error completing reminder:', errors);
                
                // Restore the reminder if there was an error (undo optimistic update)
                fetchActiveReminders();
            },
            onFinish: () => {
                completingReminderIds.value.delete(reminder.id);
            }
        });
        
    } catch (err) {
        console.error('Error completing reminder:', err);
        completingReminderIds.value.delete(reminder.id);
    }
};

const handleViewAllReminders = () => {
    router.get(route('dashboard'), { filter: 'reminder' }, { 
        preserveState: true,
        preserveScroll: true 
    });
};

const getReminderUrgencyClass = (remindAt) => {
    const now = new Date();
    const reminderTime = new Date(remindAt);
    const diffHours = (reminderTime - now) / (1000 * 60 * 60);
    
    if (diffHours <= 1) {
        return 'border-l-red-400 bg-red-50';
    } else if (diffHours <= 24) {
        return 'border-l-orange-400 bg-orange-50';
    } else {
        return 'border-l-blue-400 bg-blue-50';
    }
};

const getTimeIcon = (remindAt) => {
    const now = new Date();
    const reminderTime = new Date(remindAt);
    const diffHours = (reminderTime - now) / (1000 * 60 * 60);
    
    if (diffHours <= 1) {
        return '🔴'; // Urgent
    } else if (diffHours <= 24) {
        return '🟡'; // Soon
    } else {
        return '🔔'; // Later
    }
};

// Lifecycle
onMounted(() => {
    fetchActiveReminders();
});
</script>

<template>
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <!-- Header -->
        <div class="bg-gradient-to-r from-orange-50 to-yellow-50 px-4 sm:px-6 py-4 border-b border-gray-100">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-900 flex items-center">
                    <span class="text-xl mr-2">⏰</span>
                    <span class="hidden sm:inline">{{ t('dashboard.active_reminders') }}</span>
                    <span class="sm:hidden">Mementouri</span>
                </h2>
                <div v-if="hasReminders" class="flex items-center">
                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-orange-100 text-orange-800">
                        {{ reminders.length }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Content -->
        <div class="p-4 sm:p-6">
            <!-- Loading State -->
            <div v-if="isLoading" class="space-y-3">
                <div v-for="i in 3" :key="i" class="animate-pulse">
                    <div class="flex items-center space-x-3 p-3">
                        <div class="w-4 h-4 bg-gray-200 rounded"></div>
                        <div class="flex-1 space-y-2">
                            <div class="h-3 bg-gray-200 rounded w-3/4"></div>
                            <div class="h-2 bg-gray-200 rounded w-1/2"></div>
                        </div>
                        <div class="w-6 h-6 bg-gray-200 rounded"></div>
                    </div>
                </div>
            </div>

            <!-- Error State -->
            <div v-else-if="error" class="text-center py-6">
                <div class="text-gray-400 mb-2">⚠️</div>
                <p class="text-sm text-gray-500">{{ t('errors.loading_reminders') }}</p>
                <button @click="fetchActiveReminders" 
                        class="mt-2 text-xs text-blue-600 hover:text-blue-700 underline">
                    {{ t('common.try_again') }}
                </button>
            </div>

            <!-- No Reminders State -->
            <div v-else-if="!hasReminders" class="text-center py-6">
                <div class="text-4xl mb-2">✨</div>
                <p class="text-sm font-medium text-gray-700 mb-1">{{ t('dashboard.no_active_reminders') }}</p>
                <p class="text-xs text-gray-500">{{ t('dashboard.all_caught_up') }}</p>
            </div>

            <!-- Reminders List -->
            <div v-else class="space-y-2">
                <div v-for="reminder in reminders" 
                     :key="reminder.id"
                     :class="[
                         'group relative border-l-4 rounded-r-lg transition-all duration-300',
                         getReminderUrgencyClass(reminder.remind_at)
                     ]">
                    
                    <!-- Reminder Item -->
                    <div class="flex items-center justify-between p-3 sm:p-4">
                        <div class="flex items-start space-x-3 flex-1 min-w-0">
                            <!-- Time Icon -->
                            <div class="flex-shrink-0 mt-0.5">
                                <span class="text-lg">{{ getTimeIcon(reminder.remind_at) }}</span>
                            </div>
                            
                            <!-- Content -->
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-900 truncate">
                                    {{ reminder.title }}
                                </p>
                                <p class="text-xs text-gray-500 mt-1">
                                    {{ reminder.remind_at_human }}
                                </p>
                            </div>
                        </div>

                        <!-- Complete Button -->
                        <div class="flex-shrink-0 ml-3">
                            <button @click="completeReminder(reminder)"
                                    :disabled="completingReminderIds.has(reminder.id)"
                                    :class="[
                                        'w-8 h-8 rounded-full border-2 flex items-center justify-center transition-all duration-200',
                                        'hover:border-green-400 hover:bg-green-50 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-1',
                                        completingReminderIds.has(reminder.id) 
                                            ? 'border-green-400 bg-green-100 cursor-not-allowed' 
                                            : 'border-gray-300 hover:border-green-400'
                                    ]"
                                    :title="t('dashboard.mark_reminder_done')">
                            
                            <!-- Loading Spinner -->
                            <svg v-if="completingReminderIds.has(reminder.id)" 
                                 class="animate-spin h-4 w-4 text-green-600" 
                                 xmlns="http://www.w3.org/2000/svg" 
                                 fill="none" 
                                 viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            
                            <!-- Check Icon -->
                            <svg v-else xmlns="http://www.w3.org/2000/svg" 
                                 class="h-4 w-4 text-green-600 opacity-0 group-hover:opacity-100 transition-opacity" 
                                 fill="none" 
                                 viewBox="0 0 24 24" 
                                 stroke="currentColor">
                                <path stroke-linecap="round" 
                                      stroke-linejoin="round" 
                                      stroke-width="2" 
                                      d="M5 13l4 4L19 7" />
                            </svg>
                        </button>
                        </div>
                    </div>
                </div>

                <!-- View All Link -->
                <div v-if="reminders.length >= 4" class="text-center pt-3 border-t border-gray-100">
                    <button @click="handleViewAllReminders"
                            class="text-sm text-blue-600 hover:text-blue-700 font-medium">
                        {{ t('dashboard.view_all_reminders') }} →
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>