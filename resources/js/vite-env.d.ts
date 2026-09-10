/// <reference types="vite/client" />

import type { wayfinderRoute } from './wayfinder/route'

declare module 'vue' {
    interface ComponentCustomProperties {
        route: typeof wayfinderRoute
    }
}
