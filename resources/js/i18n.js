import { createI18n } from 'vue-i18n'
import en from './locales/en.json'
import fil from './locales/fil.json'

function getInitialLocale() {
  try {
    const stored = localStorage.getItem('locale')
    if (stored === 'en' || stored === 'fil') return stored
  } catch {}
  return 'en'
}

export const i18n = createI18n({
  legacy: false,
  locale: getInitialLocale(),
  fallbackLocale: 'en',
  messages: { en, fil },
})
