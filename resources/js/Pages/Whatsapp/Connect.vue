<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import InputError from '@/Components/InputError.vue';

const { t } = useI18n();

const form = useForm({
    whatsapp_id: '',
});

const submit = () => {
    form.post(route('whatsapp.store'), {
        // Redirect is handled by the controller on success
    });
};
</script>

<template>
    <Head :title="t('connect_page.title_whatsapp')" />
    <div class="min-h-screen bg-gradient-to-br from-slate-900 via-green-900 to-teal-900 text-white flex flex-col items-center justify-center p-4 sm:p-6">

        <div class="w-full max-w-2xl">
            <div class="bg-slate-800/50 border border-slate-700/50 rounded-2xl shadow-2xl backdrop-blur-lg p-8 sm:p-10">
                <div class="text-center mb-8">
                    <h1 class="text-3xl font-bold text-white">{{ t('connect_page.title_whatsapp') }}</h1>
                    <p class="text-green-200 mt-2">{{ t('connect_page.subtitle_whatsapp') }}</p>
                </div>

                <div class="bg-slate-900/50 rounded-xl p-6 border border-slate-700 space-y-4 mb-8">
                    <h3 class="text-lg font-medium text-white">{{ t('connect_page.instructions_title') }}</h3>
                    <ol class="list-decimal list-inside space-y-3 text-green-100">
                        <li>{{ t('connect_page.instructions_whatsapp_step_1') }} <strong class='text-white'>+40 123 456 789</strong> (exemplu)</li>
                        <li>{{ t('connect_page.instructions_whatsapp_step_2') }}</li>
                        <li>{{ t('connect_page.instructions_whatsapp_step_3') }}</li>
                        <li>{{ t('connect_page.instructions_whatsapp_step_4') }}</li>
                    </ol>
                </div>

                <form @submit.prevent="submit">
                    <div class="mb-6">
                        <label for="whatsapp_id" class="block text-sm font-medium text-green-100 mb-2">{{ t('connect_page.form_label_whatsapp') }}</label>
                        <input id="whatsapp_id" type="text" v-model="form.whatsapp_id" required autofocus
                               :placeholder="t('connect_page.form_placeholder')"
                               class="w-full px-4 py-3 bg-slate-800/60 border-2 border-slate-700 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none transition text-white placeholder-slate-400" />
                        <InputError class="mt-2" :message="form.errors.whatsapp_id" />
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <Link :href="route('dashboard')" class="w-full text-center px-6 py-3 border-2 border-slate-600 rounded-xl font-semibold hover:bg-slate-700 transition-all flex items-center justify-center">
                            {{ t('connect_page.button_skip') }}
                        </Link>
                        <button type="submit" :disabled="form.processing"
                                class="w-full group px-6 py-3 bg-gradient-to-r from-green-500 to-teal-600 rounded-xl font-bold text-lg shadow-lg hover:shadow-green-500/25 transition-all transform hover:scale-105 flex items-center justify-center disabled:opacity-50">
                            <span v-if="!form.processing">{{ t('connect_page.button_submit') }}</span>
                            <span v-else>{{ t('connect_page.button_submitting') }}</span>
                        </button>
                    </div>
                     <p v-if="form.recentlySuccessful" class="mt-4 text-center text-sm text-green-400">
                         {{ t('connect_page.success_message') }}
                    </p>
                </form>
            </div>
        </div>
    </div>
</template>
