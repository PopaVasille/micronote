<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import InputError from '@/Components/InputError.vue';

const props = defineProps({
    currentTelegramId: String
});

const form = useForm({
    telegram_id: props.currentTelegramId || '',
});

const submit = () => {
    form.post(route('telegram.store'), {
        // Redirectarea se face din controller, aici doar afișăm starea
    });
};
</script>

<template>
    <Head title="Conectare Telegram" />
    <div class="min-h-screen bg-gradient-to-br from-slate-900 via-blue-900 to-indigo-900 text-white flex flex-col items-center justify-center p-4 sm:p-6">

        <div class="w-full max-w-2xl">
            <div class="bg-slate-800/50 border border-slate-700/50 rounded-2xl shadow-2xl backdrop-blur-lg p-8 sm:p-10">
                <div class="text-center mb-8">
                    <h1 class="text-3xl font-bold text-white">Conectează-ți contul Telegram</h1>
                    <p class="text-blue-200 mt-2">Acesta este pasul final pentru a putea trimite notițe direct din Telegram.</p>
                </div>

                <div class="bg-slate-900/50 rounded-xl p-6 border border-slate-700 space-y-4 mb-8">
                    <h3 class="text-lg font-medium text-white">Cum obții ID-ul de Telegram:</h3>
                    <ol class="list-decimal list-inside space-y-3 text-blue-100">
                        <li>Deschide aplicația Telegram și caută bot-ul <a href="https://t.me/userinfobot" target="_blank" class="font-bold text-white underline hover:text-blue-300 transition-colors">@userinfobot</a>.</li>
                        <li>Apasă pe butonul **START** în conversația cu bot-ul.</li>
                        <li>Bot-ul îți va răspunde imediat cu ID-ul tău numeric.</li>
                        <li>Copiază acel ID și introdu-l în câmpul de mai jos.</li>
                    </ol>
                </div>

                <form @submit.prevent="submit">
                    <div class="mb-6">
                        <label for="telegram_id" class="block text-sm font-medium text-blue-100 mb-2">ID-ul tău de Telegram</label>
                        <input id="telegram_id" type="text" v-model="form.telegram_id" required autofocus
                               class="w-full px-4 py-3 bg-slate-800/60 border-2 border-slate-700 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition text-white placeholder-slate-400" placeholder="Introdu ID-ul numeric aici" />
                        <InputError class="mt-2" :message="form.errors.telegram_id" />
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <Link :href="route('dashboard')" class="w-full text-center px-6 py-3 border-2 border-slate-600 rounded-xl font-semibold hover:bg-slate-700 transition-all flex items-center justify-center">
                            Omitere pentru moment
                        </Link>
                        <button type="submit" :disabled="form.processing"
                                class="w-full group px-6 py-3 bg-gradient-to-r from-blue-500 to-purple-600 rounded-xl font-bold text-lg shadow-lg hover:shadow-blue-500/25 transition-all transform hover:scale-105 flex items-center justify-center disabled:opacity-50">
                            <span v-if="!form.processing">Conectează și Continuă</span>
                            <span v-else>Se conectează...</span>
                        </button>
                    </div>
                     <p v-if="form.recentlySuccessful" class="mt-4 text-center text-sm text-green-400">
                        Cont Telegram conectat cu succes! Vei fi redirecționat...
                    </p>
                </form>
            </div>
        </div>
    </div>
</template>
