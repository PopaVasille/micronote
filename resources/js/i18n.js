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
    // Debug în DOM pentru staging
    if (typeof window !== 'undefined') {
        window.debugI18n = {
            messages: messages,
            loadedKeys: Object.keys(messages),
            enKeys: messages.en ? Object.keys(messages.en) : [],
            roKeys: messages.ro ? Object.keys(messages.ro) : []
        };

        // Afișează info debug în header-ul paginii pentru staging
        setTimeout(() => {
            const debugDiv = document.createElement('div');
            debugDiv.id = 'i18n-debug';
            debugDiv.style.cssText = 'position:fixed;top:0;left:0;background:red;color:white;padding:5px;z-index:9999;font-size:12px;';
            debugDiv.innerHTML = `Debug: Locales loaded: ${Object.keys(messages).join(', ')} | EN Keys: ${messages.en ? Object.keys(messages.en).length : 0} | RO Keys: ${messages.ro ? Object.keys(messages.ro).length : 0}`;
            document.body.appendChild(debugDiv);

            // Auto-remove după 10 secunde
            setTimeout(() => debugDiv.remove(), 10000);
        }, 1000);
    }
    return messages;
}

export default createI18n({
    legacy: false,
    locale: 'en',
    fallbackLocale: 'en',
    messages: loadLocaleMessages(),
});
