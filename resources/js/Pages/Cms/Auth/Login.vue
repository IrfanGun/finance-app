<script setup>
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Eye, EyeOff, LockKeyhole, UserRound } from 'lucide-vue-next';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

defineProps({
    canResetPassword: {
        type: Boolean,
        default: false,
    },
    status: {
        type: String,
        default: '',
    },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const showPassword = ref(false);

const submit = () => form.post('/cms/login', {
    onFinish: () => form.reset('password'),
});
</script>

<template>
    <Head title="Log in to Clarity" />

    <div class="relative flex h-dvh min-h-0 flex-col overflow-hidden bg-[#101A2E] text-white">
        <div
            class="fixed inset-0 bg-cover bg-center"
            style="background-image: url('/images/login-dawn-mountains.png')"
            aria-hidden="true"
        />
        <div
            class="fixed inset-0 bg-gradient-to-r from-[#101A2E]/75 via-[#101A2E]/35 to-[#101A2E]/20"
            aria-hidden="true"
        />

        <header class="cms-login-header relative z-10 mx-auto flex w-full max-w-[1440px] shrink-0 items-center justify-between px-6 py-3 sm:px-10 sm:py-4 lg:px-14">
            <Link href="/cms/login" class="flex items-center gap-3" aria-label="Clarity home">
                <span class="flex h-11 w-11 items-center justify-center rounded-full bg-[#2457DA] text-2xl font-semibold text-white shadow-lg shadow-black/20">c</span>
                <span>
                    <span class="block text-2xl font-medium tracking-tight">Clarity</span>
                    <span class="mt-0.5 block text-[9px] font-medium uppercase tracking-[0.25em] text-white/75">Finance · Focus · Freedom</span>
                </span>
            </Link>

            <div class="flex items-center gap-4 text-xs font-medium text-white/90 sm:gap-6 sm:text-sm">
                <span class="hidden sm:inline">Personal finance</span>
                <span class="hidden text-white/50 sm:inline">|</span>
                <Link href="/login" class="transition hover:text-[#FFB28C]">Mobile app</Link>
            </div>
        </header>

        <main class="relative z-10 mx-auto grid min-h-0 w-full max-w-[1440px] flex-1 grid-cols-1 items-center px-5 py-2 sm:px-10 lg:grid-cols-[minmax(0,1fr)_360px_minmax(0,1fr)] lg:px-14">
            <section class="hidden max-w-[340px] py-6 lg:block">
                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-blue-200">Your money, in focus</p>
                <h1 class="mt-5 font-serif text-4xl leading-[1.12] tracking-tight xl:text-[46px]">
                    A more meaningful tomorrow,
                    <span class="block italic text-blue-300">together.</span>
                </h1>
                <div class="mt-6 h-1 w-12 rounded-full bg-[#2457DA]" />
                <p class="mt-5 max-w-[270px] font-serif text-base leading-7 text-white/90">
                    A clearer view of your finances, at every stage of life.
                </p>
            </section>

            <section class="flex w-full items-center justify-center lg:col-start-2">
                <div class="cms-login-card flex min-h-[500px] w-full max-w-[350px] flex-col justify-center rounded-[24px] border border-white/70 bg-[#FBF8F3]/95 px-6 py-8 text-[#24242C] shadow-[0_24px_80px_rgba(17,15,13,0.35)] backdrop-blur-xl sm:px-7 sm:py-8">
                    <div class="text-center">
                        <h2 class="font-serif text-[30px] leading-tight tracking-tight">Welcome back</h2>
                        <p class="mt-1.5 text-[9px] font-medium uppercase tracking-[0.25em] text-[#78777A]">Log in to your account</p>
                    </div>

                    <div v-if="status" class="mt-4 rounded-lg bg-green-50 px-3 py-2.5 text-xs font-medium text-green-700">
                        {{ status }}
                    </div>

                    <form class="mt-4 flex flex-col gap-3" @submit.prevent="submit">
                        <div>
                            <label for="email" class="sr-only">Email address</label>
                            <div class="relative">
                                <UserRound class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-[#64646B]" :size="17" />
                                <input
                                    id="email"
                                    v-model="form.email"
                                    type="email"
                                    name="email"
                                    placeholder="Email address"
                                    required
                                    autofocus
                                    autocomplete="username"
                                    class="h-11 w-full rounded-lg border border-[#E2DED8] bg-white pl-10 pr-4 text-sm text-[#24242C] outline-none transition placeholder:text-[#85848A] focus:border-[#2457DA] focus:ring-2 focus:ring-blue-100"
                                />
                            </div>
                            <InputError class="mt-1.5" :message="form.errors.email" />
                        </div>

                        <div>
                            <label for="password" class="sr-only">Password</label>
                            <div class="relative">
                                <LockKeyhole class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-[#64646B]" :size="17" />
                                <input
                                    id="password"
                                    v-model="form.password"
                                    :type="showPassword ? 'text' : 'password'"
                                    name="password"
                                    placeholder="Password"
                                    required
                                    autocomplete="current-password"
                                    class="h-11 w-full rounded-lg border border-[#E2DED8] bg-white pl-10 pr-12 text-sm text-[#24242C] outline-none transition placeholder:text-[#85848A] focus:border-[#2457DA] focus:ring-2 focus:ring-blue-100"
                                />
                                <button
                                    type="button"
                                    class="absolute right-3 top-1/2 -translate-y-1/2 text-[#64646B] transition hover:text-[#24242C]"
                                    :aria-label="showPassword ? 'Hide password' : 'Show password'"
                                    @click="showPassword = !showPassword"
                                >
                                    <EyeOff v-if="showPassword" :size="17" />
                                    <Eye v-else :size="17" />
                                </button>
                            </div>
                            <InputError class="mt-1.5" :message="form.errors.password" />
                        </div>

                        <div class="flex items-center justify-between gap-3 pt-0.5">
                            <label class="flex items-center gap-2 text-xs text-[#68676D]">
                                <input v-model="form.remember" type="checkbox" name="remember" class="rounded border-[#C9C4BD] text-[#2457DA] focus:ring-blue-200" />
                                Remember me
                            </label>
                            <Link v-if="canResetPassword" href="/forgot-password" class="text-[11px] font-medium text-[#55545A] underline decoration-[#B9B4AD] underline-offset-2 transition hover:text-[#2457DA]">
                                Forgot password?
                            </Link>
                        </div>

                        <PrimaryButton
                            type="submit"
                            variant="cms"
                            :disabled="form.processing"
                            class="mt-1"
                        >
                            {{ form.processing ? 'Signing in…' : 'Log in  →' }}
                        </PrimaryButton>
                    </form>

                    <p class="mt-4 text-center text-[11px] text-[#68676D]">
                        New to Clarity?
                        <Link href="/register" class="ml-1 font-semibold text-[#2457DA] underline decoration-blue-200 underline-offset-2 hover:text-[#1D4FC9]">Create an account</Link>
                    </p>

                    <div class="mt-4 flex items-center justify-center gap-2 border-t border-[#E7E1D9] pt-3 text-[10px] text-[#858188]">
                        <LockKeyhole :size="13" />
                        <span>Your sign-in is encrypted and secure</span>
                    </div>
                </div>
            </section>

            <div class="hidden lg:block" />
        </main>

        <footer class="cms-login-footer relative z-10 mx-auto flex w-full max-w-[1440px] shrink-0 flex-col items-center justify-between gap-2 px-6 pb-3 text-[10px] text-white/80 sm:flex-row sm:px-10 lg:px-14">
            <span>Clarity Finance · Personal money management</span>
            <div class="flex items-center gap-4">
                <span>Privacy</span>
                <span class="text-white/40">|</span>
                <span>Security</span>
                <span class="text-white/40">|</span>
                <Link href="/login" class="transition hover:text-white">Open mobile version</Link>
            </div>
        </footer>
    </div>
</template>

<style scoped>
@media (max-height: 640px) {
    .cms-login-header {
        padding-top: 0.5rem;
        padding-bottom: 0.5rem;
    }

    .cms-login-footer {
        display: none;
    }

    .cms-login-card {
        min-height: min(460px, calc(100dvh - 120px));
        padding-top: 1.25rem;
        padding-bottom: 1.25rem;
    }

    .cms-login-card h2 {
        font-size: 1.5rem;
    }

    .cms-login-card form {
        gap: 0.5rem;
    }
}
</style>
