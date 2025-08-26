<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();
</script>

<template>
    <Head :title="t('platform_selection.title')" />
    <div class="min-h-screen bg-gradient-to-br from-slate-900 via-indigo-900 to-purple-900 text-white flex flex-col items-center justify-center p-4 sm:p-6">
        
        <div class="w-full max-w-4xl">
            <div class="bg-slate-800/50 border border-slate-700/50 rounded-2xl shadow-2xl backdrop-blur-lg p-8 sm:p-10">
                <div class="text-center mb-12">
                    <div class="w-20 h-20 bg-gradient-to-r from-blue-400 to-purple-500 rounded-2xl flex items-center justify-center mx-auto shadow-lg mb-6">
                        <span class="text-white font-bold text-5xl">M</span>
                    </div>
                    <h1 class="text-4xl font-bold text-white mb-4">{{ t('platform_selection.welcome_title') }}</h1>
                    <p class="text-lg text-blue-200 max-w-2xl mx-auto">{{ t('platform_selection.welcome_subtitle') }}</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-12">
                    <!-- Telegram Option -->
                    <Link :href="route('telegram.connect')" 
                          class="group bg-slate-900/30 border-2 border-slate-700 hover:border-blue-500 rounded-2xl p-8 transition-all duration-300 hover:bg-slate-800/50 hover:scale-105 hover:shadow-2xl hover:shadow-blue-500/20">
                        <div class="flex flex-col items-center text-center">
                            <!-- Telegram Icon -->
                            <div class="w-16 h-16 bg-gradient-to-r from-blue-400 to-blue-600 rounded-full flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                                <svg class="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm4.64 6.8c-.15 1.58-.8 5.42-1.13 7.19-.14.75-.42 1-.68 1.03-.58.05-1.02-.38-1.58-.75-.88-.58-1.38-.94-2.23-1.5-.99-.65-.35-1.01.22-1.59.15-.15 2.71-2.48 2.76-2.69a.2.2 0 0 0-.05-.18c-.06-.05-.14-.03-.21-.02-.09.02-1.49.95-4.22 2.79-.4.27-.76.41-1.08.4-.36-.01-1.04-.2-1.55-.37-.63-.2-1.12-.31-1.08-.66.02-.18.27-.36.74-.55 2.92-1.27 4.86-2.11 5.83-2.51 2.78-1.16 3.35-1.36 3.73-1.36.08 0 .27.02.39.12.1.08.13.19.14.27-.01.06.01.24 0 .38z"/>
                                </svg>
                            </div>
                            <h3 class="text-2xl font-bold text-white mb-3 group-hover:text-blue-300 transition-colors">Telegram</h3>
                            <p class="text-blue-200 mb-6 leading-relaxed">{{ t('platform_selection.telegram_description') }}</p>
                            
                            <!-- Features List -->
                            <div class="space-y-2 mb-6">
                                <div class="flex items-center text-green-300 text-sm">
                                    <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                    </svg>
                                    {{ t('platform_selection.telegram_feature_1') }}
                                </div>
                                <div class="flex items-center text-green-300 text-sm">
                                    <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                    </svg>
                                    {{ t('platform_selection.telegram_feature_2') }}
                                </div>
                                <div class="flex items-center text-green-300 text-sm">
                                    <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                    </svg>
                                    {{ t('platform_selection.telegram_feature_3') }}
                                </div>
                            </div>
                            
                            <div class="w-full py-3 bg-gradient-to-r from-blue-500 to-blue-600 rounded-xl font-semibold group-hover:from-blue-400 group-hover:to-blue-500 transition-all">
                                {{ t('platform_selection.connect_with_telegram') }}
                            </div>
                        </div>
                    </Link>

                    <!-- WhatsApp Option -->
                    <Link :href="route('whatsapp.connect')" 
                          class="group bg-slate-900/30 border-2 border-slate-700 hover:border-green-500 rounded-2xl p-8 transition-all duration-300 hover:bg-slate-800/50 hover:scale-105 hover:shadow-2xl hover:shadow-green-500/20">
                        <div class="flex flex-col items-center text-center">
                            <!-- WhatsApp Icon -->
                            <div class="w-16 h-16 bg-gradient-to-r from-green-400 to-green-600 rounded-full flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                                <svg class="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.890-5.335 11.893-11.893A11.821 11.821 0 0020.484 3.085"/>
                                </svg>
                            </div>
                            <h3 class="text-2xl font-bold text-white mb-3 group-hover:text-green-300 transition-colors">WhatsApp</h3>
                            <p class="text-green-200 mb-6 leading-relaxed">{{ t('platform_selection.whatsapp_description') }}</p>
                            
                            <!-- Features List -->
                            <div class="space-y-2 mb-6">
                                <div class="flex items-center text-green-300 text-sm">
                                    <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                    </svg>
                                    {{ t('platform_selection.whatsapp_feature_1') }}
                                </div>
                                <div class="flex items-center text-green-300 text-sm">
                                    <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                    </svg>
                                    {{ t('platform_selection.whatsapp_feature_2') }}
                                </div>
                                <div class="flex items-center text-green-300 text-sm">
                                    <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                    </svg>
                                    {{ t('platform_selection.whatsapp_feature_3') }}
                                </div>
                            </div>
                            
                            <div class="w-full py-3 bg-gradient-to-r from-green-500 to-green-600 rounded-xl font-semibold group-hover:from-green-400 group-hover:to-green-500 transition-all">
                                {{ t('platform_selection.connect_with_whatsapp') }}
                            </div>
                        </div>
                    </Link>
                </div>

                <!-- Skip Section -->
                <div class="text-center">
                    <p class="text-slate-400 mb-4">{{ t('platform_selection.skip_message') }}</p>
                    <Link :href="route('dashboard')" 
                          class="inline-block px-8 py-3 border-2 border-slate-600 rounded-xl font-semibold hover:bg-slate-700 hover:border-slate-500 transition-all">
                        {{ t('platform_selection.skip_button') }}
                    </Link>
                </div>
            </div>
        </div>
    </div>
</template>