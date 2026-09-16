<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { update as profileUpdate } from '@/routes/profile';
import { send as verificationSend } from '@/routes/verification';

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const user = usePage().props.auth.user;

const form = useForm({
    name: user.name,
    email: user.email,
});
</script>

<template>
    <section>
        <form
            class="space-y-5"
            @submit.prevent="form.patch(profileUpdate.url(), { preserveScroll: true })"
        >
            <div class="relative">
                <InputLabel
                    for="name"
                    value="Full name"
                    class="pointer-events-none absolute left-4 top-2 z-10 text-sm font-medium !text-app-muted"
                />

                <TextInput
                    id="name"
                    v-model="form.name"
                    type="text"
                    class="block h-14 w-full !rounded-xl !border-app-divider px-4 pb-1 pt-5 text-lg shadow-none focus:!border-app-primary focus:!ring-app-primary/10"
                    required
                    autofocus
                    autocomplete="name"
                />

                <InputError class="mt-2" :message="form.errors.name" />
            </div>

            <div class="relative">
                <InputLabel
                    for="email"
                    value="Email address"
                    class="pointer-events-none absolute left-4 top-2 z-10 text-sm font-medium !text-app-muted"
                />

                <TextInput
                    id="email"
                    v-model="form.email"
                    type="email"
                    class="block h-14 w-full !rounded-xl !border-app-divider px-4 pb-1 pt-5 text-lg shadow-none focus:!border-app-primary focus:!ring-app-primary/10"
                    required
                    autocomplete="username"
                />

                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div v-if="mustVerifyEmail && user.email_verified_at === null">
                <p class="rounded-lg bg-amber-50 px-4 py-3 text-sm leading-6 text-amber-800">
                    Your email address is unverified.
                    <Link
                        :href="verificationSend.url()"
                        method="post"
                        as="button"
                        class="font-semibold underline hover:text-amber-900 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2"
                    >
                        Resend verification email
                    </Link>
                </p>

                <div
                    v-show="status === 'verification-link-sent'"
                    class="mt-2 text-sm font-medium text-green-600"
                >
                    A new verification link has been sent to your email address.
                </div>
            </div>

            <div class="flex flex-col items-stretch gap-2">
                <PrimaryButton
                    class="!bg-app-primary w-full justify-center rounded-xl py-3 text-lg normal-case tracking-normal hover:!bg-app-bright focus:!ring-app-primary/30"
                    :disabled="form.processing"
                >
                    {{ form.processing ? 'Saving...' : 'Save changes' }}
                </PrimaryButton>

                <Transition
                    enter-active-class="transition ease-in-out"
                    enter-from-class="opacity-0"
                    leave-active-class="transition ease-in-out"
                    leave-to-class="opacity-0"
                >
                    <p
                        v-if="form.recentlySuccessful"
                        class="text-sm text-green-600"
                    >
                        Saved.
                    </p>
                </Transition>
            </div>
        </form>
    </section>
</template>
