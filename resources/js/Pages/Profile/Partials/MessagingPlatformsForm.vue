<script setup>
import { usePage, Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { computed } from 'vue';

const { t } = useI18n();
const page = usePage();

const user = computed(() => page.props.auth.user);
const isAnyPlatformConnected = computed(() => user.value.telegram_id || user.value.whatsapp_id);
</script>

<template>
    <section>
        <header>
            <h2 class="text-lg font-medium text-gray-900">{{ t('profile.messaging_platforms') }}</h2>
            <p class="mt-1 text-sm text-gray-600">{{ t('profile.messaging_platforms_description') }}</p>
        </header>

        <div class="mt-6 space-y-4">
            <!-- Platform Selection Option (if no platforms are connected) -->
            <div v-if="!isAnyPlatformConnected" class="border-2 border-dashed border-gray-300 rounded-lg p-6">
                <div class="text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">{{ t('profile.no_platforms_connected') }}</h3>
                    <p class="mt-1 text-sm text-gray-500">{{ t('profile.connect_platforms_to_start') }}</p>
                    <div class="mt-6">
                        <Link :href="route('platform.selection')" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            <svg class="mr-2 -ml-1 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            {{ t('profile.connect_platforms') }}
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Connected Platforms -->
            <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Telegram Platform -->
                <div class="border rounded-lg p-4" :class="user.telegram_id ? 'border-green-300 bg-green-50' : 'border-gray-200 bg-gray-50'">
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <div class="w-10 h-10 bg-blue-500 rounded-full flex items-center justify-center">
                                <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm4.64 6.8c-.15 1.58-.8 5.42-1.13 7.19-.14.75-.42 1-.68 1.03-.58.05-1.02-.38-1.58-.75-.88-.58-1.38-.94-2.23-1.5-.99-.65-.35-1.01.22-1.59.15-.15 2.71-2.48 2.76-2.69a.2.2 0 0 0-.05-.18c-.06-.05-.14-.03-.21-.02-.09.02-1.49.95-4.22 2.79-.4.27-.76.41-1.08.4-.36-.01-1.04-.2-1.55-.37-.63-.2-1.12-.31-1.08-.66.02-.18.27-.36.74-.55 2.92-1.27 4.86-2.11 5.83-2.51 2.78-1.16 3.35-1.36 3.73-1.36.08 0 .27.02.39.12.1.08.13.19.14.27-.01.06.01.24 0 .38z"/>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-3 flex-1">
                            <h3 class="text-sm font-medium text-gray-900">Telegram</h3>
                            <div v-if="user.telegram_id" class="mt-1">
                                <p class="text-sm text-green-700">{{ t('profile.connected') }}</p>
                                <p class="text-xs text-gray-500">ID: {{ user.telegram_id }}</p>
                            </div>
                            <div v-else class="mt-1">
                                <p class="text-sm text-gray-500">{{ t('profile.not_connected') }}</p>
                                <Link :href="route('telegram.connect')" class="text-xs text-blue-600 hover:text-blue-500">
                                    {{ t('profile.connect_now') }}
                                </Link>
                            </div>
                        </div>
                        <div v-if="user.telegram_id" class="flex-shrink-0">
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                <svg class="mr-1 h-3 w-3" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                                {{ t('profile.active') }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- WhatsApp Platform -->
                <div class="border rounded-lg p-4" :class="user.whatsapp_id ? 'border-green-300 bg-green-50' : 'border-gray-200 bg-gray-50'">
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <div class="w-10 h-10 bg-green-500 rounded-full flex items-center justify-center">
                                <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.890-5.335 11.893-11.893A11.821 11.821 0 0020.484 3.085"/>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-3 flex-1">
                            <h3 class="text-sm font-medium text-gray-900">WhatsApp</h3>
                            <div v-if="user.whatsapp_id" class="mt-1">
                                <p class="text-sm text-green-700">{{ t('profile.connected') }}</p>
                                <p class="text-xs text-gray-500">ID: {{ user.whatsapp_id }}</p>
                            </div>
                            <div v-else class="mt-1">
                                <p class="text-sm text-gray-500">{{ t('profile.not_connected') }}</p>
                                <Link :href="route('whatsapp.connect')" class="text-xs text-blue-600 hover:text-blue-500">
                                    {{ t('profile.connect_now') }}
                                </Link>
                            </div>
                        </div>
                        <div v-if="user.whatsapp_id" class="flex-shrink-0">
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                <svg class="mr-1 h-3 w-3" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                                {{ t('profile.active') }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Add Missing Platform Section (only shown if one platform is connected) -->
            <div v-if="isAnyPlatformConnected && (!user.telegram_id || !user.whatsapp_id)" class="border border-gray-200 rounded-lg p-4 bg-blue-50">
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div class="ml-3 flex-1">
                        <h3 class="text-sm font-medium text-blue-900">{{ t('profile.add_another_platform') }}</h3>
                        <p class="mt-1 text-sm text-blue-700">
                            {{ t('profile.add_another_platform_description') }}
                        </p>
                        <div class="mt-3 flex space-x-3">
                            <Link v-if="!user.telegram_id" :href="route('telegram.connect')" class="inline-flex items-center px-3 py-1 border border-blue-300 text-xs font-medium rounded text-blue-700 bg-white hover:bg-blue-50">
                                Telegram
                            </Link>
                            <Link v-if="!user.whatsapp_id" :href="route('whatsapp.connect')" class="inline-flex items-center px-3 py-1 border border-blue-300 text-xs font-medium rounded text-blue-700 bg-white hover:bg-blue-50">
                                WhatsApp
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>