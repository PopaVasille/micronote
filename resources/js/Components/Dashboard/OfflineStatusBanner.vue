<script setup>
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

defineProps({
    isOffline: Boolean,
    hasPendingChanges: Boolean,
});
</script>

<template>
    <div v-if="isOffline || hasPendingChanges" class="bg-gray-100 border-l-4 border-yellow-500 p-4 mb-4">
        <div class="flex items-center">
            <div v-if="isOffline" class="flex items-center text-yellow-700">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20"
                     fill="currentColor">
                    <path fill-rule="evenodd"
                          d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                          clip-rule="evenodd"/>
                </svg>
                <span>{{ t('dashboard.offline_alert') }}</span>
            </div>
            <div v-else-if="hasPendingChanges" class="flex items-center text-yellow-700">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20"
                     fill="currentColor">
                    <path fill-rule="evenodd"
                          d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z"
                          clip-rule="evenodd"/>
                </svg>
                <span>{{ t('dashboard.sync_alert') }}</span>
            </div>
        </div>
    </div>
</template>
