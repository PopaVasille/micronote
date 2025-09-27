import './bootstrap';
import '../css/app.css';

import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import i18n from './i18n';

const appName = window.document.getElementsByTagName('title')[0]?.innerText || 'Laravel';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        const app = createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .use(i18n);

        console.log('DEBUG I18N MESSAGES:', JSON.stringify(i18n.global.messages.value));

        // Initialize locale from localStorage or server props
        const storedLocale = localStorage.getItem('selected_locale');
        const serverLocale = props.initialPage.props.locale;
        
        // Prefer server locale if it exists and differs from stored locale
        const initialLocale = serverLocale || storedLocale || 'en';
        
        if (initialLocale) {
            i18n.global.locale.value = initialLocale;
            // Update localStorage to match server
            if (serverLocale) {
                localStorage.setItem('selected_locale', serverLocale);
            }
        }

        app.mixin({
            watch: {
                '$page.props.locale': {
                    handler(newLocale) {
                        if (newLocale && i18n.global.locale.value !== newLocale) {
                            i18n.global.locale.value = newLocale;
                            localStorage.setItem('selected_locale', newLocale);
                        }
                    },
                    immediate: true,
                },
            },
        });

        app.mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});
