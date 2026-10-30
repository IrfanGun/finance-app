/// <reference types="vite/client" />

import type { wayfinderRoute } from './wayfinder/route'
import type ButtonPrimary from './Components/ButtonPrimary.vue'
import type ConfirmationModal from './Components/ConfirmationModal.vue'
import type SuccessModal from './Components/SuccessModal.vue'

declare module 'vue' {
    interface GlobalComponents {
        ButtonPrimary: typeof ButtonPrimary
        ConfirmationModal: typeof ConfirmationModal
        SuccessModal: typeof SuccessModal
    }

    interface ComponentCustomProperties {
        route: typeof wayfinderRoute
    }
}
