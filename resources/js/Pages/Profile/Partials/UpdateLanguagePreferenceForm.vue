<template>
    <section>
        <header>
            <h2 class="text-lg font-medium text-gray-900">{{ t('profile.language_preferences') }}</h2>
            <p class="mt-1 text-sm text-gray-600">
                {{ t('profile.language_description') }}
            </p>
        </header>

        <div class="mt-6 space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <label class="text-sm font-medium text-gray-700">{{ t('profile.interface_language') }}</label>
                    <p class="text-sm text-gray-500">{{ t('profile.interface_language_help') }}</p>
                </div>
                
                <div class="ml-4">
                    <LanguageSwitcher variant="light" />
                </div>
            </div>
        </div>
    </section>
</template>

<script setup>
import LanguageSwitcher from '@/Components/LanguageSwitcher.vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();
</script>