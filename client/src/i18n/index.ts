import { createI18n } from 'vue-i18n';
import en from '../languages/english.json';
// import ps from '../languages/pashto.json'; // Example for your specific market

const i18n = createI18n({
  legacy: false, // Required for Composition API
  locale: 'en',  // Default locale
  fallbackLocale: 'en',
  messages: {
    en,
    // ps
  },
  // Set to true if you want to use $t in templates without 'useI18n' in every component
  globalInjection: true,
});

export default i18n;
