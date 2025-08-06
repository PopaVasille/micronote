<script setup>
import { ref, onMounted } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    canLogin: Boolean,
    canRegister: Boolean,
    laravelVersion: String,
    phpVersion: String,
});

const form = useForm({
    email: '',
});

const submit = () => {
    form.post(route('early-access.store'), {
        onFinish: () => form.reset('email'),
    });
};

const isVisible = ref(false);
const activeFeature = ref(0);

onMounted(() => {
    isVisible.value = true;
    setInterval(() => {
        activeFeature.value = (activeFeature.value + 1) % 3;
    }, 3000);
});

const features = [
    {
        icon: `<svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" /></svg>`,
        title: "Integrare Telegram",
        description: "Trimite orice mesaj către botul nostru. Zero aplicații noi, zero complicații. Folosești Telegram-ul ca de obicei.",
        gradient: "from-blue-500 to-cyan-500"
    },
    {
        icon: `<svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" /></svg>`,
        title: "Sortare Inteligentă AI",
        description: "Inteligența artificială clasifică automat mesajele în task-uri, idei, cumpărături și memento-uri. Magia se întâmplă în fundal.",
        gradient: "from-purple-500 to-pink-500"
    },
    {
        icon: `<svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>`,
        title: "Memento-uri Inteligente",
        description: "Scrie 'amintește-mi mâine la 10' și primești notificarea exact când ai nevoie. Fără configurări, fără setări.",
        gradient: "from-green-500 to-teal-500"
    }
];

const pricingFeatures = {
    free: [
        "200 notițe pe lună",
        "Organizare automată de bază",
        "Dashboard web complet",
        "Liste de cumpărături",
        "10 tag-uri personale",
        "Export CSV"
    ],
    plus: [
        "Notițe nelimitate",
        "AI clasificare avansată (30/lună)",
        "Memento-uri inteligente (15/lună)",
        "Export PDF elegant",
        "Tag-uri nelimitate",
        "Căutare avansată",
        "Statistici detaliate"
    ]
};
</script>

<template>
    <Head title="Bun venit la MicroNote" />
    <div class="min-h-screen bg-gradient-to-br from-slate-900 via-blue-900 to-indigo-900 text-white overflow-hidden">
        <!-- Header -->
        <header class="relative z-50 px-6 py-4">
            <nav class="max-w-7xl mx-auto flex justify-between items-center">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-gradient-to-r from-blue-400 to-purple-500 rounded-xl flex items-center justify-center">
                        <span class="text-white font-bold text-xl">M</span>
                    </div>
                    <span class="text-2xl font-bold">MicroNote</span>
                </div>

                <div class="hidden md:flex space-x-8">
                    <a href="#features" class="hover:text-blue-300 transition-colors">Funcții</a>
                    <a href="#pricing" class="hover:text-blue-300 transition-colors">Prețuri</a>
                    <a href="#demo" class="hover:text-blue-300 transition-colors">Demo</a>
                </div>

                <div class="flex space-x-3">
                    <Link :href="route('login')" class="px-4 py-2 text-blue-300 hover:text-white transition-colors">
                        Intră în cont
                    </Link>
                    <Link :href="route('register')" class="px-6 py-2 bg-gradient-to-r from-blue-500 to-purple-600 rounded-lg hover:from-blue-600 hover:to-purple-700 transition-all font-semibold shadow-lg hover:shadow-xl transform hover:scale-105">
                        Începe acum
                    </Link>
                </div>
            </nav>
        </header>

        <!-- Hero Section -->
        <section class="relative px-6 py-20">
            <div class="max-w-6xl mx-auto">
                <div class="grid lg:grid-cols-2 gap-12 items-center">
                    <div :class="['space-y-8 transition-all duration-1000', isVisible ? 'opacity-100 translate-x-0' : 'opacity-0 -translate-x-10']">
                        <div class="space-y-6">
                            <div class="inline-flex items-center space-x-2 bg-blue-500/20 rounded-full px-4 py-2 text-sm font-medium border border-blue-400/30">
                                <div class="w-2 h-2 bg-green-400 rounded-full animate-pulse"></div>
                                <span>Aplicația ta preferată pentru notițe</span>
                            </div>

                            <h1 class="text-5xl lg:text-7xl font-extrabold bg-gradient-to-r from-white via-blue-100 to-purple-200 bg-clip-text text-transparent leading-tight">
                                Notițele tale,<br />
                                <span class="bg-gradient-to-r from-blue-400 to-purple-500 bg-clip-text text-transparent">
                                    organizate instant
                                </span>
                            </h1>

                            <p class="text-xl text-blue-100 max-w-2xl leading-relaxed">
                                Trimite o idee pe Telegram și MicroNote o organizează automat.
                                <strong class="text-white"> Fără aplicații noi, fără complicații.</strong>
                            </p>
                        </div>

                        <form @submit.prevent="submit" class="flex flex-col sm:flex-row gap-4">
                            <input v-model="form.email" type="email" placeholder="Adresa ta de email" class="w-full sm:w-auto flex-grow px-4 py-3 bg-slate-800/50 border-2 border-slate-700 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition text-white" required />
                            <button type="submit" :disabled="form.processing" class="group px-8 py-4 bg-gradient-to-r from-blue-500 to-purple-600 rounded-xl font-bold text-lg shadow-2xl hover:shadow-blue-500/25 transition-all transform hover:scale-105 flex items-center justify-center">
                                <span>Vreau acces</span>
                                <svg class="ml-2 w-5 h-5 group-hover:translate-x-1 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                                </svg>
                            </button>
                        </form>
                         <div v-if="$page.props.flash.success" class="mt-4 text-green-400 font-semibold">
                            {{ $page.props.flash.success }}
                        </div>
                        <div v-if="form.errors.email" class="mt-2 text-red-400 text-sm">
                            {{ form.errors.email }}
                        </div>


                        <div class="flex items-center space-x-8 pt-4">
                            <div class="flex items-center space-x-2">
                                <div class="flex -space-x-2">
                                    <div v-for="i in 3" :key="i" class="w-8 h-8 bg-gradient-to-r from-blue-400 to-purple-500 rounded-full border-2 border-slate-900 flex items-center justify-center text-xs font-bold">
                                        {{ String.fromCharCode(64 + i) }}
                                    </div>
                                </div>
                                <span class="text-blue-200 text-sm">100+ utilizatori activi</span>
                            </div>
                            <div class="flex items-center space-x-1 text-yellow-400">
                                <svg v-for="i in 5" :key="i" class="w-4 h-4 fill-current" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                </svg>
                                <span class="text-blue-200 text-sm ml-2">5.0 stele</span>
                            </div>
                        </div>
                    </div>

                    <!-- Demo Visual -->
                    <div :class="['relative transition-all duration-1000 delay-300', isVisible ? 'opacity-100 translate-x-0' : 'opacity-0 translate-x-10']">
                        <div class="relative">
                            <!-- Phone mockup -->
                            <div class="bg-gradient-to-b from-slate-800 to-slate-900 rounded-3xl p-2 shadow-2xl border border-slate-700">
                                <div class="bg-black rounded-2xl p-6 space-y-4">
                                    <div class="flex items-center space-x-3 bg-blue-600 rounded-lg p-3">
                                        <div class="w-10 h-10 bg-blue-500 rounded-full flex items-center justify-center">
                                            <span class="text-white font-bold">M</span>
                                        </div>
                                        <div>
                                            <div class="text-white font-semibold">MicroNote Bot</div>
                                            <div class="text-blue-200 text-sm">online</div>
                                        </div>
                                    </div>

                                    <div class="space-y-3">
                                        <div class="flex justify-end">
                                            <div class="bg-blue-600 text-white px-4 py-2 rounded-2xl rounded-tr-md max-w-xs">
                                                Idee: Aplicație pentru organizarea notițelor prin Telegram 🚀
                                            </div>
                                        </div>

                                        <div class="flex justify-start">
                                            <div class="bg-slate-700 text-white px-4 py-2 rounded-2xl rounded-tl-md max-w-xs">
                                                ✅ Salvat ca <strong>Idee</strong><br />
                                                📝 Organizat automat<br />
                                                🔔 Reminder setat
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Floating elements -->
                            <div class="absolute -top-4 -right-4 bg-green-500 rounded-lg p-3 shadow-lg animate-bounce">
                                <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                            </div>

                            <div class="absolute -bottom-6 -left-6 bg-purple-500 rounded-lg p-3 shadow-lg animate-pulse">
                                <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Features Section -->
        <section id="features" class="py-20 px-6">
            <div class="max-w-7xl mx-auto">
                <div class="text-center mb-16">
                    <h2 class="text-4xl lg:text-5xl font-bold mb-6">
                        Totul <span class="bg-gradient-to-r from-blue-400 to-purple-500 bg-clip-text text-transparent">automat</span>
                    </h2>
                    <p class="text-xl text-blue-100 max-w-3xl mx-auto">
                        MicroNote transformă mesajele tale obișnuite în notițe organizate, fără să schimbi nimic din rutina ta zilnică.
                    </p>
                </div>

                <div class="grid lg:grid-cols-3 gap-8 mb-16">
                    <div v-for="(feature, index) in features" :key="index"
                        :class="['group relative p-8 rounded-2xl border border-slate-700/50 hover:border-slate-600/50 transition-all duration-300 transform hover:-translate-y-2', activeFeature === index ? 'bg-gradient-to-br from-slate-800/50 to-slate-900/50' : 'bg-slate-800/30']">
                        <div :class="['inline-flex p-3 rounded-xl bg-gradient-to-r mb-6 group-hover:scale-110 transition-transform', feature.gradient]" v-html="feature.icon"></div>
                        <h3 class="text-2xl font-bold mb-4 text-white">{{ feature.title }}</h3>
                        <p class="text-blue-100 leading-relaxed">{{ feature.description }}</p>
                        <div class="absolute inset-0 bg-gradient-to-r from-blue-500/10 to-purple-500/10 rounded-2xl opacity-0 group-hover:opacity-100 transition-opacity -z-10"></div>
                    </div>
                </div>

                <!-- Interactive Demo -->
                <div id="demo" class="bg-gradient-to-r from-slate-800/50 to-slate-900/50 rounded-3xl p-8 border border-slate-700/50">
                    <div class="text-center mb-8">
                        <h3 class="text-3xl font-bold mb-4">Vezi cum funcționează</h3>
                        <p class="text-blue-100">Exemplu real de utilizare</p>
                    </div>

                    <div class="grid md:grid-cols-2 gap-8 items-center">
                        <div class="space-y-4">
                            <div class="bg-slate-700 rounded-lg p-4">
                                <div class="text-sm text-blue-300 mb-2">Tu scrii în Telegram:</div>
                                <div class="bg-blue-600 text-white p-3 rounded-lg">
                                    "Lista de cumpărături: lapte, pâine, ouă, amintește-mi să iau și fructe"
                                </div>
                            </div>

                            <div class="flex justify-center">
                                <div class="w-8 h-8 bg-gradient-to-r from-blue-500 to-purple-500 rounded-full flex items-center justify-center">
                                    <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
                                    </svg>
                                </div>
                            </div>

                            <div class="bg-green-700 rounded-lg p-4">
                                <div class="text-sm text-green-300 mb-2">MicroNote organizează:</div>
                                <div class="space-y-2">
                                    <div class="bg-green-600 text-white p-2 rounded text-sm">✅ Cumpărături: lapte, pâine, ouă, fructe</div>
                                    <div class="bg-orange-600 text-white p-2 rounded text-sm">🔔 Reminder setat pentru cumpărături</div>
                                </div>
                            </div>
                        </div>

                        <div class="bg-slate-900 rounded-2xl p-6">
                            <div class="text-center mb-4">
                                <div class="text-lg font-semibold text-white">Dashboard MicroNote</div>
                            </div>
                            <div class="space-y-3">
                                <div class="bg-green-600/20 border border-green-500/30 rounded-lg p-3">
                                    <div class="flex items-center space-x-2">
                                        <div class="w-3 h-3 bg-green-500 rounded-full"></div>
                                        <span class="text-white font-medium">Cumpărături</span>
                                    </div>
                                    <div class="text-sm text-green-100 mt-1">lapte, pâine, ouă, fructe</div>
                                </div>
                                <div class="bg-orange-600/20 border border-orange-500/30 rounded-lg p-3">
                                    <div class="flex items-center space-x-2">
                                        <div class="w-3 h-3 bg-orange-500 rounded-full"></div>
                                        <span class="text-white font-medium">Reminder</span>
                                    </div>
                                    <div class="text-sm text-orange-100 mt-1">Cumpărături - Azi, 18:00</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Pricing Section -->
        <section id="pricing" class="py-20 px-6">
            <div class="max-w-7xl mx-auto">
                <div class="text-center mb-16">
                    <h2 class="text-4xl lg:text-5xl font-bold mb-6">
                        Începe <span class="bg-gradient-to-r from-blue-400 to-purple-500 bg-clip-text text-transparent">gratuit</span>
                    </h2>
                    <p class="text-xl text-blue-100">Funcționalitățile de bază sunt gratuite pentru totdeauna</p>
                </div>

                <div class="grid md:grid-cols-2 gap-8 max-w-5xl mx-auto">
                    <!-- Free Plan -->
                    <div class="bg-slate-800/50 rounded-3xl p-8 border border-slate-700/50">
                        <div class="text-center mb-8">
                            <h3 class="text-2xl font-bold text-white mb-2">Free</h3>
                            <div class="text-5xl font-bold text-white mb-4">0€</div>
                            <p class="text-blue-100">Perfect pentru începători</p>
                        </div>

                        <ul class="space-y-4 mb-8">
                            <li v-for="(feature, index) in pricingFeatures.free" :key="index" class="flex items-center space-x-3">
                                <div class="w-5 h-5 bg-green-500 rounded-full flex items-center justify-center flex-shrink-0">
                                    <svg class="w-3 h-3 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                                    </svg>
                                </div>
                                <span class="text-blue-100">{{ feature }}</span>
                            </li>
                        </ul>

                        <Link :href="route('register')" class="w-full block text-center py-4 border-2 border-blue-500 text-blue-400 rounded-xl font-semibold hover:bg-blue-500 hover:text-white transition-all">
                            Începe gratuit
                        </Link>
                    </div>

                    <!-- Plus Plan -->
                    <div class="relative bg-gradient-to-br from-blue-600/20 to-purple-600/20 rounded-3xl p-8 border-2 border-blue-500/50">
                        <div class="absolute -top-4 left-1/2 transform -translate-x-1/2">
                            <div class="bg-gradient-to-r from-blue-500 to-purple-600 text-white px-6 py-2 rounded-full text-sm font-bold">
                                RECOMANDAT
                            </div>
                        </div>

                        <div class="text-center mb-8">
                            <h3 class="text-2xl font-bold text-white mb-2">Plus</h3>
                            <div class="text-5xl font-bold text-white mb-2">
                                2€
                                <span class="text-lg text-blue-200">/lună</span>
                            </div>
                            <p class="text-blue-100">Pentru utilizatori activi</p>
                        </div>

                        <ul class="space-y-4 mb-8">
                            <li v-for="(feature, index) in pricingFeatures.plus" :key="index" class="flex items-center space-x-3">
                                <div class="w-5 h-5 bg-gradient-to-r from-blue-400 to-purple-500 rounded-full flex items-center justify-center flex-shrink-0">
                                    <svg class="w-3 h-3 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                                    </svg>
                                </div>
                                <span class="text-white">{{ feature }}</span>
                            </li>
                        </ul>

                        <Link :href="route('register')" class="w-full block text-center py-4 bg-gradient-to-r from-blue-500 to-purple-600 text-white rounded-xl font-bold hover:from-blue-600 hover:to-purple-700 transition-all shadow-lg hover:shadow-xl transform hover:scale-105">
                            Începe cu Plus
                        </Link>
                    </div>
                </div>

                <div class="text-center mt-12">
                    <p class="text-blue-200">
                        💡 <strong>Protip:</strong> Începe cu planul Free și fă upgrade oricând dorești.
                        Fără contracte, fără obligații.
                    </p>
                </div>
            </div>
        </section>

        <!-- CTA Section -->
        <section class="py-20 px-6">
            <div class="max-w-4xl mx-auto text-center">
                <div class="bg-gradient-to-r from-slate-800/50 to-slate-900/50 rounded-3xl p-12 border border-slate-700/50">
                    <h2 class="text-4xl lg:text-5xl font-bold mb-6">
                        Gata să îți <span class="bg-gradient-to-r from-blue-400 to-purple-500 bg-clip-text text-transparent">organizezi viața</span>?
                    </h2>
                    <p class="text-xl text-blue-100 mb-8 max-w-2xl mx-auto">
                        Alătură-te celor peste 100 de utilizatori care și-au simplificat deja organizarea notițelor.
                        <strong class="text-white">Primul pas este gratuit</strong>.
                    </p>

                    <div class="flex flex-col sm:flex-row gap-4 justify-center mb-8">
                        <Link :href="route('register')" class="group px-8 py-4 bg-gradient-to-r from-blue-500 to-purple-600 rounded-xl font-bold text-lg shadow-2xl hover:shadow-blue-500/25 transition-all transform hover:scale-105 flex items-center justify-center">
                            <span>Creează cont gratuit</span>
                            <svg class="ml-2 w-5 h-5 group-hover:translate-x-1 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                            </svg>
                        </Link>

                        <button class="px-8 py-4 border-2 border-slate-600 rounded-xl font-semibold hover:bg-slate-700 transition-all flex items-center justify-center">
                            <svg class="mr-2 w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Întrebări frecvente
                        </button>
                    </div>

                    <div class="flex items-center justify-center space-x-6 text-sm text-blue-300">
                        <div class="flex items-center space-x-2">
                            <svg class="w-4 h-4 text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            <span>Fără card de credit</span>
                        </div>
                        <div class="flex items-center space-x-2">
                            <svg class="w-4 h-4 text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            <span>Setup în 30 secunde</span>
                        </div>
                        <div class="flex items-center space-x-2">
                            <svg class="w-4 h-4 text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            <span>Anulezi oricând</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer class="py-12 px-6 border-t border-slate-700/50">
            <div class="max-w-7xl mx-auto">
                <div class="flex flex-col md:flex-row justify-between items-center">
                    <div class="flex items-center space-x-3 mb-4 md:mb-0">
                        <div class="w-10 h-10 bg-gradient-to-r from-blue-400 to-purple-500 rounded-xl flex items-center justify-center">
                            <span class="text-white font-bold text-xl">M</span>
                        </div>
                        <div>
                            <div class="text-xl font-bold text-white">MicroNote</div>
                            <div class="text-sm text-blue-300">Notițele tale, organizate instant</div>
                        </div>
                    </div>

                    <div class="flex flex-col md:flex-row items-center space-y-4 md:space-y-0 md:space-x-8">
                        <div class="flex space-x-6 text-sm text-blue-300">
                            <a href="#" class="hover:text-white transition-colors">Termeni</a>
                            <a href="#" class="hover:text-white transition-colors">Confidențialitate</a>
                            <a href="#" class="hover:text-white transition-colors">Contact</a>
                        </div>

                        <div class="flex items-center space-x-4">
                            <a href="#" class="text-blue-300 hover:text-white transition-colors">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M8.29 20.251c7.547 0 11.675-6.253 11.675-11.675 0-.178 0-.355-.012-.53A8.348 8.348 0 0022 5.92a8.19 8.19 0 01-2.357.646 4.118 4.118 0 001.804-2.27 8.224 8.224 0 01-2.605.996 4.107 4.107 0 00-6.993 3.743 11.65 11.65 0 01-8.457-4.287 4.106 4.106 0 001.27 5.477A4.072 4.072 0 012.8 9.713v.052a4.105 4.105 0 003.292 4.022 4.095 4.095 0 01-1.853.07 4.108 4.108 0 003.834 2.85A8.233 8.233 0 012 18.407a11.616 11.616 0 006.29 1.84"></path>
                                </svg>
                            </a>
                            <a href="#" class="text-blue-300 hover:text-white transition-colors">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                    <path fill-rule="evenodd" d="M12.017 0C5.396 0 .029 5.367.029 11.987c0 5.079 3.158 9.417 7.618 11.174-.105-.949-.199-2.403.042-3.441.219-.937 1.404-5.965 1.404-5.965s-.359-.719-.359-1.782c0-1.668.967-2.914 2.171-2.914 1.023 0 1.518.769 1.518 1.69 0 1.029-.655 2.568-.994 3.995-.283 1.194.599 2.169 1.777 2.169 2.133 0 3.772-2.249 3.772-5.495 0-2.873-2.064-4.882-5.012-4.882-3.414 0-5.418 2.561-5.418 5.207 0 1.031.397 2.138.893 2.738a.36.36 0 01.083.345l-.333 1.36c-.053.22-.174.267-.402.161-1.499-.698-2.436-2.889-2.436-4.649 0-3.785 2.75-7.262 7.929-7.262 4.163 0 7.398 2.967 7.398 6.931 0 4.136-2.607 7.464-6.227 7.464-1.216 0-2.357-.631-2.75-1.378l-.748 2.853c-.271 1.043-1.002 2.35-1.492 3.146C9.57 23.812 10.763 24.009 12.017 24.009c6.624 0 11.99-5.367 11.99-11.988C24.007 5.367 18.641.001.012.017 0z" clip-rule="evenodd"></path>
                                </svg>
                            </a>
                            <a href="#" class="text-blue-300 hover:text-white transition-colors">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                    <path fill-rule="evenodd" d="M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.531 1.032 1.531 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0112 6.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.202 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.943.359.309.678.92.678 1.855 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0022 12.017C22 6.484 17.522 2 12 2z" clip-rule="evenodd"></path>
                                </svg>
                            </a>
                        </div>
                    </div>
                </div>

                <div class="mt-8 pt-8 border-t border-slate-700/50 text-center text-sm text-blue-300">
                    <p>&copy; {{ new Date().getFullYear() }} MicroNote. Toate drepturile rezervate. Construit cu ❤️ pentru organizarea perfectă.</p>
                </div>
            </div>
        </footer>
    </div>
</template>