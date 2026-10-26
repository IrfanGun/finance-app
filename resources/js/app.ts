import '../css/app.css'

import { createInertiaApp } from '@inertiajs/vue3'
import { createApp, h } from 'vue'
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers'
import type { DefineComponent } from 'vue'
import ButtonPrimary from './Components/ButtonPrimary.vue'
import { wayfinderRoute } from './wayfinder/route'
import CmsLayout from './Layouts/CmsLayout.vue'
import LanguageSwitcher from './Components/LanguageSwitcher.vue'
import { createAppI18n } from './i18n'

createInertiaApp({
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob('./Pages/**/*.vue') as Record<
                string,
                () => Promise<DefineComponent>
            >,
        ).then((page) => {
            if (window.location.pathname.startsWith('/cms/')) {
                page.default.layout = CmsLayout
            }

            return page
        }),

    setup({ el, App, props, plugin }) {
        const appI18n = createAppI18n(
            document.documentElement.lang === 'en' ? 'en' : 'id',
        )
        const vueApp = createApp({
            render: () => h('div', { style: { display: 'contents' } }, [
                h(App, props),
                h(LanguageSwitcher),
            ]),
        })
        vueApp.config.globalProperties.route = wayfinderRoute
        vueApp
            .component('ButtonPrimary', ButtonPrimary)
            .use(plugin)
            .use(appI18n)
            .mount(el)
    },
})
