import { createI18n } from 'vue-i18n';

function loadLocaleMessages() {
    const mainLocales = import.meta.glob('./locales/*.json', { eager: true });
    const messages = {};

    // Load main translation files (en.json, ro.json)
    for (const path in mainLocales) {
        const matched = path.match(/([A-Za-z0-9-_]+)\.json$/i);
        if (matched && matched.length > 1) {
            const locale = matched[1];
            // Ensure we only process main files here, not terms or privacy files
            if ((locale === 'en' || locale === 'ro') && !path.includes('terms.') && !path.includes('privacy.')) {
                messages[locale] = mainLocales[path].default;
            }
        }
    }

    // Load and merge the terms files (terms.en.json, terms.ro.json)
    const termsLocales = import.meta.glob('./locales/terms.*.json', { eager: true });
    for (const path in termsLocales) {
        const matched = path.match(/terms\.([a-z]{2})\.json$/i);
        if (matched && matched.length > 1) {
            const locale = matched[1]; // 'en' or 'ro'
            if (messages[locale]) {
                messages[locale].terms = termsLocales[path].default;
            }
        }
    }

    // Load and merge the privacy files (privacy.en.json, privacy.ro.json)
    const privacyLocales = import.meta.glob('./locales/privacy.*.json', { eager: true });
    for (const path in privacyLocales) {
        const matched = path.match(/privacy\.([a-z]{2})\.json$/i);
        if (matched && matched.length > 1) {
            const locale = matched[1]; // 'en' or 'ro'
            if (messages[locale]) {
                messages[locale].privacy = privacyLocales[path].default;
            }
        }
    }
    return messages;
}

export default createI18n({
    legacy: false,
    locale: 'en',
    fallbackLocale: 'en',
    messages: loadLocaleMessages(),
});
