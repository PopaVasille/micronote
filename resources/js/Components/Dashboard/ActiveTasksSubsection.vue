<template>
  <div class="space-y-3 flex flex-col h-full">
    <!-- Section Header -->
    <div class="flex items-center justify-between">
      <h3 class="text-sm font-medium text-gray-900 flex items-center">
        <span class="text-base mr-2">✅</span>
        <span>{{ $t('dashboard.unified_section.active_tasks') }}</span>
        <span v-if="!isLoading && tasks.length > 0" class="ml-2 text-xs text-gray-500 bg-gray-100 px-2 py-1 rounded-full">
          {{ tasks.length }}
        </span>
      </h3>
    </div>

    <!-- Loading State - Mobile Optimized -->
    <div v-if="isLoading" class="space-y-2">
      <div v-for="i in 3" :key="i" class="animate-pulse">
        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg border border-gray-100">
          <div class="flex items-center space-x-3 flex-1">
            <!-- Priority indicator skeleton -->
            <div class="w-4 h-4 bg-gray-200 rounded-full flex-shrink-0"></div>
            <div class="flex-1 min-w-0">
              <!-- Task title skeleton -->
              <div class="h-4 bg-gray-200 rounded w-3/4 mb-2"></div>
              <!-- Due date skeleton -->
              <div class="h-3 bg-gray-200 rounded w-1/2"></div>
            </div>
          </div>
          <!-- Completion button skeleton -->
          <div class="w-11 h-11 bg-gray-200 rounded-full flex-shrink-0"></div>
        </div>
      </div>
      <!-- Loading text for screen readers -->
      <div class="sr-only" aria-live="polite">{{ $t('dashboard.unified_section.loading_tasks_sr') }}</div>
    </div>

    <!-- Error State - Mobile Optimized -->
    <div v-else-if="error" class="p-4 bg-red-50 border border-red-200 rounded-lg">
      <div class="text-center">
        <div class="text-2xl mb-2">⚠️</div>
        <p class="text-sm text-red-700 font-medium mb-3">{{ error }}</p>
        <button
          @click="retryFetchTasks"
          class="inline-flex items-center px-4 py-2 bg-red-100 hover:bg-red-200 active:bg-red-300 text-red-700 text-sm font-medium rounded-lg transition-colors duration-200 touch-manipulation"
          :disabled="isRetrying"
        >
          <span v-if="isRetrying" class="w-4 h-4 border-2 border-red-600 border-t-transparent rounded-full animate-spin mr-2"></span>
          <span v-else class="mr-2">🔄</span>
          {{ isRetrying ? $t('dashboard.unified_section.retrying') : $t('dashboard.unified_section.retry') }}
        </button>
      </div>
      <!-- Error announcement for screen readers -->
      <div class="sr-only" aria-live="assertive">{{ $t('dashboard.unified_section.error_loading_tasks_sr') }} {{ error }}</div>
    </div>

    <!-- Empty State - Mobile Optimized -->
    <div v-else-if="tasks.length === 0" class="p-6 text-center">
      <div class="text-4xl mb-3">🎉</div>
      <p class="text-sm font-medium text-gray-700 mb-1">{{ $t('dashboard.unified_section.no_active_tasks') }}</p>
      <p class="text-xs text-gray-500 mb-4">{{ $t('dashboard.unified_section.all_tasks_completed') }}</p>
      <div class="text-xs text-gray-400 bg-gray-50 rounded-lg p-3 border border-gray-100">
        💡 {{ $t('dashboard.unified_section.new_tasks_appear_here') }}
      </div>
      <!-- Empty state announcement for screen readers -->
      <div class="sr-only" aria-live="polite">{{ $t('dashboard.unified_section.no_active_tasks_sr') }}</div>
    </div>

    <!-- Tasks List -->
    <div v-else class="space-y-2 flex-1">
      <div
        v-for="task in sortedTasks"
        :key="task.id"
        class="group flex items-center justify-between p-3 bg-gray-50 border border-gray-100 rounded-lg hover:border-gray-200 hover:bg-white transition-all duration-200 active:scale-[0.98] cursor-pointer"
        :class="{
          'opacity-50 pointer-events-none': completingTaskIds.has(task.id),
          'transform hover:scale-[1.01] hover:shadow-sm': !completingTaskIds.has(task.id)
        }"
        @click="handleTaskClick(task)"
      >
        <!-- Task Content -->
        <div class="flex items-center space-x-3 flex-1 min-w-0">
          <!-- Priority Indicator with enhanced visibility -->
          <div class="flex-shrink-0">
            <span
              class="text-lg leading-none"
              :class="{
                'filter drop-shadow-sm': task.priority === 3,
                'opacity-90': task.priority === 2,
                'opacity-75': task.priority === 1
              }"
              :title="getPriorityLabel(task.priority)"
            >
              {{ getPriorityIcon(task.priority) }}
            </span>
          </div>

          <!-- Task Details -->
          <div class="flex-1 min-w-0">
            <div class="flex items-center space-x-2 mb-1">
              <h4 class="text-sm font-medium text-gray-900 truncate leading-tight group-hover:text-blue-700 transition-colors">
                {{ task.title }}
              </h4>
              <!-- Click indicator -->
              <svg class="w-3 h-3 text-gray-400 opacity-0 group-hover:opacity-100 transition-opacity" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
              </svg>
            </div>
            
            <!-- Due Date Info with improved mobile styling -->
            <div class="flex items-center space-x-2 text-xs">
              <span
                v-if="task.due_date"
                class="inline-flex items-center px-2 py-1 rounded-full font-medium"
                :class="getDueDateClasses(task)"
              >
                <span class="mr-1 text-xs">📅</span>
                {{ formatDueDate(task) }}
              </span>
              <span
                v-else
                class="inline-flex items-center px-2 py-1 rounded-full bg-blue-50 text-blue-700 border border-blue-100 font-medium"
              >
                <span class="mr-1 text-xs">📝</span>
                {{ $t('dashboard.unified_section.no_due_date') }}
              </span>
            </div>
          </div>
        </div>

        <!-- Completion Button with enhanced mobile touch targets -->
        <button
          @click.stop="completeTask(task)"
          :disabled="completingTaskIds.has(task.id)"
          class="flex-shrink-0 w-11 h-11 flex items-center justify-center rounded-full border-2 transition-all duration-200 touch-manipulation active:scale-95"
          :class="{
            'border-gray-300 hover:border-green-400 hover:bg-green-50 active:bg-green-100': !completingTaskIds.has(task.id),
            'border-green-300 bg-green-50': completingTaskIds.has(task.id)
          }"
          :title="completingTaskIds.has(task.id) ? $t('dashboard.unified_section.completing') : $t('dashboard.unified_section.mark_as_completed')"
        >
          <span
            v-if="completingTaskIds.has(task.id)"
            class="w-4 h-4 border-2 border-green-500 border-t-transparent rounded-full animate-spin"
          ></span>
          <span
            v-else
            class="text-gray-500 hover:text-green-600 active:text-green-700 text-lg transition-colors duration-150"
          >
            ✓
          </span>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { router } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'

const { t: $t } = useI18n()

// Props and Emits
const emit = defineEmits(['task-completed', 'task-clicked'])

// Reactive State
const tasks = ref([])
const isLoading = ref(true)
const error = ref(null)
const isRetrying = ref(false)
const completingTaskIds = ref(new Set())
const retryCount = ref(0)
const maxRetries = ref(3)
const isOnline = ref(navigator.onLine)
const abortController = ref(null)

// Computed Properties
const sortedTasks = computed(() => {
  return tasks.value.sort((a, b) => {
    // First by priority (highest first: 3=High, 2=Medium, 1=Normal)
    if (a.priority !== b.priority) {
      return b.priority - a.priority
    }
    // Then by due date (tasks with due dates first)
    if (a.due_date && !b.due_date) return -1
    if (!a.due_date && b.due_date) return 1
    // Then by creation date (newest first)
    return new Date(b.created_at) - new Date(a.created_at)
  })
})

// Methods
const fetchActiveTasks = async (isRetry = false) => {
  try {
    if (isRetry) {
      isRetrying.value = true
      retryCount.value++
    } else {
      isLoading.value = true
      retryCount.value = 0
    }
    error.value = null
    
    // Cancel previous request if still pending
    if (abortController.value) {
      abortController.value.abort()
    }
    
    // Create new abort controller for this request
    abortController.value = new AbortController()
    
    const response = await fetch('/api/tasks/active', {
      method: 'GET',
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      },
      signal: abortController.value.signal,
      // Add timeout for mobile networks
      timeout: 10000
    })
    
    if (!response.ok) {
      const errorData = await response.json().catch(() => ({}))
      
      if (response.status === 404) {
        throw new Error(errorData.message || $t('dashboard.unified_section.task_service_unavailable'))
      } else if (response.status >= 500) {
        throw new Error(errorData.message || $t('dashboard.unified_section.server_error'))
      } else if (response.status === 401) {
        throw new Error(errorData.message || $t('dashboard.unified_section.session_expired'))
      } else if (response.status === 429) {
        throw new Error('Prea multe cereri. Încearcă din nou în câteva secunde.')
      } else {
        throw new Error(errorData.message || $t('dashboard.unified_section.could_not_load_tasks'))
      }
    }
    
    const data = await response.json()
    
    // Validate response structure
    if (!data || typeof data !== 'object') {
      throw new Error('Răspuns invalid de la server')
    }
    
    // Combine tasks with and without due dates
    const allTasks = [
      ...(Array.isArray(data.tasks_with_due_date) ? data.tasks_with_due_date : []),
      ...(Array.isArray(data.tasks_without_due_date) ? data.tasks_without_due_date : [])
    ]
    
    // Validate task structure
    const validTasks = allTasks.filter(task => 
      task && 
      typeof task === 'object' && 
      task.id && 
      task.title &&
      typeof task.priority === 'number'
    )
    
    tasks.value = validTasks
    retryCount.value = 0 // Reset retry count on success
    
  } catch (err) {
    if (err.name === 'AbortError') {
      // Request was cancelled, don't show error
      return
    }
    
    let errorMessage = $t('dashboard.unified_section.could_not_load_tasks')
    
    if (err.name === 'TypeError' && (err.message.includes('fetch') || err.message.includes('network'))) {
      errorMessage = $t('dashboard.unified_section.check_internet_connection')
    } else if (err.message.includes('timeout')) {
      errorMessage = 'Cererea a expirat. Verifică conexiunea și încearcă din nou.'
    } else if (err.message) {
      errorMessage = err.message
    }
    
    error.value = errorMessage
    console.error('Error fetching active tasks:', err)
    
    // Auto-retry with exponential backoff for network errors
    if (!isRetry && retryCount.value < maxRetries.value && isOnline.value) {
      const delay = Math.min(1000 * Math.pow(2, retryCount.value), 10000) // Max 10 seconds
      setTimeout(() => {
        fetchActiveTasks(true)
      }, delay)
    }
    
  } finally {
    isLoading.value = false
    isRetrying.value = false
    abortController.value = null
  }
}

const retryFetchTasks = () => {
  fetchActiveTasks(true)
}

const completeTask = async (task) => {
  if (completingTaskIds.value.has(task.id)) return
  
  // Validate task object
  if (!task || !task.id || typeof task.id !== 'number') {
    error.value = 'Task invalid. Reîncarcă pagina și încearcă din nou.'
    setTimeout(() => { error.value = null }, 5000)
    return
  }
  
  // Check if user is online
  if (!isOnline.value) {
    error.value = 'Nu ești conectat la internet. Verifică conexiunea și încearcă din nou.'
    setTimeout(() => { error.value = null }, 5000)
    return
  }
  
  try {
    completingTaskIds.value.add(task.id)
    
    // Optimistic update - remove task immediately
    const originalTasks = [...tasks.value]
    const taskIndex = tasks.value.findIndex(t => t.id === task.id)
    
    if (taskIndex === -1) {
      throw new Error('Task-ul nu mai există în listă')
    }
    
    tasks.value = tasks.value.filter(t => t.id !== task.id)
    
    // Make API call with timeout and retry logic
    let attempts = 0
    const maxAttempts = 3
    
    const attemptCompletion = async () => {
      attempts++
      
      try {
        await router.post(`/api/tasks/${task.id}/complete`, {}, {
          preserveState: true,
          preserveScroll: true,
          timeout: 8000, // 8 second timeout
          onError: (errors) => {
            throw new Error(errors?.message || 'Server error')
          },
          onSuccess: () => {
            // Emit event to parent
            emit('task-completed', task)
          }
        })
      } catch (routerError) {
        if (attempts < maxAttempts && isOnline.value) {
          // Exponential backoff retry
          const delay = Math.min(1000 * Math.pow(2, attempts - 1), 5000)
          await new Promise(resolve => setTimeout(resolve, delay))
          return attemptCompletion()
        }
        throw routerError
      }
    }
    
    await attemptCompletion()
    
  } catch (err) {
    // Restore task on error
    const sortedTasks = [...tasks.value, task].sort((a, b) => {
      if (a.priority !== b.priority) return b.priority - a.priority
      if (a.due_date && !b.due_date) return -1
      if (!a.due_date && b.due_date) return 1
      return new Date(b.created_at) - new Date(a.created_at)
    })
    tasks.value = sortedTasks
    
    // Determine error message
    let errorMessage = $t('dashboard.unified_section.task_completion_failed')
    
    if (err.message) {
      if (err.message.includes('timeout')) {
        errorMessage = 'Cererea a expirat. Verifică conexiunea și încearcă din nou.'
      } else if (err.message.includes('network') || err.message.includes('fetch')) {
        errorMessage = $t('dashboard.unified_section.connection_interrupted')
      } else if (err.message.includes('404')) {
        errorMessage = 'Task-ul nu a fost găsit. Reîncarcă pagina.'
      } else if (err.message.includes('409')) {
        errorMessage = 'Task-ul este în curs de procesare. Încearcă din nou în câteva secunde.'
      } else if (err.message.includes('429')) {
        errorMessage = 'Prea multe cereri. Încearcă din nou în câteva secunde.'
      } else {
        errorMessage = err.message
      }
    }
    
    error.value = errorMessage
    console.error('Error completing task:', err)
    
    // Auto-clear error after 7 seconds
    setTimeout(() => {
      error.value = null
    }, 7000)
    
  } finally {
    completingTaskIds.value.delete(task.id)
  }
}

const getPriorityIcon = (priority) => {
  switch (priority) {
    case 3: return '🔥' // High
    case 2: return '⚡' // Medium
    case 1: 
    default: return '▫️' // Normal
  }
}

const getPriorityLabel = (priority) => {
  switch (priority) {
    case 3: return $t('dashboard.unified_section.priority_high')
    case 2: return $t('dashboard.unified_section.priority_medium')
    case 1:
    default: return $t('dashboard.unified_section.priority_normal')
  }
}

const getDueDateClasses = (task) => {
  if (!task.due_date) return ''
  
  const today = new Date().toISOString().split('T')[0]
  const dueDate = task.due_date
  
  if (dueDate === today) {
    return 'bg-orange-50 text-orange-700 border border-orange-200 font-medium'
  } else if (dueDate < today) {
    return 'bg-red-50 text-red-700 border border-red-200 font-medium'
  } else {
    return 'bg-green-50 text-green-700 border border-green-200 font-medium'
  }
}

const formatDueDate = (task) => {
  if (!task.due_date) return ''
  
  const today = new Date().toISOString().split('T')[0]
  const dueDate = task.due_date
  
  if (dueDate === today) {
    return task.due_time ? `${$t('dashboard.unified_section.today')}, ${task.due_time}` : $t('dashboard.unified_section.today')
  } else if (dueDate < today) {
    return task.due_time ? `${$t('dashboard.unified_section.overdue')}, ${task.due_time}` : $t('dashboard.unified_section.overdue')
  } else {
    // Format future date
    const date = new Date(dueDate + 'T00:00:00')
    const options = { month: 'short', day: 'numeric' }
    const formattedDate = date.toLocaleDateString('ro-RO', options)
    return task.due_time ? `${formattedDate}, ${task.due_time}` : formattedDate
  }
}

const handleTaskClick = (task) => {
  if (completingTaskIds.value.has(task.id)) return
  emit('task-clicked', task)
}

// Network connectivity monitoring
const handleOnline = () => {
  isOnline.value = true
  // Retry fetching tasks if there was an error and we're back online
  if (error.value && !isLoading.value) {
    fetchActiveTasks(true)
  }
}

const handleOffline = () => {
  isOnline.value = false
  error.value = 'Nu ești conectat la internet. Verifică conexiunea.'
}

// Cleanup function
const cleanup = () => {
  // Cancel any pending requests
  if (abortController.value) {
    abortController.value.abort()
    abortController.value = null
  }
  
  // Clear any pending timeouts
  completingTaskIds.value.clear()
  
  // Remove event listeners
  window.removeEventListener('online', handleOnline)
  window.removeEventListener('offline', handleOffline)
}

// Lifecycle
onMounted(() => {
  // Add network connectivity listeners
  window.addEventListener('online', handleOnline)
  window.addEventListener('offline', handleOffline)
  
  // Initial fetch
  fetchActiveTasks()
})

// Cleanup on unmount
import { onUnmounted } from 'vue'
onUnmounted(() => {
  cleanup()
})
</script>

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

.transition-colors {
  transition-property: color, background-color, border-color;
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
  .hover\:scale-\[1\.01\]:hover {
    transform: scale(1.01);
  }
  
  .hover\:shadow-sm:hover {
    box-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.05);
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

.active\:text-green-700:active {
  color: rgb(21 128 61);
}

/* Priority indicator enhancements */
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
  .hover\:border-gray-200:hover {
    border-color: rgb(229 231 235);
  }
  
  .hover\:bg-white:hover {
    background-color: rgb(255 255 255);
  }
}
</style>