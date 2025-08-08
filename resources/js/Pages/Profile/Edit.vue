<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import UpdateLanguagePreferenceForm from './Partials/UpdateLanguagePreferenceForm.vue';
import { Head } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});
</script>

<template>
    <Head :title="t('common.profile')" />

    <div class="min-h-screen bg-gray-50">
        <!-- Header -->
        <header class="bg-white border-b border-gray-200 p-4">
            <div class="max-w-7xl mx-auto flex items-center justify-between">
                <div class="flex items-center">
                    <div class="flex items-center">
                        <div class="w-8 h-8 rounded-md bg-gradient-to-r from-primary to-secondary flex items-center justify-center text-white font-bold">
                            M
                        </div>
                        <h1 class="ml-2 text-xl font-bold">MicroNote</h1>
                    </div>
                    <div class="ml-8">
                        <h2 class="text-lg font-semibold text-gray-900">{{ t('common.profile') }}</h2>
                        <p class="text-sm text-gray-500">{{ t('profile.manage_account') }}</p>
                    </div>
                </div>
                
                <!-- Back to Dashboard -->
                <a :href="route('dashboard')" class="flex items-center text-sm text-gray-600 hover:text-gray-900 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    {{ t('common.back_to_dashboard') }}
                </a>
            </div>
        </header>

        <!-- Main Content -->
        <main class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Sidebar Navigation -->
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-lg border border-gray-200 p-4">
                        <nav class="space-y-1">
                            <a href="#profile-info" class="flex items-center px-3 py-2 text-sm font-medium text-gray-900 bg-gray-100 rounded-lg">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-3 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                                {{ t('profile.profile_information') }}
                            </a>
                            <a href="#language" class="flex items-center px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-lg">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-3 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"/>
                                </svg>
                                {{ t('profile.language_preferences') }}
                            </a>
                            <a href="#password" class="flex items-center px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-lg">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-3 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                                </svg>
                                {{ t('profile.password_security') }}
                            </a>
                            <a href="#danger" class="flex items-center px-3 py-2 text-sm font-medium text-red-700 hover:bg-red-50 rounded-lg">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-3 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L12.732 4c-.77-.833-1.964-.833-2.732 0L3.362 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                                </svg>
                                {{ t('profile.danger_zone') }}
                            </a>
                        </nav>
                    </div>
                </div>

                <!-- Content Area -->
                <div class="lg:col-span-2 space-y-8">
                    <!-- Profile Information -->
                    <div id="profile-info" class="bg-white rounded-lg border border-gray-200 p-6">
                        <UpdateProfileInformationForm
                            :must-verify-email="mustVerifyEmail"
                            :status="status"
                        />
                    </div>

                    <!-- Language Preferences -->
                    <div id="language" class="bg-white rounded-lg border border-gray-200 p-6">
                        <UpdateLanguagePreferenceForm />
                    </div>

                    <!-- Password & Security -->
                    <div id="password" class="bg-white rounded-lg border border-gray-200 p-6">
                        <UpdatePasswordForm />
                    </div>

                    <!-- Danger Zone -->
                    <div id="danger" class="bg-white rounded-lg border border-red-200 p-6">
                        <DeleteUserForm />
                    </div>
                </div>
            </div>
        </main>
    </div>
</template>

<style>
/* Apply the same color scheme as Dashboard */
:root {
    --color-primary: #10b981;
    --color-primary-light: #4ade80;
    --color-primary-dark: #059669;
    --color-secondary: #38bdf8;
    --color-secondary-light: #7dd3fc;
    --color-secondary-dark: #0284c7;
    --color-accent: #0ea5e9;
}

.bg-primary {
    background-color: var(--color-primary);
}

.from-primary {
    --tw-gradient-from: var(--color-primary);
    --tw-gradient-stops: var(--tw-gradient-from), var(--tw-gradient-to, rgba(16, 185, 129, 0));
}

.to-secondary {
    --tw-gradient-to: var(--color-secondary);
}
</style>