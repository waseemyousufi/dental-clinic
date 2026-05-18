import { createI18n } from 'vue-i18n'

import en from '../languages/english.json'
import dr from '../languages/dari.json'
import ps from '../languages/pashto.json'

const savedLocale = localStorage.getItem('locale') || 'en'

const i18n = createI18n({
  legacy: false,
  locale: savedLocale,
  fallbackLocale: 'en',

  messages: {
    en,
    dr,
    ps,
  },

  globalInjection: true,
})

export default i18n
