<script setup>
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';

const { t, tm } = useI18n();
const i18n = useI18n({ useScope: 'global' });

const currentLanguage = computed(() => i18n.locale.value);

function formatContent(content) {
    return content.replace(/\*\*(.*?)\*\*/g, '<strong class="text-white">$1</strong>');
}

</script>

<template>
    <Head :title="t('landing.footer_terms')" />
    <div class="min-h-screen bg-gradient-to-br from-slate-900 via-blue-900 to-indigo-900 text-white overflow-hidden">
        <!-- Header -->
        <header class="relative z-50 px-6 py-4">
            <nav class="max-w-7xl mx-auto flex justify-between items-center">
                <Link :href="route('landing')" class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-gradient-to-r from-blue-400 to-purple-500 rounded-xl flex items-center justify-center">
                        <span class="text-white font-bold text-xl">M</span>
                    </div>
                    <span class="text-2xl font-bold">MicroNote</span>
                </Link>

                <div class="flex items-center space-x-4">
                    <Link :href="route('landing')" class="text-blue-300 hover:text-white transition-colors flex items-center space-x-2 pr-4 border-r border-slate-600">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                        </svg>
                        <span>{{ t('terms.back') }}</span>
                    </Link>
                    <button @click="i18n.locale.value = 'ro'" :class="['px-4 py-2 rounded-md transition-colors', currentLanguage === 'ro' ? 'bg-blue-500 text-white' : 'text-blue-300 hover:bg-slate-700']">
                        Română
                    </button>
                    <button @click="i18n.locale.value = 'en'" :class="['px-4 py-2 rounded-md transition-colors', currentLanguage === 'en' ? 'bg-blue-500 text-white' : 'text-blue-300 hover:bg-slate-700']">
                        English
                    </button>
                </div>
            </nav>
        </header>

        <!-- Content Section -->
        <main class="px-6 py-16">
            <div class="max-w-4xl mx-auto bg-slate-800/50 rounded-2xl p-8 md:p-12 border border-slate-700/50">
                <div :key="currentLanguage">
                    <h1 class="text-4xl lg:text-5xl font-extrabold bg-gradient-to-r from-white via-blue-100 to-purple-200 bg-clip-text text-transparent leading-tight mb-4">
                        {{ t('terms.title') }}
                    </h1>
                    <p class="text-sm text-blue-200 mb-8">{{ t('terms.last_updated') }}</p>

                    <p class="text-blue-100 leading-relaxed mb-10" v-html="formatContent(t('terms.intro'))"></p>

                    <div class="space-y-8">
                        <section v-for="(section, index) in tm('terms.sections')" :key="index">
                            <h2 class="text-2xl font-bold text-white mb-3 border-b-2 border-blue-500/50 pb-2">{{ section.title }}</h2>
                            <p class="text-blue-100 leading-relaxed" v-html="formatContent(section.content)"></p>
                        </section>
                    </div>
                </div>
            </div>
        </main>

        <!-- Footer -->
        <footer class="py-12 px-6 mt-16 border-t border-slate-700/50">
            <div class="max-w-7xl mx-auto text-center text-sm text-blue-300">
                 <p>&copy; {{ new Date().getFullYear() }} MicroNote. {{ t('landing.footer_copyright') }} ❤️ {{ t('landing.footer_copyright_end') }}</p>
            </div>
        </footer>
    </div>
</template>
