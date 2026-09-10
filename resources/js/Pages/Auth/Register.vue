<script setup>
import InputError from '@/Components/InputError.vue';
import { store as register } from '@/actions/App/Http/Controllers/Auth/RegisteredUserController';
import { create as login } from '@/actions/App/Http/Controllers/Auth/AuthenticatedSessionController';
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({ name: '', email: '', password: '', password_confirmation: '' });
const submit = () => form.post(register.url(), { onFinish: () => form.reset('password', 'password_confirmation') });
</script>

<template>
    <Head title="Create account" />
    <main class="min-h-screen bg-app px-6 py-8 text-app-heading">
        <div class="mx-auto flex min-h-[calc(100vh-4rem)] w-full max-w-[342px] flex-col">
            <div class="flex flex-1 flex-col">
                <Link href="/" class="mt-16 flex items-center gap-2 self-center" aria-label="Clarity home">
                    <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-600 text-2xl font-bold text-white">c</span>
                    <span class="text-[26px] font-semibold tracking-tight text-slate-800">clarity</span>
                </Link>
                <div class="mt-8 text-center"><h1 class="text-[30px] font-bold leading-tight tracking-tight">Create your account</h1><p class="mt-1 text-base text-slate-500">Start seeing your money more clearly.</p></div>
                <form class="mt-8 flex flex-col gap-5" @submit.prevent="submit">
                    <div class="flex flex-col gap-2"><label for="name" class="text-base text-slate-800">Name</label><input id="name" v-model="form.name" type="text" name="name" placeholder="Your name" required autofocus autocomplete="name" class="h-[42px] rounded-lg border border-slate-300 px-4 text-base outline-none transition placeholder:text-slate-700 focus:border-blue-600 focus:ring-2 focus:ring-blue-100" /><InputError :message="form.errors.name" /></div>
                    <div class="flex flex-col gap-2"><label for="email" class="text-base text-slate-800">Email address</label><input id="email" v-model="form.email" type="email" name="email" placeholder="you@example.com" required autocomplete="username" class="h-[42px] rounded-lg border border-slate-300 px-4 text-base outline-none transition placeholder:text-slate-700 focus:border-blue-600 focus:ring-2 focus:ring-blue-100" /><InputError :message="form.errors.email" /></div>
                    <div class="flex flex-col gap-2"><label for="password" class="text-base text-slate-800">Password</label><input id="password" v-model="form.password" type="password" name="password" placeholder="Create a password" required autocomplete="new-password" class="h-[42px] rounded-lg border border-slate-300 px-4 text-base outline-none transition placeholder:text-slate-700 focus:border-blue-600 focus:ring-2 focus:ring-blue-100" /><InputError :message="form.errors.password" /></div>
                    <div class="flex flex-col gap-2"><label for="password_confirmation" class="text-base text-slate-800">Confirm password</label><input id="password_confirmation" v-model="form.password_confirmation" type="password" name="password_confirmation" placeholder="Repeat your password" required autocomplete="new-password" class="h-[42px] rounded-lg border border-slate-300 px-4 text-base outline-none transition placeholder:text-slate-700 focus:border-blue-600 focus:ring-2 focus:ring-blue-100" /><InputError :message="form.errors.password_confirmation" /></div>
                    <button type="submit" :disabled="form.processing" class="mt-1 h-[55px] rounded-xl bg-blue-600 text-base font-medium text-white transition hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-300 disabled:cursor-not-allowed disabled:opacity-60">Create account</button>
                </form>
                <p class="mt-7 text-center text-sm text-slate-500">Already have an account? <Link :href="login.url()" class="font-semibold text-blue-600 hover:text-blue-700">Log in</Link></p>
            </div>
            <p class="pb-7 text-center text-xs text-slate-500">A little clarity. A lot more confidence.</p>
        </div>
    </main>
</template>
