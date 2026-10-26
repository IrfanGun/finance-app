import { createI18n } from 'vue-i18n'
import en from './en.json'
import id from './id.json'

export type Locale = 'en' | 'id'

const messages: Record<Locale, Record<string, string>> = {
    en,
    id,
}

export function createAppI18n(locale: Locale) {
    return createI18n({
        legacy: false,
        globalInjection: true,
        locale,
        fallbackLocale: 'en',
        messages,
    })
}
