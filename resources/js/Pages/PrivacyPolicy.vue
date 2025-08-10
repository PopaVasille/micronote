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
    <Head :title="t('privacy.title')" />
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
                        <span>{{ t('privacy.back') }}</span>
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
                        {{ t('privacy.title') }}
                    </h1>
                    <p class="text-sm text-blue-200 mb-8">{{ t('privacy.last_updated') }}</p>

                    <p class="text-blue-100 leading-relaxed mb-10" v-html="formatContent(t('privacy.intro'))"></p>

                    <div class="space-y-8">
                        <section v-for="(section, index) in tm('privacy.sections')" :key="index">
                            <h2 class="text-2xl font-bold text-white mb-3 border-b-2 border-blue-500/50 pb-2">{{ section.title }}</h2>
                            <div v-if="section.content" class="text-blue-100 leading-relaxed mb-6" v-html="formatContent(section.content)"></div>
                            
                            <!-- Subsections if they exist -->
                            <div v-if="section.subsections" class="space-y-6">
                                <div v-for="(subsection, subIndex) in section.subsections" :key="subIndex" class="pl-4 border-l-2 border-slate-600">
                                    <h3 class="text-xl font-semibold text-white mb-3">{{ subsection.title }}</h3>
                                    <div v-if="subsection.content" class="text-blue-100 leading-relaxed mb-4" v-html="formatContent(subsection.content)"></div>
                                    
                                    <!-- List items if they exist -->
                                    <ul v-if="subsection.items" class="space-y-3 mb-4">
                                        <li v-for="(item, itemIndex) in subsection.items" :key="itemIndex" class="flex items-start space-x-3">
                                            <div class="w-2 h-2 bg-blue-400 rounded-full mt-2 flex-shrink-0"></div>
                                            <div class="text-blue-100 leading-relaxed" v-html="formatContent(item)"></div>
                                        </li>
                                    </ul>
                                </div>
                            </div>

                            <!-- Direct list items for sections without subsections -->
                            <ul v-if="section.items && !section.subsections" class="space-y-3">
                                <li v-for="(item, itemIndex) in section.items" :key="itemIndex" class="flex items-start space-x-3">
                                    <div class="w-2 h-2 bg-blue-400 rounded-full mt-2 flex-shrink-0"></div>
                                    <div class="text-blue-100 leading-relaxed" v-html="formatContent(item)"></div>
                                </li>
                            </ul>
                        </section>
                    </div>

                    <!-- Contact Section -->
                    <div class="mt-12 p-6 bg-slate-700/30 rounded-xl border border-slate-600/50">
                        <h3 class="text-xl font-bold text-white mb-4">{{ t('privacy.contact.title') }}</h3>
                        <div class="grid md:grid-cols-2 gap-6">
                            <div>
                                <h4 class="font-semibold text-blue-300 mb-2">{{ t('privacy.contact.general') }}</h4>
                                <p class="text-blue-100 text-sm">{{ t('privacy.contact.email') }}: privacy@micronote.ro</p>
                                <p class="text-blue-100 text-sm">{{ t('privacy.contact.dpo') }}: dpo@micronote.ro</p>
                            </div>
                            <div>
                                <h4 class="font-semibold text-blue-300 mb-2">{{ t('privacy.contact.authority') }}</h4>
                                <p class="text-blue-100 text-sm">{{ t('privacy.contact.anspdcp') }}</p>
                                <p class="text-blue-100 text-sm">anspdcp@dataprotection.ro</p>
                                <a href="https://www.dataprotection.ro" target="_blank" class="text-blue-400 hover:text-blue-300 text-sm">www.dataprotection.ro</a>
                            </div>
                        </div>
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