<script setup>
import { ref, onMounted, computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { router } from '@inertiajs/vue3';

const { t } = useI18n();

const emit = defineEmits(['reminderCompleted', 'reminderClicked']);

// State
const isLoading = ref(true);
const reminders = ref([]);
const error = ref(null);
const isRetrying = ref(false);
const completingReminderIds = ref(new Set());
const retryCount = ref(0);
const maxRetries = ref(3);
const isOnline = ref(navigator.onLine);
const abortController = ref(null);

// Computed
const hasReminders = computed(() => reminders.value.length > 0);

// Methods
const fetchActiveReminders = async (isRetry = false) => {
    try {
        if (isRetry) {
            isRetrying.value = true;
            retryCount.value++;
        } else {
            isLoading.value = true;
            retryCount.value = 0;
        }
        error.value = null;

        // Cancel previous request if still pending
        if (abortController.value) {
            abortController.value.abort();
        }
        
        // Create new abort controller for this request
        abortController.value = new AbortController();

        const response = await fetch(route('api.reminders.active'), {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            signal: abortController.value.signal,
            timeout: 10000
        });

        if (!response.ok) {
            const errorData = await response.json().catch(() => ({}));
            
            if (response.status === 404) {
                throw new Error(errorData.message || 'Serviciul de mementori nu este disponibil');
            } else if (response.status >= 500) {
                throw new Error(errorData.message || 'Eroare de server. Încearcă din nou în câteva momente.');
            } else if (response.status === 401) {
                throw new Error(errorData.message || 'Sesiunea a expirat. Reîncarcă pagina.');
            } else if (response.status === 429) {
                throw new Error('Prea multe cereri. Încearcă din nou în câteva secunde.');
            } else {
                throw new Error(errorData.message || 'Nu s-au putut încărca mementorile');
            }
        }

        const data = await response.json();
        
        // Validate response structure
        if (!Array.isArray(data)) {
            throw new Error('Răspuns invalid de la server');
        }
        
        // Validate reminder structure
        const validReminders = data.filter(reminder => 
            reminder && 
            typeof reminder === 'object' && 
            reminder.id && 
            reminder.title &&
            reminder.remind_at
        );

        reminders.value = validReminders;
        retryCount.value = 0; // Reset retry count on success
        
    } catch (err) {
        if (err.name === 'AbortError') {
            // Request was cancelled, don't show error
            return;
        }
        
        let errorMessage = 'Eroare la încărcarea mementorilor';
        
        if (err.name === 'TypeError' && (err.message.includes('fetch') || err.message.includes('network'))) {
            errorMessage = 'Verifică conexiunea la internet și încearcă din nou';
        } else if (err.message.includes('timeout')) {
            errorMessage = 'Cererea a expirat. Verifică conexiunea și încearcă din nou.';
        } else if (err.message) {
            errorMessage = err.message;
        }
        
        error.value = errorMessage;
        console.error('Error fetching active reminders:', err);
        
        // Auto-retry with exponential backoff for network errors
        if (!isRetry && retryCount.value < maxRetries.value && isOnline.value) {
            const delay = Math.min(1000 * Math.pow(2, retryCount.value), 10000); // Max 10 seconds
            setTimeout(() => {
                fetchActiveReminders(true);
            }, delay);
        }
        
    } finally {
        isLoading.value = false;
        isRetrying.value = false;
        abortController.value = null;
    }
};

const retryFetchReminders = () => {
    fetchActiveReminders(true);
};

const completeReminder = async (reminder) => {
    if (completingReminderIds.value.has(reminder.id)) {
        return; // Prevent double clicks
    }

    // Validate reminder object
    if (!reminder || !reminder.id || typeof reminder.id !== 'number') {
        error.value = 'Memento invalid. Reîncarcă pagina și încearcă din nou.';
        setTimeout(() => { error.value = null; }, 5000);
        return;
    }
    
    // Check if user is online
    if (!isOnline.value) {
        error.value = 'Nu ești conectat la internet. Verifică conexiunea și încearcă din nou.';
        setTimeout(() => { error.value = null; }, 5000);
        return;
    }

    try {
        completingReminderIds.value.add(reminder.id);

        // Store original reminders for potential restoration
        const originalReminders = [...reminders.value];
        const reminderIndex = reminders.value.findIndex(r => r.id === reminder.id);
        
        if (reminderIndex === -1) {
            throw new Error('Mementoul nu mai există în listă');
        }

        // Optimistic update - remove reminder immediately
        reminders.value = reminders.value.filter(r => r.id !== reminder.id);

        // Make API call with timeout and retry logic
        let attempts = 0;
        const maxAttempts = 3;
        
        const attemptCompletion = async () => {
            attempts++;
            
            try {
                await router.post(route('api.reminders.complete', reminder.id), {}, {
                    preserveState: true,
                    preserveScroll: true,
                    timeout: 8000, // 8 second timeout
                    onError: (errors) => {
                        throw new Error(errors?.message || 'Server error');
                    },
                    onSuccess: () => {
                        // Emit event for parent component
                        emit('reminderCompleted', reminder);
                        console.log('Reminder completed successfully');
                    }
                });
            } catch (routerError) {
                if (attempts < maxAttempts && isOnline.value) {
                    // Exponential backoff retry
                    const delay = Math.min(1000 * Math.pow(2, attempts - 1), 5000);
                    await new Promise(resolve => setTimeout(resolve, delay));
                    return attemptCompletion();
                }
                throw routerError;
            }
        };
        
        await attemptCompletion();

    } catch (err) {
        console.error('Error completing reminder:', err);

        // Restore reminder on error
        const sortedReminders = [...reminders.value, reminder].sort((a, b) =>
            new Date(a.remind_at) - new Date(b.remind_at)
        );
        reminders.value = sortedReminders;

        // Determine error message
        let errorMessage = 'Nu s-a putut completa mementorul. Verifică conexiunea și încearcă din nou.';
        
        if (err.message) {
            if (err.message.includes('timeout')) {
                errorMessage = 'Cererea a expirat. Verifică conexiunea și încearcă din nou.';
            } else if (err.message.includes('network') || err.message.includes('fetch')) {
                errorMessage = 'Conexiune întreruptă. Verifică internetul și încearcă din nou.';
            } else if (err.message.includes('404')) {
                errorMessage = 'Mementoul nu a fost găsit. Reîncarcă pagina.';
            } else if (err.message.includes('409')) {
                errorMessage = 'Mementoul este în curs de procesare. Încearcă din nou în câteva secunde.';
            } else if (err.message.includes('429')) {
                errorMessage = 'Prea multe cereri. Încearcă din nou în câteva secunde.';
            } else {
                errorMessage = err.message;
            }
        }

        error.value = errorMessage;

        // Auto-clear error after 7 seconds
        setTimeout(() => {
            error.value = null;
        }, 7000);
        
    } finally {
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
        return 'border-l-red-400 bg-red-50 hover:bg-red-100';
    } else if (diffHours <= 24) {
        return 'border-l-orange-400 bg-orange-50 hover:bg-orange-100';
    } else {
        return 'border-l-blue-400 bg-blue-50 hover:bg-blue-100';
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

const handleReminderClick = (reminder) => {
    if (completingReminderIds.value.has(reminder.id)) return;
    emit('reminderClicked', reminder);
};

// Network connectivity monitoring
const handleOnline = () => {
    isOnline.value = true;
    // Retry fetching reminders if there was an error and we're back online
    if (error.value && !isLoading.value) {
        fetchActiveReminders(true);
    }
};

const handleOffline = () => {
    isOnline.value = false;
    error.value = 'Nu ești conectat la internet. Verifică conexiunea.';
};

// Cleanup function
const cleanup = () => {
    // Cancel any pending requests
    if (abortController.value) {
        abortController.value.abort();
        abortController.value = null;
    }
    
    // Clear any pending timeouts
    completingReminderIds.value.clear();
    
    // Remove event listeners
    window.removeEventListener('online', handleOnline);
    window.removeEventListener('offline', handleOffline);
};

// Lifecycle
onMounted(() => {
    // Add network connectivity listeners
    window.addEventListener('online', handleOnline);
    window.addEventListener('offline', handleOffline);
    
    // Initial fetch
    fetchActiveReminders();
});

// Cleanup on unmount
import { onUnmounted } from 'vue';
onUnmounted(() => {
    cleanup();
});
</script>

<template>
    <div class="flex flex-col h-full">
        <!-- Subsection Header -->
        <div class="flex items-center justify-between mb-3">
            <h3 class="text-sm font-medium text-gray-900 flex items-center">
                <span class="text-base mr-2">⏰</span>
                <span class="hidden sm:inline">{{ t('dashboard.active_reminders') }}</span>
                <span class="sm:hidden">Mementouri</span>
                <span v-if="!isLoading && hasReminders" class="ml-2 text-xs text-gray-500 bg-gray-100 px-2 py-1 rounded-full">
                    {{ reminders.length }}
                </span>
            </h3>
        </div>

        <!-- Content -->
        <div class="flex-1">
        <!-- Loading State - Mobile Optimized -->
        <div v-if="isLoading" class="space-y-2">
            <div v-for="i in 3" :key="i" class="animate-pulse">
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg border border-gray-100">
                    <div class="flex items-center space-x-3 flex-1">
                        <!-- Time icon skeleton -->
                        <div class="w-4 h-4 bg-gray-200 rounded-full flex-shrink-0"></div>
                        <div class="flex-1 min-w-0">
                            <!-- Reminder title skeleton -->
                            <div class="h-4 bg-gray-200 rounded w-3/4 mb-2"></div>
                            <!-- Time info skeleton -->
                            <div class="h-3 bg-gray-200 rounded w-1/2"></div>
                        </div>
                    </div>
                    <!-- Completion button skeleton -->
                    <div class="w-11 h-11 bg-gray-200 rounded-full flex-shrink-0"></div>
                </div>
            </div>
            <!-- Loading text for screen readers -->
            <div class="sr-only" aria-live="polite">Se încarcă mementorile...</div>
        </div>

        <!-- Error State - Mobile Optimized -->
        <div v-else-if="error" class="p-4 bg-red-50 border border-red-200 rounded-lg">
            <div class="text-center">
                <div class="text-2xl mb-2">⚠️</div>
                <p class="text-sm text-red-700 font-medium mb-3">{{ error }}</p>
                <button
                    @click="retryFetchReminders"
                    class="inline-flex items-center px-4 py-2 bg-red-100 hover:bg-red-200 active:bg-red-300 text-red-700 text-sm font-medium rounded-lg transition-colors duration-200 touch-manipulation"
                    :disabled="isRetrying"
                >
                    <span v-if="isRetrying" class="w-4 h-4 border-2 border-red-600 border-t-transparent rounded-full animate-spin mr-2"></span>
                    <span v-else class="mr-2">🔄</span>
                    {{ isRetrying ? 'Se reîncearcă...' : t('common.try_again') }}
                </button>
            </div>
            <!-- Error announcement for screen readers -->
            <div class="sr-only" aria-live="assertive">Eroare la încărcarea mementorilor. {{ error }}</div>
        </div>

        <!-- No Reminders State - Mobile Optimized -->
        <div v-else-if="!hasReminders" class="p-6 text-center">
            <div class="text-4xl mb-3">✨</div>
            <p class="text-sm font-medium text-gray-700 mb-1">{{ t('dashboard.no_active_reminders') }}</p>
            <p class="text-xs text-gray-500 mb-4">{{ t('dashboard.all_caught_up') }}</p>
            <div class="text-xs text-gray-400 bg-gray-50 rounded-lg p-3 border border-gray-100">
                💡 Mementorile noi vor apărea aici automat
            </div>
            <!-- Empty state announcement for screen readers -->
            <div class="sr-only" aria-live="polite">Nu există mementori activi în acest moment</div>
        </div>

        <!-- Reminders List -->
        <div v-else class="space-y-2 flex-1">
            <div v-for="reminder in reminders"
                 :key="reminder.id"
                 :class="[
                     'group relative border-l-4 rounded-r-lg transition-all duration-200 hover:shadow-sm active:scale-[0.98] cursor-pointer',
                     getReminderUrgencyClass(reminder.remind_at)
                 ]"
                 @click="handleReminderClick(reminder)">

                <!-- Reminder Item -->
                <div class="flex items-center justify-between p-3">
                    <div class="flex items-start space-x-3 flex-1 min-w-0">
                        <!-- Time Icon with enhanced visibility -->
                        <div class="flex-shrink-0 mt-0.5">
                            <span class="text-lg leading-none filter drop-shadow-sm">{{ getTimeIcon(reminder.remind_at) }}</span>
                        </div>

                        <!-- Content -->
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center space-x-2">
                                <p class="text-sm font-medium text-gray-900 truncate leading-tight group-hover:text-blue-700 transition-colors">
                                    {{ reminder.title }}
                                </p>
                                <!-- Click indicator -->
                                <svg class="w-3 h-3 text-gray-400 opacity-0 group-hover:opacity-100 transition-opacity flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                </svg>
                            </div>
                            <p class="text-xs text-gray-600 mt-1 font-medium">
                                {{ reminder.remind_at_human }}
                            </p>
                        </div>
                    </div>

                    <!-- Complete Button - Enhanced mobile touch targets -->
                    <div class="flex-shrink-0 ml-3">
                        <button @click.stop="completeReminder(reminder)"
                                :disabled="completingReminderIds.has(reminder.id)"
                                :class="[
                                    'w-11 h-11 rounded-full border-2 flex items-center justify-center transition-all duration-200 touch-manipulation active:scale-95',
                                    'hover:border-green-400 hover:bg-green-50 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-1',
                                    completingReminderIds.has(reminder.id)
                                        ? 'border-green-400 bg-green-100 cursor-not-allowed'
                                        : 'border-gray-300 hover:border-green-400 active:bg-green-100'
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
                             class="h-4 w-4 text-green-600 opacity-0 group-hover:opacity-100 transition-opacity duration-150"
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
<style scoped>
/* Ensure touch targets are exactly 44px for optimal mobile experience */
button {
  min-height: 44px;
  min-width: 44px;
}

/* Smooth animations optimized for mobile performance */
.transition-all {
  transition-property: all;
  transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
  transition-duration: 200ms;
}

.transition-opacity {
  transition-property: opacity;
  transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
  transition-duration: 150ms;
}

/* Optimize for touch interactions */
.touch-manipulation {
  touch-action: manipulation;
  -webkit-tap-highlight-color: transparent;
}

/* Enhanced mobile hover states */
@media (hover: hover) {
  .hover\:shadow-sm:hover {
    box-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.05);
  }

  .hover\:bg-red-100:hover {
    background-color: rgb(254 226 226);
  }

  .hover\:bg-orange-100:hover {
    background-color: rgb(255 237 213);
  }

  .hover\:bg-blue-100:hover {
    background-color: rgb(219 234 254);
  }
}

/* Active states for touch feedback */
.active\:scale-\[0\.98\]:active {
  transform: scale(0.98);
}

.active\:scale-95:active {
  transform: scale(0.95);
}

.active\:bg-green-100:active {
  background-color: rgb(220 252 231);
}

/* Time icon enhancements */
.filter.drop-shadow-sm {
  filter: drop-shadow(0 1px 1px rgb(0 0 0 / 0.05));
}

/* Loading spinner animation */
@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

.animate-spin {
  animation: spin 1s linear infinite;
}

/* Pulse animation for loading skeleton */
@keyframes pulse {
  0%, 100% {
    opacity: 1;
  }
  50% {
    opacity: .5;
  }
}

.animate-pulse {
  animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

/* Responsive text sizing */
@media (max-width: 640px) {
  .text-sm {
    font-size: 0.875rem;
    line-height: 1.25rem;
  }

  .text-xs {
    font-size: 0.75rem;
    line-height: 1rem;
  }
}

/* Ensure proper spacing on very small screens */
@media (max-width: 375px) {
  .p-3 {
    padding: 0.625rem;
  }

  .space-x-3 > :not([hidden]) ~ :not([hidden]) {
    margin-left: 0.625rem;
  }
}

/* Screen reader only content */
.sr-only {
  position: absolute;
  width: 1px;
  height: 1px;
  padding: 0;
  margin: -1px;
  overflow: hidden;
  clip: rect(0, 0, 0, 0);
  white-space: nowrap;
  border: 0;
}

/* Enhanced error state styling */
.bg-red-50 {
  background-color: rgb(254 242 242);
}

.border-red-200 {
  border-color: rgb(254 202 202);
}

.text-red-700 {
  color: rgb(185 28 28);
}

.bg-red-100 {
  background-color: rgb(254 226 226);
}

.hover\:bg-red-200:hover {
  background-color: rgb(254 202 202);
}

.active\:bg-red-300:active {
  background-color: rgb(252 165 165);
}

/* Loading state improvements */
.animate-pulse {
  animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

@keyframes pulse {
  0%, 100% {
    opacity: 1;
  }
  50% {
    opacity: .5;
  }
}

/* Improved focus states for accessibility */
button:focus-visible {
  outline: 2px solid #3b82f6;
  outline-offset: 2px;
}

/* Better touch feedback */
@media (hover: none) and (pointer: coarse) {
  .hover\:border-green-400:hover {
    border-color: rgb(74 222 128);
  }

  .hover\:bg-green-50:hover {
    background-color: rgb(240 253 244);
  }
}

/* Border-left styling for urgency indicators */
.border-l-4 {
  border-left-width: 4px;
}

/* Focus ring improvements for accessibility */
.focus\:ring-2:focus {
  --tw-ring-offset-shadow: var(--tw-ring-inset) 0 0 0 var(--tw-ring-offset-width) var(--tw-ring-offset-color);
  --tw-ring-shadow: var(--tw-ring-inset) 0 0 0 calc(2px + var(--tw-ring-offset-width)) var(--tw-ring-color);
  box-shadow: var(--tw-ring-offset-shadow), var(--tw-ring-shadow), var(--tw-shadow, 0 0 #0000);
}

.focus\:ring-green-500:focus {
  --tw-ring-color: rgb(34 197 94);
}

.focus\:ring-offset-1:focus {
  --tw-ring-offset-width: 1px;
}
</style>
