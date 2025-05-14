// resources/js/Pages/Telegram/Connect.vue
<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import InputError from '@/Components/InputError.vue';
import { Head } from '@inertiajs/vue3';

const props = defineProps({
    currentTelegramId: String
});

const form = useForm({
    telegram_id: props.currentTelegramId || '',
});

const submit = () => {
    form.post(route('telegram.connect'));
};
</script>

<template>
    <Head title="Conectare Telegram" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold text-gray-800">
                Conectare Cont Telegram
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="mb-6">
                        <h3 class="text-lg font-medium">Pentru a conecta contul Telegram:</h3>
                        <ol class="mt-4 list-decimal list-inside space-y-2">
                            <li>Deschide Telegram și caută <span class="font-medium">@MicroNoteBot</span></li>
                            <li>Trimite mesajul <span class="font-medium">/start</span> către bot</li>
                            <li>Botul îți va răspunde cu ID-ul tău Telegram</li>
                            <li>Copiază ID-ul și introdu-l mai jos</li>
                        </ol>
                    </div>

                    <form @submit.prevent="submit">
                        <div class="mb-4">
                            <InputLabel for="telegram_id" value="ID Telegram" />
                            <TextInput
                                id="telegram_id"
                                type="text"
                                class="mt-1 block w-full"
                                v-model="form.telegram_id"
                                required
                                autofocus
                            />
                            <InputError class="mt-2" :message="form.errors.telegram_id" />
                        </div>

                        <div class="flex items-center gap-4">
                            <PrimaryButton :disabled="form.processing">Conectează</PrimaryButton>
                            <p v-if="form.recentlySuccessful" class="text-sm text-green-600">
                                Cont Telegram conectat cu succes!
                            </p>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
