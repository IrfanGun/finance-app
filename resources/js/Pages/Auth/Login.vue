<script setup>
import InputError from '@/Components/InputError.vue';
import { store as login } from '@/actions/App/Http/Controllers/Auth/AuthenticatedSessionController';
import { create as forgotPassword } from '@/actions/App/Http/Controllers/Auth/PasswordResetLinkController';
import { create as register } from '@/actions/App/Http/Controllers/Auth/RegisteredUserController';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({ canResetPassword: { type: Boolean }, status: { type: String } });
const form = useForm({ email: '', password: '', remember: false });
const submit = () => form.post(login.url(), { onFinish: () => form.reset('password') });
</script>

<template>
    <Head title="Log in" />
    <main class="min-h-screen bg-app px-6 py-8 text-app-heading">
        <div class="mx-auto flex min-h-[calc(100vh-4rem)] w-full max-w-[342px] flex-col">
            <div class="flex flex-1 flex-col">
                <Link href="/" class="mt-24 flex items-center gap-2 self-center" aria-label="Clarity home">
                    <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-600 text-2xl font-bold text-white">c</span>
                    <span class="text-[26px] font-semibold tracking-tight text-slate-800">clarity</span>
                </Link>
                <div class="mt-9 text-center"><h1 class="text-[30px] font-bold leading-tight tracking-tight">Welcome back</h1><p class="mt-1 text-base text-slate-500">Your money, in a clearer view.</p></div>
                <div v-if="status" class="mt-6 text-sm font-medium text-green-600">{{ status }}</div>
                <form class="mt-9 flex flex-col gap-6" @submit.prevent="submit">
                    <div class="flex flex-col gap-2"><label for="email" class="text-base text-slate-800">Email address</label><input id="email" v-model="form.email" type="email" name="email" placeholder="you@example.com" required autofocus autocomplete="username" class="h-[42px] rounded-lg border border-slate-300 px-4 text-base text-slate-900 outline-none transition placeholder:text-slate-700 focus:border-blue-600 focus:ring-2 focus:ring-blue-100" /><InputError :message="form.errors.email" /></div>
                    <div class="flex flex-col gap-2"><label for="password" class="text-base text-slate-800">Password</label><input id="password" v-model="form.password" type="password" name="password" placeholder="Enter your password" required autocomplete="current-password" class="h-[42px] rounded-lg border border-slate-300 px-4 text-base text-slate-900 outline-none transition placeholder:text-slate-700 focus:border-blue-600 focus:ring-2 focus:ring-blue-100" /><InputError :message="form.errors.password" /></div>
                    <label class="-mt-3 flex items-center gap-2 text-sm text-slate-600">
                        <input v-model="form.remember" type="checkbox" name="remember" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500" />
                        Remember me
                    </label>
                    <Link v-if="canResetPassword" :href="forgotPassword.url()" class="-mt-1 self-end text-sm font-medium text-blue-600 hover:text-blue-700">Forgot password?</Link>
                    <button type="submit" :disabled="form.processing" class="h-[55px] rounded-xl bg-blue-600 text-base font-medium text-white transition hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-300 disabled:cursor-not-allowed disabled:opacity-60">Log in</button>
                </form>
                <p class="mt-7 text-center text-sm text-slate-500">New to Clarity? <Link :href="register.url()" class="font-semibold text-blue-600 hover:text-blue-700">Create account</Link></p>
            </div>
            <p class="pb-7 text-center text-xs text-slate-500">A little clarity. A lot more confidence.</p>
        </div>
    </main>
</template>
