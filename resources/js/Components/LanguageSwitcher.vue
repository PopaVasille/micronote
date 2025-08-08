<template>
    <div class="relative">
        <button @click="toggleDropdown" :class="buttonClasses">
            <span>{{ currentLocale.toUpperCase() }}</span>
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
        </button>

        <div v-if="isOpen" v-on-click-outside="closeDropdown" :class="dropdownClasses">
            <a href="#" @click.prevent="switchLanguage('en')" :class="itemClasses">EN</a>
            <a href="#" @click.prevent="switchLanguage('ro')" :class="itemClasses">RO</a>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { vOnClickOutside } from '@vueuse/components';

const props = defineProps({
    variant: {
        type: String,
        default: 'dark', // 'dark' or 'light'
    },
});

const i18n = useI18n();
const page = usePage();

const isOpen = ref(false);

const currentLocale = computed(() => i18n.locale.value);

// Dynamic classes based on variant
const buttonClasses = computed(() => {
    const baseClasses = 'flex items-center space-x-1 text-sm font-medium transition-colors';
    if (props.variant === 'light') {
        return `${baseClasses} text-gray-700 hover:text-gray-900 bg-gray-100 hover:bg-gray-200 px-3 py-2 rounded-lg border border-gray-200`;
    } else {
        return `${baseClasses} text-gray-300 hover:text-white`;
    }
});

const dropdownClasses = computed(() => {
    const baseClasses = 'absolute right-0 mt-2 w-24 rounded-md shadow-lg z-50 border';
    if (props.variant === 'light') {
        return `${baseClasses} bg-white border-gray-200`;
    } else {
        return `${baseClasses} bg-slate-800 border-slate-700`;
    }
});

const itemClasses = computed(() => {
    const baseClasses = 'block px-4 py-2 text-sm transition-colors';
    if (props.variant === 'light') {
        return `${baseClasses} text-gray-700 hover:bg-gray-100`;
    } else {
        return `${baseClasses} text-gray-300 hover:bg-slate-700`;
    }
});

const form = useForm({
    locale: currentLocale.value,
});

const toggleDropdown = () => {
    isOpen.value = !isOpen.value;
};

const closeDropdown = () => {
    isOpen.value = false;
};

const switchLanguage = (locale) => {
    form.locale = locale;
    form.post(route('language.switch'), {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
            // Update Vue i18n locale immediately
            i18n.locale.value = locale;
            closeDropdown();
            
            // Store the locale in localStorage for persistence
            localStorage.setItem('selected_locale', locale);
        },
    });
};
</script>