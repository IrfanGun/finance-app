import { dashboard, login, logout, register } from '@/routes'
import { edit as profileEdit, update as profileUpdate, destroy as profileDestroy } from '@/routes/profile'
import { email as passwordEmail, request as passwordRequest, reset as passwordReset, store as passwordStore, confirm as passwordConfirm, update as passwordUpdate } from '@/routes/password'
import { send as verificationSend } from '@/routes/verification'

const urls: Record<string, () => string> = {
    dashboard: () => dashboard.url(),
    login: () => login.url(),
    logout: () => logout.url(),
    register: () => register.url(),
    'profile.edit': () => profileEdit.url(),
    'profile.update': () => profileUpdate.url(),
    'profile.destroy': () => profileDestroy.url(),
    'password.request': () => passwordRequest.url(),
    'password.email': () => passwordEmail.url(),
    'password.store': () => passwordStore.url(),
    'password.confirm': () => passwordConfirm.url(),
    'password.update': () => passwordUpdate.url(),
    'verification.send': () => verificationSend.url(),
}

export function wayfinderRoute(name?: string) {
    if (!name) {
        return {
            current: (currentName: string) => window.location.pathname === urls[currentName]?.(),
        }
    }

    if (!urls[name]) {
        throw new Error(`Unknown route: ${name}`)
    }

    return urls[name]()
}
