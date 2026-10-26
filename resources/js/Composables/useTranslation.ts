import { useI18n } from 'vue-i18n'

export function useTranslation() {
    const { locale, t } = useI18n({ useScope: 'global' })

    return {
        locale,
        t,
    }
}
