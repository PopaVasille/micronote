<template>
    <div class="bg-gray-900 text-white">
        <Head title="Micronote - Notițe rapide, via Telegram" />

        <!-- Header -->
        <header class="container mx-auto px-6 py-4 flex justify-between items-center">
            <div class="text-2xl font-bold">
                <a href="#">Micronote</a>
            </div>
            <nav class="hidden md:flex space-x-8">
                <a href="#features" class="hover:text-gray-300">Funcționalități</a>
                <a href="#pricing" class="hover:text-gray-300">Prețuri</a>
                <a href="#early-access" class="hover:text-gray-300">Acces Timpuriu</a>
            </nav>
            <div>
                <a href="/login" class="bg-gray-800 hover:bg-gray-700 py-2 px-4 rounded-lg">Login</a>
                <a href="/register" class="bg-blue-600 hover:bg-blue-500 py-2 px-4 rounded-lg ml-4">Register</a>
            </div>
        </header>

        <!-- Hero Section -->
        <section class="container mx-auto px-6 py-24 text-center">
            <h1 class="text-5xl font-extrabold md:text-6xl">Eliberează-ți mintea. Notează instant.</h1>
            <p class="mt-6 text-lg text-gray-400 max-w-2xl mx-auto">Micronote este sanctuarul tău digital pentru gânduri, idei și memento-uri. Capturează totul instantaneu prin Telegram și organizează-ți viața fără efort.</p>
            <div class="mt-8">
                <a href="#early-access" class="bg-blue-600 hover:bg-blue-500 text-white font-bold py-4 px-8 rounded-full text-lg">Devino un membru fondator</a>
            </div>
        </section>

        <!-- Features Section -->
        <section id="features" class="py-20 bg-gray-800">
            <div class="container mx-auto px-6">
                <div class="text-center mb-12">
                    <h2 class="text-4xl font-bold">Tot ce ai nevoie pentru a fi organizat</h2>
                    <p class="text-gray-400 mt-4">De la liste de cumpărături la idei geniale, Micronote este partenerul tău de încredere.</p>
                </div>
                <div class="grid md:grid-cols-3 gap-8 text-center">
                    <div class="bg-gray-900 p-8 rounded-lg">
                        <h3 class="text-2xl font-bold">Integrare cu Telegram</h3>
                        <p class="mt-4 text-gray-400">Trimite un mesaj bot-ului nostru și ideea ta este salvată. Simplu, rapid și eficient, direct din aplicația ta preferată de mesagerie.</p>
                    </div>
                    <div class="bg-gray-900 p-8 rounded-lg">
                        <h3 class="text-2xl font-bold">Clasificare Inteligentă</h3>
                        <p class="mt-4 text-gray-400">Cu ajutorul AI, Micronote înțelege dacă vrei să salvezi o notiță, să creezi o listă sau să setezi un memento. Tu doar scrii, noi ne ocupăm de restul.</p>
                    </div>
                    <div class="bg-gray-900 p-8 rounded-lg">
                        <h3 class="text-2xl font-bold">Organizare flexibilă</h3>
                        <p class="mt-4 text-gray-400">Folosește etichete, culori și categorii pentru a-ți regăsi notițele instant. Căutare puternică pentru a nu pierde niciodată o idee.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Pricing Section -->
        <section id="pricing" class="py-20">
            <div class="container mx-auto px-6 text-center">
                <h2 class="text-4xl font-bold">Un preț corect pentru superputeri</h2>
                <p class="text-gray-400 mt-4 max-w-3xl mx-auto">Micronote este și va fi mereu gratuit pentru funcționalitățile de bază prin Telegram. Pentru a susține dezvoltarea și a oferi integrări premium, vom introduce un plan opțional.</p>
                <div class="mt-12 flex flex-col md:flex-row justify-center items-center gap-8">
                    <div class="border border-gray-700 rounded-lg p-8 w-full md:w-1/3">
                        <h3 class="text-2xl font-bold">Gratuit</h3>
                        <p class="text-5xl font-extrabold my-4">$0</p>
                        <ul class="text-gray-400 space-y-2">
                            <li>Notițe nelimitate prin Telegram</li>
                            <li>Clasificare AI de bază</li>
                            <li>Organizare cu etichete</li>
                        </ul>
                    </div>
                    <div class="border-2 border-blue-500 rounded-lg p-8 w-full md:w-1/3 relative">
                        <span class="bg-blue-500 text-white text-sm font-bold px-3 py-1 rounded-full absolute -top-4">ÎN CURÂND</span>
                        <h3 class="text-2xl font-bold">Premium</h3>
                        <p class="text-5xl font-extrabold my-4">$5<span class="text-lg">/lună</span></p>
                        <ul class="text-gray-400 space-y-2">
                            <li>Tot ce este în planul Gratuit, plus:</li>
                            <li>Integrare cu WhatsApp</li>
                            <li>Memento-uri recurente avansate</li>
                            <li>Suport prioritar</li>
                        </ul>
                    </div>
                </div>
            </div>
        </section>

        <!-- Early Access Section -->
        <section id="early-access" class="py-20 bg-gray-800">
            <div class="container mx-auto px-6 text-center max-w-3xl">
                <h2 class="text-4xl font-bold">Fii parte din poveste.</h2>
                <p class="text-gray-400 mt-4">Încă lucrăm la finisarea detaliilor și la adăugarea de noi funcționalități uimitoare. Dacă îți place ce ai văzut și vrei să fii printre primii care folosesc Micronote la potențial maxim, lasă-ne adresa ta de e-mail. Vei primi acces prioritar, actualizări exclusive și poate chiar o surpriză pentru membrii fondatori.</p>
                <div v-if="$page.props.flash.success" class="mt-8 bg-green-500 text-white p-4 rounded-lg">
                    {{ $page.props.flash.success }}
                </div>
                <form v-else @submit.prevent="submit" class="mt-8 flex flex-col md:flex-row justify-center items-center gap-4">
                    <input v-model="form.email" type="email" placeholder="adresa.ta@email.com" class="bg-gray-900 border border-gray-700 rounded-lg py-3 px-4 w-full md:w-2/3 focus:ring-blue-500 focus:border-blue-500">
                    <button type="submit" :disabled="form.processing" class="bg-blue-600 hover:bg-blue-500 text-white font-bold py-3 px-8 rounded-lg w-full md:w-auto">Vreau acces!</button>
                </form>
                <div v-if="form.errors.email" class="text-red-500 mt-2">{{ form.errors.email }}</div>
            </div>
        </section>

        <!-- Footer -->
        <footer class="container mx-auto px-6 py-8 text-center text-gray-500">
            <p>&copy; {{ new Date().getFullYear() }} Micronote. Toate drepturile rezervate.</p>
        </footer>

    </div>
</template>

<script setup>
import { Head, useForm } from '@inertiajs/vue3';

const form = useForm({
    email: '',
});

const submit = () => {
    form.post(route('early-access.store'), {
        preserveScroll: true,
        onSuccess: () => form.reset('email'),
    });
};
</script>
