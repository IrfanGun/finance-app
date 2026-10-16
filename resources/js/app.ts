import '../css/app.css'

import { createInertiaApp } from '@inertiajs/vue3'
import { createApp, h } from 'vue'
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers'
import type { DefineComponent } from 'vue'
import ButtonPrimary from './Components/ButtonPrimary.vue'
import { wayfinderRoute } from './wayfinder/route'
import CmsLayout from './Layouts/CmsLayout.vue'

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
        const vueApp = createApp({
            render: () => h(App, props),
        })
        vueApp.config.globalProperties.route = wayfinderRoute
        vueApp
            .component('ButtonPrimary', ButtonPrimary)
            .use(plugin)
            .mount(el)
    },
})
