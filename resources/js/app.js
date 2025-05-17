import '../css/app.css';
import './bootstrap';

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h } from 'vue';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import Test from './Components/test.vue';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

// Înregistrare Service Worker
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        // Amână înregistrarea pentru a nu bloca încărcarea paginii
        setTimeout(() => {
            navigator.serviceWorker.register('/serviceworker.js')
                .then(registration => {
                    console.log('ServiceWorker înregistrat cu succes:', registration.scope);
                })
                .catch(error => {
                    console.log('Înregistrarea ServiceWorker a eșuat:', error);
                });
        }, 1000);
    });
}

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob('./Pages/**/*.vue'),
        ),
    setup({el, App, props, plugin}) {
        const app = createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue);

        // Înregistrează componenta global
        app.component('Test', Test);

        app.mount(el);
    },
    progress: {
        color: '#4B5563',
    },
}).then(r =>{});
