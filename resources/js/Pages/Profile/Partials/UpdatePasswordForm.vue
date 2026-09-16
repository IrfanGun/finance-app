<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { update as passwordUpdate } from '@/routes/password';

const passwordInput = ref(null);
const currentPasswordInput = ref(null);
const focusedField = ref(null);

const form = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const focusField = (field) => {
    focusedField.value = field;
};

const blurField = (field) => {
    if (focusedField.value === field) {
        focusedField.value = null;
    }
};

const shouldFloatLabel = (field) => {
    return focusedField.value === field || Boolean(form[field]);
};

const updatePassword = () => {
    form.put(passwordUpdate.url(), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
        onError: () => {
            if (form.errors.password) {
                form.reset('password', 'password_confirmation');
                focusField('password');
                passwordInput.value.focus();
            }

            if (form.errors.current_password) {
                form.reset('current_password');
                focusField('current_password');
                currentPasswordInput.value.focus();
            }
        },
    });
};
</script>

<template>
    <section>
        <form class="space-y-5" @submit.prevent="updatePassword">
            <div>
                <div class="relative">
                    <TextInput
                        id="current_password"
                        ref="currentPasswordInput"
                        v-model="form.current_password"
                        type="password"
                        placeholder=" "
                        class="peer block h-14 w-full !rounded-xl !border-app-divider !bg-white px-4 pb-1 pt-5 text-lg shadow-none placeholder:text-transparent focus:!border-app-primary focus:!ring-app-primary/10"
                        autocomplete="current-password"
                        @focus="focusField('current_password')"
                        @blur="blurField('current_password')"
                    />

                    <InputLabel
                        for="current_password"
                        value="Current password"
                        :class="[
                            'pointer-events-none absolute left-3 z-10 px-1 transition-all duration-200',
                            shouldFloatLabel('current_password')
                                ? 'top-0 -translate-y-1/2 bg-white text-sm !text-app-primary'
                                : 'top-1/2 -translate-y-1/2 bg-transparent text-base !text-app-muted',
                        ]"
                    />
                </div>

                <InputError
                    :message="form.errors.current_password"
                    class="mt-2"
                />
            </div>

            <div>
                <div class="relative">
                    <TextInput
                        id="password"
                        ref="passwordInput"
                        v-model="form.password"
                        type="password"
                        placeholder=" "
                        class="peer block h-14 w-full !rounded-xl !border-app-divider !bg-white px-4 pb-1 pt-5 text-lg shadow-none placeholder:text-transparent focus:!border-app-primary focus:!ring-app-primary/10"
                        autocomplete="new-password"
                        @focus="focusField('password')"
                        @blur="blurField('password')"
                    />

                    <InputLabel
                        for="password"
                        value="New password"
                        :class="[
                            'pointer-events-none absolute left-3 z-10 px-1 transition-all duration-200',
                            shouldFloatLabel('password')
                                ? 'top-0 -translate-y-1/2 bg-white text-sm !text-app-primary'
                                : 'top-1/2 -translate-y-1/2 bg-transparent text-base !text-app-muted',
                        ]"
                    />
                </div>

                <InputError :message="form.errors.password" class="mt-2" />
            </div>

            <div>
                <div class="relative">
                    <TextInput
                        id="password_confirmation"
                        v-model="form.password_confirmation"
                        type="password"
                        placeholder=" "
                        class="peer block h-14 w-full !rounded-xl !border-app-divider !bg-white px-4 pb-1 pt-5 text-lg shadow-none placeholder:text-transparent focus:!border-app-primary focus:!ring-app-primary/10"
                        autocomplete="new-password"
                        @focus="focusField('password_confirmation')"
                        @blur="blurField('password_confirmation')"
                    />

                    <InputLabel
                        for="password_confirmation"
                        value="Confirm new password"
                        :class="[
                            'pointer-events-none absolute left-3 z-10 px-1 transition-all duration-200',
                            shouldFloatLabel('password_confirmation')
                                ? 'top-0 -translate-y-1/2 bg-white text-sm !text-app-primary'
                                : 'top-1/2 -translate-y-1/2 bg-transparent text-base !text-app-muted',
                        ]"
                    />
                </div>

                <InputError
                    :message="form.errors.password_confirmation"
                    class="mt-2"
                />
            </div>

            <div class="flex flex-col items-stretch gap-2">
                <PrimaryButton
                    class="!bg-app-primary !text-xl h-14 w-full justify-center rounded-xl py-3 normal-case tracking-normal hover:!bg-app-bright focus:!ring-app-primary/30"
                    :disabled="form.processing"
                >
                    {{ form.processing ? 'Updating...' : 'Update password' }}
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
