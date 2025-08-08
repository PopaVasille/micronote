<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import InputError from '@/Components/InputError.vue';
import Checkbox from '@/Components/Checkbox.vue';
import LanguageSwitcher from '@/Components/LanguageSwitcher.vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

defineProps({
    canResetPassword: Boolean,
    status: String,
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <Head :title="t('common.login')" />
    <div class="min-h-screen bg-gradient-to-br from-slate-900 via-blue-900 to-indigo-900 text-white flex flex-col items-center justify-center p-4 sm:p-6">

        <!-- Header with Language Switcher -->
        <div class="w-full max-w-md mb-4 flex justify-between items-center">
            <Link href="/" class="flex items-center text-blue-300 hover:text-white transition-colors group">
                <svg class="w-5 h-5 mr-2 transform group-hover:-translate-x-1 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
                {{ t('auth.back_to_home') }}
            </Link>
            
            <LanguageSwitcher variant="dark" />
        </div>

        <div class="w-full max-w-md bg-slate-800/50 border border-slate-700/50 rounded-2xl shadow-2xl backdrop-blur-lg">
            <div class="p-8 sm:p-10">
                <div class="text-center mb-8">
                    <Link href="/" class="inline-block mb-4">
                        <div class="w-16 h-16 bg-gradient-to-r from-blue-400 to-purple-500 rounded-2xl flex items-center justify-center mx-auto shadow-lg">
                            <span class="text-white font-bold text-4xl">M</span>
                        </div>
                    </Link>
                    <h1 class="text-3xl font-bold text-white">{{ t('auth.login_title') }}</h1>
                    <p class="text-blue-200 mt-2">{{ t('auth.login_subtitle') }}</p>
                </div>

                <div v-if="status" class="mb-4 text-sm font-medium text-green-400 bg-green-500/20 p-3 rounded-lg">
                    {{ status }}
                </div>

                <form @submit.prevent="submit" class="space-y-6">
                    <div>
                        <label for="email" class="block text-sm font-medium text-blue-100 mb-2">{{ t('auth.email') }}</label>
                        <input id="email" type="email" v-model="form.email" required autofocus autocomplete="username"
                               class="w-full px-4 py-3 bg-slate-800/60 border-2 border-slate-700 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition text-white placeholder-slate-400" :placeholder="t('auth.email_placeholder')" />
                        <InputError class="mt-2" :message="form.errors.email" />
                    </div>

                    <div>
                        <div class="flex justify-between items-center mb-2">
                            <label for="password" class="block text-sm font-medium text-blue-100">{{ t('auth.password') }}</label>
                            <Link v-if="canResetPassword" :href="route('password.request')" class="text-sm text-blue-300 hover:text-white hover:underline">
                                {{ t('auth.forgot_password') }}
                            </Link>
                        </div>
                        <input id="password" type="password" v-model="form.password" required autocomplete="current-password"
                               class="w-full px-4 py-3 bg-slate-800/60 border-2 border-slate-700 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition text-white" :placeholder="t('auth.password_placeholder')" />
                        <InputError class="mt-2" :message="form.errors.password" />
                    </div>

                    <div class="block">
                        <label class="flex items-center">
                            <Checkbox name="remember" v-model:checked="form.remember" />
                            <span class="ms-2 text-sm text-blue-200">{{ t('auth.remember_me') }}</span>
                        </label>
                    </div>

                    <div class="!mt-8">
                        <button type="submit" :disabled="form.processing"
                                class="w-full group px-8 py-4 bg-gradient-to-r from-blue-500 to-purple-600 rounded-xl font-bold text-lg shadow-2xl hover:shadow-blue-500/25 transition-all transform hover:scale-105 flex items-center justify-center disabled:opacity-50 disabled:scale-100">
                            <span>{{ t('auth.login_button') }}</span>
                        </button>
                    </div>

                    <div class="text-center mt-6">
                        <Link :href="route('register')" class="text-sm text-blue-300 hover:text-white hover:underline transition-colors">
                            {{ t('auth.no_account') }}
                        </Link>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>