/// <reference types="vite/client" />

import type { wayfinderRoute } from './wayfinder/route'
import type ButtonPrimary from './Components/ButtonPrimary.vue'

declare module 'vue' {
    interface GlobalComponents {
        ButtonPrimary: typeof ButtonPrimary
    }

    interface ComponentCustomProperties {
        route: typeof wayfinderRoute
    }
}
