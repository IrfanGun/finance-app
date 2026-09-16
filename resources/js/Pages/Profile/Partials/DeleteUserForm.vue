<script setup>
import DangerButton from '@/Components/DangerButton.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import Modal from '@/Components/Modal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { useForm } from '@inertiajs/vue3';
import { nextTick, ref } from 'vue';
import { destroy as profileDestroy } from '@/routes/profile';

const confirmingUserDeletion = ref(false);
const passwordInput = ref(null);

const form = useForm({
    password: '',
});

const confirmUserDeletion = () => {
    confirmingUserDeletion.value = true;

    nextTick(() => passwordInput.value.focus());
};

const deleteUser = () => {
    form.delete(profileDestroy.url(), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
        onError: () => passwordInput.value.focus(),
        onFinish: () => form.reset(),
    });
};

const closeModal = () => {
    confirmingUserDeletion.value = false;

    form.clearErrors();
    form.reset();
};
</script>

<template>
    <section class="space-y-5">
        <header>
            <h2 class="text-base font-semibold text-red-700">
                Delete account
            </h2>

            <p class="mt-1 text-sm leading-6 text-red-700/70">
                This permanently removes your account and all associated data.
            </p>
        </header>

        <DangerButton
            class="rounded-xl px-4 py-3 normal-case tracking-normal"
            @click="confirmUserDeletion"
        >
            Delete account
        </DangerButton>

        <Modal :show="confirmingUserDeletion" @close="closeModal">
            <div class="p-6">
                <h2 class="text-lg font-semibold text-gray-900">
                    Delete your account?
                </h2>

                <p class="mt-1 text-sm leading-6 text-gray-600">
                    This permanently deletes your account and all related data.
                    Enter your password to continue.
                </p>

                <div class="mt-6">
                    <InputLabel
                        for="password"
                        value="Password"
                        class="sr-only"
                    />

                    <TextInput
                        id="password"
                        ref="passwordInput"
                        v-model="form.password"
                        type="password"
                        class="mt-2 block h-12 w-3/4 !rounded-xl !border-app-divider px-4 text-base shadow-none focus:!border-app-primary focus:!ring-app-primary/10"
                        placeholder="Password"
                        @keyup.enter="deleteUser"
                    />

                    <InputError :message="form.errors.password" class="mt-2" />
                </div>

                <div class="mt-6 flex justify-end">
                    <SecondaryButton
                        class="rounded-xl normal-case tracking-normal"
                        @click="closeModal"
                    >
                        Cancel
                    </SecondaryButton>

                    <DangerButton
                        class="ms-3 rounded-xl normal-case tracking-normal"
                        :class="{ 'opacity-25': form.processing }"
                        :disabled="form.processing"
                        @click="deleteUser"
                    >
                        Delete account
                    </DangerButton>
                </div>
            </div>
        </Modal>
    </section>
</template>
