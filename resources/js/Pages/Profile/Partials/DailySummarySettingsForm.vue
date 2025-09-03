<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { onMounted, ref } from 'vue';

const { t } = useI18n();

const props = defineProps({
    user: {
        type: Object,
        required: true,
    },
});

const form = useForm({
    daily_summary_enabled: props.user.daily_summary_enabled || false,
    daily_summary_time: props.user.daily_summary_time || '08:00',
    daily_summary_timezone: props.user.daily_summary_timezone || 'Europe/Bucharest',
});

const timezones = ref([
    { value: 'Europe/Bucharest', label: 'București (GMT+2)' },
    { value: 'Europe/London', label: 'London (GMT+0)' },
    { value: 'Europe/Paris', label: 'Paris (GMT+1)' },
    { value: 'Europe/Berlin', label: 'Berlin (GMT+1)' },
    { value: 'Europe/Madrid', label: 'Madrid (GMT+1)' },
    { value: 'Europe/Rome', label: 'Roma (GMT+1)' },
    { value: 'America/New_York', label: 'New York (GMT-5)' },
    { value: 'America/Los_Angeles', label: 'Los Angeles (GMT-8)' },
    { value: 'America/Chicago', label: 'Chicago (GMT-6)' },
    { value: 'Asia/Tokyo', label: 'Tokyo (GMT+9)' },
    { value: 'Asia/Shanghai', label: 'Shanghai (GMT+8)' },
    { value: 'Australia/Sydney', label: 'Sydney (GMT+11)' },
]);

const timeOptions = ref([]);

// Generate time options (every 30 minutes)
onMounted(() => {
    for (let hour = 6; hour < 22; hour++) {
        for (let minute = 0; minute < 60; minute += 30) {
            const time = `${hour.toString().padStart(2, '0')}:${minute.toString().padStart(2, '0')}`;
            let label = time;
            
            // Add friendly labels for common times
            if (time === '08:00') label += ' (Dimineața)';
            else if (time === '07:00') label += ' (Devreme)';
            else if (time === '09:00') label += ' (Târziu dimineața)';
            else if (time === '21:00') label += ' (Seara)';
            
            timeOptions.value.push({ value: time, label });
        }
    }
});

const submit = () => {
    form.patch(route('profile.daily-summary.update'), {
        preserveScroll: true,
        onSuccess: () => {
            // Show success message or handle success
        },
    });
};

const hasMessagingPlatform = () => {
    return props.user.telegram_id || props.user.whatsapp_id || props.user.whatsapp_phone;
};
</script>

<template>
    <section>
        <header class="mb-6">
            <div class="flex items-center mb-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-green-600 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <h2 class="text-lg font-medium text-gray-900">
                    {{ t('profile.daily_summary.title', 'Rezumat Zilnic') }}
                </h2>
            </div>
            <p class="text-sm text-gray-600">
                {{ t('profile.daily_summary.description', 'Primește în fiecare dimineață un rezumat cu task-urile, evenimentele și memento-urile tale pentru ziua curentă.') }}
            </p>
        </header>

        <!-- Warning if no messaging platform connected -->
        <div v-if="!hasMessagingPlatform()" class="mb-6 p-4 bg-amber-50 border border-amber-200 rounded-lg">
            <div class="flex items-start">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-amber-400 mr-2 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L12.732 4c-.77-.833-1.964-.833-2.732 0L3.362 16.5c-.77.833.192 2.5 1.732 2.5z" />
                </svg>
                <div>
                    <h3 class="text-sm font-medium text-amber-800">
                        {{ t('profile.daily_summary.no_platform_title', 'Conectează o platformă de mesagerie') }}
                    </h3>
                    <p class="mt-1 text-sm text-amber-700">
                        {{ t('profile.daily_summary.no_platform_message', 'Pentru a primi rezumate zilnice, trebuie să conectezi mai întâi Telegram sau WhatsApp în secțiunea "Platforme de mesagerie".') }}
                    </p>
                </div>
            </div>
        </div>

        <form @submit.prevent="submit" class="space-y-6">
            <!-- Enable Daily Summary Toggle -->
            <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                <div>
                    <label class="text-sm font-medium text-gray-900">
                        {{ t('profile.daily_summary.enable_label', 'Activează rezumatul zilnic') }}
                    </label>
                    <p class="text-sm text-gray-500 mt-1">
                        {{ t('profile.daily_summary.enable_help', 'Vei primi un mesaj în fiecare dimineață cu programul zilei') }}
                    </p>
                </div>
                <div class="relative">
                    <input
                        id="daily_summary_enabled"
                        v-model="form.daily_summary_enabled"
                        type="checkbox"
                        :disabled="!hasMessagingPlatform()"
                        class="sr-only"
                    />
                    <label
                        for="daily_summary_enabled"
                        class="flex items-center cursor-pointer"
                        :class="{ 'opacity-50 cursor-not-allowed': !hasMessagingPlatform() }"
                    >
                        <div class="relative">
                            <div
                                class="block w-14 h-8 rounded-full transition-colors"
                                :class="form.daily_summary_enabled ? 'bg-green-500' : 'bg-gray-300'"
                            ></div>
                            <div
                                class="absolute left-1 top-1 bg-white w-6 h-6 rounded-full transition-transform"
                                :class="form.daily_summary_enabled ? 'transform translate-x-6' : ''"
                            ></div>
                        </div>
                    </label>
                </div>
            </div>

            <!-- Time Settings (only shown when enabled) -->
            <div v-if="form.daily_summary_enabled" class="space-y-4">
                <!-- Time Selection -->
                <div>
                    <InputLabel for="daily_summary_time" :value="t('profile.daily_summary.time_label', 'Ora de trimitere')" />
                    <select
                        id="daily_summary_time"
                        v-model="form.daily_summary_time"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500"
                        required
                    >
                        <option v-for="time in timeOptions" :key="time.value" :value="time.value">
                            {{ time.label }}
                        </option>
                    </select>
                    <InputError class="mt-2" :message="form.errors.daily_summary_time" />
                    <p class="mt-1 text-xs text-gray-500">
                        {{ t('profile.daily_summary.time_help', 'Alege ora la care vrei să primești rezumatul zilnic') }}
                    </p>
                </div>

                <!-- Timezone Selection -->
                <div>
                    <InputLabel for="daily_summary_timezone" :value="t('profile.daily_summary.timezone_label', 'Fusul orar')" />
                    <select
                        id="daily_summary_timezone"
                        v-model="form.daily_summary_timezone"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500"
                        required
                    >
                        <option v-for="timezone in timezones" :key="timezone.value" :value="timezone.value">
                            {{ timezone.label }}
                        </option>
                    </select>
                    <InputError class="mt-2" :message="form.errors.daily_summary_timezone" />
                    <p class="mt-1 text-xs text-gray-500">
                        {{ t('profile.daily_summary.timezone_help', 'Selectează fusul orar pentru ora corectă de livrare') }}
                    </p>
                </div>

                <!-- Preview Section -->
                <div class="p-4 bg-blue-50 border border-blue-200 rounded-lg">
                    <div class="flex items-start">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-400 mr-2 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <div>
                            <h3 class="text-sm font-medium text-blue-800">
                                {{ t('profile.daily_summary.preview_title', 'Exemplu de rezumat zilnic') }}
                            </h3>
                            <div class="mt-2 p-3 bg-white rounded border text-sm">
                                <div class="font-medium">🌅 Bună dimineața, {{ user.name }}!</div>
                                <div class="text-gray-600 mb-2">📅 Rezumatul pentru luni, 03.01.2025</div>
                                <div class="space-y-2">
                                    <div>
                                        <div class="font-medium">✅ <strong>Task-uri pentru azi</strong> (2)</div>
                                        <div class="ml-4 text-gray-700">
                                            <div>🔥 Finalizează prezentarea (14:00)</div>
                                            <div>▫️ Verifică email-urile</div>
                                        </div>
                                    </div>
                                    <div>
                                        <div class="font-medium">📅 <strong>Evenimente</strong> (1)</div>
                                        <div class="ml-4 text-gray-700">🔸 Ședință echipă (10:30)</div>
                                    </div>
                                </div>
                                <div class="mt-2 text-xs text-gray-500 italic">
                                    📱 Pentru a gestiona notițele, accesează dashboard-ul web sau trimite-mi un mesaj!
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Save Button -->
            <div class="flex items-center gap-4">
                <PrimaryButton :disabled="form.processing">
                    {{ t('common.save', 'Salvează') }}
                </PrimaryButton>

                <Transition
                    enter-active-class="transition ease-in-out"
                    enter-from-class="opacity-0"
                    leave-active-class="transition ease-in-out"
                    leave-to-class="opacity-0"
                >
                    <p v-if="form.recentlySuccessful" class="text-sm text-gray-600">
                        {{ t('common.saved', 'Salvat.') }}
                    </p>
                </Transition>
            </div>

            <!-- Test Button (only when enabled) -->
            <div v-if="form.daily_summary_enabled && hasMessagingPlatform()" class="pt-4 border-t border-gray-200">
                <button
                    type="button"
                    @click="$inertia.post(route('profile.daily-summary.test'), {}, { preserveScroll: true })"
                    class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                    </svg>
                    {{ t('profile.daily_summary.test_button', 'Trimite test') }}
                </button>
                <p class="mt-2 text-xs text-gray-500">
                    {{ t('profile.daily_summary.test_help', 'Trimite un exemplu de rezumat zilnic pentru a vedea cum arată') }}
                </p>
            </div>
        </form>
    </section>
</template>