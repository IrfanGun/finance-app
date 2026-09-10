import '../css/app.css'

import { createInertiaApp } from '@inertiajs/vue3'
import { createApp, h } from 'vue'
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers'
import type { DefineComponent } from 'vue'
import { wayfinderRoute } from './wayfinder/route'

createInertiaApp({
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob('./Pages/**/*.vue') as Record<
                string,
                () => Promise<DefineComponent>
            >,
        ),

    setup({ el, App, props, plugin }) {
        const vueApp = createApp({
            render: () => h(App, props),
        })
        vueApp.config.globalProperties.route = wayfinderRoute
        vueApp
            .use(plugin)
            .mount(el)
    },
})
