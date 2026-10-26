<script setup lang="ts">
import { computed } from 'vue'
import { router } from '@inertiajs/vue3'
import LocaleController from '@/actions/App/Http/Controllers/LocaleController'
import { useTranslation } from '@/Composables/useTranslation'

const props = defineProps({
    floating: {
        type: Boolean,
        default: true,
    },
})

const { locale: activeLocale, t } = useTranslation()
const currentLocale = computed(() => activeLocale.value === 'en' ? 'en' : 'id')
const label = computed(() => t('Choose language'))
const isDashboard = computed(() => window.location.pathname === '/dashboard')

function changeLocale(nextLocale: 'en' | 'id'): void {
    if (nextLocale === currentLocale.value) {
        return
    }

    router.post(
        LocaleController.url(),
        { locale: nextLocale },
        {
            preserveScroll: true,
            onSuccess: () => {
                activeLocale.value = nextLocale
            },
        },
    )
}
</script>

<template>
    <div
        v-if="!props.floating || !isDashboard"
        class="flex items-center gap-1 rounded-xl border border-slate-200 bg-white/95 p-1 shadow-lg backdrop-blur"
        :class="props.floating ? 'fixed right-4 top-4 z-[100]' : 'shrink-0 shadow-none'"
        role="group"
        :aria-label="label"
    >
        <ButtonPrimary
            type="button"
            :variant="currentLocale === 'en' ? 'primary' : 'secondary'"
            class="!min-h-0 !rounded-lg !px-3 !py-1.5 !text-xs"
            :aria-pressed="currentLocale === 'en'"
            @click="changeLocale('en')"
        >
            EN
        </ButtonPrimary>
        <ButtonPrimary
            type="button"
            :variant="currentLocale === 'id' ? 'primary' : 'secondary'"
            class="!min-h-0 !rounded-lg !px-3 !py-1.5 !text-xs"
            :aria-pressed="currentLocale === 'id'"
            @click="changeLocale('id')"
        >
            ID
        </ButtonPrimary>
    </div>
</template>
