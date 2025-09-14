<template>
  <div class="bg-white rounded-lg shadow-sm border border-gray-100 overflow-hidden">
    <!-- Mobile-Optimized Header -->
    <div class="bg-gradient-to-r from-blue-50 to-purple-50 px-3 py-3 border-b border-gray-100">
      <h2 class="text-base font-semibold text-gray-900 flex items-center">
        <span class="text-lg mr-2">📋</span>
        <span class="hidden sm:inline">{{ $t('dashboard.unified_section.title_full') }}</span>
        <span class="sm:hidden">{{ $t('dashboard.unified_section.title_mobile') }}</span>
      </h2>
    </div>
    
    <!-- Component Error Display -->
    <div v-if="componentError" class="p-3 bg-red-50 border-b border-red-200">
      <div class="flex items-center justify-between">
        <div class="flex items-center space-x-2">
          <span class="text-red-600 text-sm">⚠️</span>
          <span class="text-red-700 text-sm font-medium">{{ componentError }}</span>
        </div>
        <button
          @click="clearComponentError"
          class="text-red-600 hover:text-red-800 text-sm font-medium"
        >
          ✕
        </button>
      </div>
    </div>

    <!-- Mobile-First Content with responsive layout -->
    <div class="p-3 lg:p-0">
      <!-- Mobile: Stack vertically, Desktop: 2 columns side by side -->
      <div class="space-y-4 lg:space-y-0 lg:grid lg:grid-cols-2">
        <ActiveTasksSubsection
          @task-completed="handleTaskCompleted"
          @filter-tasks="handleFilterTasks"
          @task-clicked="handleTaskClicked"
        />
        <!-- Divider: horizontal on mobile only -->
        <div class="border-t border-gray-100 lg:hidden"></div>
        <RemindersSubsection
          @reminder-completed="handleReminderCompleted"
          @filter-reminders="handleFilterReminders"
          @reminder-clicked="handleReminderClicked"
        />
      </div>
    </div>
  </div>
</template>

<script setup>
import { defineEmits, ref, onMounted, onUnmounted } from 'vue'
import { useI18n } from 'vue-i18n'
import ActiveTasksSubsection from './ActiveTasksSubsection.vue'
import RemindersSubsection from './RemindersSubsection.vue'

const { t: $t } = useI18n()

// Define events that will be emitted to parent Dashboard component
const emit = defineEmits([
  'task-completed',
  'reminder-completed', 
  'filter-tasks',
  'filter-reminders',
  'task-clicked',
  'reminder-clicked',
  'error'
])

// Error handling state
const componentError = ref(null)
const isComponentMounted = ref(false)

// Event handlers that propagate events to parent Dashboard component
const handleTaskCompleted = (task) => {
  try {
    emit('task-completed', task)
  } catch (err) {
    console.error('Error handling task completion:', err)
    componentError.value = 'Eroare la procesarea completării task-ului'
    emit('error', err)
  }
}

const handleReminderCompleted = (reminder) => {
  try {
    emit('reminder-completed', reminder)
  } catch (err) {
    console.error('Error handling reminder completion:', err)
    componentError.value = 'Eroare la procesarea completării mementoului'
    emit('error', err)
  }
}

const handleFilterTasks = () => {
  try {
    emit('filter-tasks')
  } catch (err) {
    console.error('Error handling task filter:', err)
    componentError.value = 'Eroare la filtrarea task-urilor'
    emit('error', err)
  }
}

const handleFilterReminders = () => {
  try {
    emit('filter-reminders')
  } catch (err) {
    console.error('Error handling reminder filter:', err)
    componentError.value = 'Eroare la filtrarea mementourilor'
    emit('error', err)
  }
}

const handleTaskClicked = (task) => {
  try {
    if (!task || !task.id) {
      throw new Error('Invalid task object')
    }
    emit('task-clicked', task)
  } catch (err) {
    console.error('Error handling task click:', err)
    componentError.value = 'Eroare la deschiderea task-ului'
    emit('error', err)
  }
}

const handleReminderClicked = (reminder) => {
  try {
    if (!reminder || !reminder.id) {
      throw new Error('Invalid reminder object')
    }
    emit('reminder-clicked', reminder)
  } catch (err) {
    console.error('Error handling reminder click:', err)
    componentError.value = 'Eroare la deschiderea mementoului'
    emit('error', err)
  }
}

// Error recovery
const clearComponentError = () => {
  componentError.value = null
}

// Lifecycle management
onMounted(() => {
  isComponentMounted.value = true
  
  // Clear any component errors after 10 seconds
  if (componentError.value) {
    setTimeout(clearComponentError, 10000)
  }
})

onUnmounted(() => {
  isComponentMounted.value = false
  clearComponentError()
})
</script>

<style scoped>
/* Desktop layout improvements */
@media (min-width: 1024px) {
  /* Grid layout for desktop - align items to start */
  .lg\:grid-cols-2 {
    display: grid;
    grid-template-columns: 1fr 1fr;
    align-items: start;
    gap: 1.5rem;
  }

  /* Only style the actual components, not dividers */
  .lg\:grid-cols-2 > *:not(.lg\:hidden) {
    background: rgba(249, 250, 251, 0.3);
    border-radius: 8px;
    padding: 16px;
    min-height: 300px;
    display: flex;
    flex-direction: column;
  }

  /* Hide horizontal dividers on desktop */
  .lg\:hidden {
    display: none;
  }
}

/* Smooth transitions for responsive changes */
.lg\:grid {
  transition: all 0.3s ease;
}

/* Hover effects for clickable items */
.cursor-pointer:hover {
  transform: translateY(-1px);
}
</style>