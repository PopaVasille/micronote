<script setup>
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

defineProps({
    show: Boolean,
});
</script>

<template>
    <div v-if="show" class="bg-blue-50 border-l-4 border-blue-400 p-4 mb-6">
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd"
                          d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                          clip-rule="evenodd"/>
                </svg>
            </div>
            <div class="ml-3">
                <p class="text-sm text-blue-700">
                    {{ t('dashboard.messaging_platforms_alert') }}
                    <a :href="route('platform.selection')"
                       class="font-medium underline text-blue-700 hover:text-blue-600">
                        {{ t('dashboard.connect_messaging_platforms') }}
                    </a>
                </p>
            </div>
        </div>
    </div>
</template>
