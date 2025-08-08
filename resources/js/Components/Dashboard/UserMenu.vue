<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();
const page = usePage();

const props = defineProps({
    user: Object,
    searchQuery: String,
});

const emit = defineEmits(['update:searchQuery']);

const showUserDropdown = ref(false);
const showMobileUserMenu = ref(false);

const closeDropdown = () => {
    showUserDropdown.value = false;
    showMobileUserMenu.value = false;
};

onMounted(() => {
    document.addEventListener('click', closeDropdown);
});

onBeforeUnmount(() => {
    document.removeEventListener('click', closeDropdown);
});

const getPlanBadgeClass = (plan) => {
    return plan === 'plus'
        ? 'bg-gradient-to-r from-blue-500 to-purple-600 text-white'
        : 'bg-gray-100 text-gray-700 border border-gray-300';
};

const updateSearchQuery = (event) => {
    emit('update:searchQuery', event.target.value);
};
</script>

<template>
    <div class="flex items-center space-x-2">
        <!-- Desktop User Dropdown (ascuns pe mobile) -->
        <div class="relative hidden md:block" @click.stop>
            <button
                @click="showUserDropdown = !showUserDropdown"
                class="flex items-center space-x-2 bg-gray-50 hover:bg-gray-100 rounded-lg px-3 py-2 transition-colors duration-200"
            >
                <div class="w-8 h-8 rounded-full bg-gradient-to-r from-primary to-secondary flex items-center justify-center text-white font-medium text-sm">
                    {{ user.name.charAt(0).toUpperCase() }}
                </div>
                <div class="text-left">
                    <div class="text-sm font-medium text-gray-900">{{ user.name }}</div>
                    <div class="flex items-center space-x-1">
                        <span :class="['inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium', getPlanBadgeClass(user.plan)]">
                            {{ user.plan === 'plus' ? 'Plus' : 'Free' }}
                        </span>
                    </div>
                </div>
                <svg :class="['h-4 w-4 text-gray-500 transition-transform duration-200', showUserDropdown ? 'rotate-180' : '']" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>

            <div v-if="showUserDropdown" class="absolute right-0 mt-2 w-56 bg-white rounded-lg shadow-lg border border-gray-200 py-1 z-50">
                <div class="px-4 py-3 border-b border-gray-100">
                    <div class="flex items-center space-x-3">
                        <div>
                            <div class="font-medium text-gray-900">{{ user.name }}</div>
                            <div class="text-sm text-gray-500">{{ user.email }}</div>
                            <span :class="['inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium mt-1', getPlanBadgeClass(user.plan)]">
                                {{ user.plan === 'plus' ? 'Plan Plus' : 'Plan Free' }}
                            </span>
                        </div>
                    </div>
                </div>
                <div class="py-1">
                    <Link :href="route('profile.edit')" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors" @click="showUserDropdown = false">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-3 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        {{ t('common.profile') }}
                    </Link>
                    <Link :href="route('telegram.connect')" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors" @click="showUserDropdown = false">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-3 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                        {{ t('dashboard.telegram_connection') }}
                        <span v-if="!user.telegram_id" class="ml-auto w-2 h-2 bg-yellow-400 rounded-full"></span>
                    </Link>
                    <a v-if="user.plan === 'free'" href="#" class="flex items-center px-4 py-2 text-sm text-blue-600 hover:bg-blue-50 transition-colors" @click="showUserDropdown = false">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-3 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                        {{ t('dashboard.upgrade_to_plus') }}
                        <span class="ml-auto bg-gradient-to-r from-blue-500 to-purple-600 text-white text-xs px-2 py-1 rounded-full">2€/{{ t('common.month') }}</span>
                    </a>
                    <div class="border-t border-gray-100 my-1"></div>
                    <Link :href="route('logout')" method="post" as="button" class="flex items-center w-full px-4 py-2 text-sm text-red-600 hover:bg-red-50 transition-colors" @click="showUserDropdown = false">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-3 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                        {{ t('common.logout') }}
                    </Link>
                </div>
            </div>
        </div>

        <!-- Mobile Settings Button -->
        <div class="md:hidden relative" @click.stop>
            <button @click="showMobileUserMenu = !showMobileUserMenu" class="p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-lg transition-colors" :title="t('dashboard.user_settings')">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            </button>

            <div v-if="showMobileUserMenu" class="absolute right-0 mt-2 w-64 bg-white rounded-lg shadow-xl border border-gray-200 py-2 z-50">
                <div class="px-4 py-3 border-b border-gray-100">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 rounded-full bg-gradient-to-r from-primary to-secondary flex items-center justify-center text-white font-medium">
                            {{ user.name.charAt(0).toUpperCase() }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="font-medium text-gray-900 truncate">{{ user.name }}</div>
                            <div class="text-sm text-gray-500 truncate">{{ user.email }}</div>
                            <span :class="['inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium mt-1', getPlanBadgeClass(user.plan)]">
                                {{ user.plan === 'plus' ? 'Plan Plus' : 'Plan Free' }}
                            </span>
                        </div>
                    </div>
                </div>
                <div class="py-2">
                    <div class="px-4 pb-3 border-b border-gray-100">
                        <div class="relative">
                            <input :value="searchQuery" @input="updateSearchQuery" type="text" :placeholder="t('dashboard.search_in_notes')" class="w-full py-2 pl-9 pr-4 bg-gray-50 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm"/>
                            <svg xmlns="http://www.w3.org/2000/svg" class="absolute left-3 top-3 text-gray-400 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        </div>
                    </div>
                    <div class="border-t border-gray-100 my-2"></div>
                    <Link :href="route('profile.edit')" class="flex items-center px-4 py-3 text-sm text-gray-700 hover:bg-gray-50 transition-colors" @click="showMobileUserMenu = false">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        {{ t('common.profile') }}
                    </Link>
                    <Link :href="route('telegram.connect')" class="flex items-center px-4 py-3 text-sm text-gray-700 hover:bg-gray-50 transition-colors" @click="showMobileUserMenu = false">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                        {{ t('dashboard.telegram_connection') }}
                        <span v-if="!user.telegram_id" class="ml-auto w-2 h-2 bg-yellow-400 rounded-full"></span>
                    </Link>
                    <a v-if="user.plan === 'free'" href="#" class="flex items-center px-4 py-3 text-sm text-blue-600 hover:bg-blue-50 transition-colors" @click="showMobileUserMenu = false">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                        {{ t('dashboard.upgrade_to_plus') }}
                        <span class="ml-auto bg-gradient-to-r from-blue-500 to-purple-600 text-white text-xs px-2 py-1 rounded-full">2€</span>
                    </a>
                    <div class="border-t border-gray-100 my-2"></div>
                    <Link :href="route('logout')" method="post" as="button" class="flex items-center w-full px-4 py-3 text-sm text-red-600 hover:bg-red-50 transition-colors" @click="showMobileUserMenu = false">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                        {{ t('common.logout') }}
                    </Link>
                </div>
            </div>
        </div>
    </div>
</template>
