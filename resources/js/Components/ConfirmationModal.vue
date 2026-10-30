<script>
let nextConfirmationModalId = 0;
</script>

<script setup>
import { AlertTriangle } from 'lucide-vue-next';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    title: {
        type: String,
        required: true,
    },
    description: {
        type: String,
        default: '',
    },
    cancelText: {
        type: String,
        default: '',
    },
    confirmText: {
        type: String,
        default: '',
    },
    intent: {
        type: String,
        default: 'danger',
        validator: (value) => ['danger', 'primary'].includes(value),
    },
    closeOnBackdrop: {
        type: Boolean,
        default: true,
    },
});

const emit = defineEmits(['close', 'confirm']);
const instanceId = ++nextConfirmationModalId;
const titleId = `confirmation-modal-title-${instanceId}`;
const descriptionId = `confirmation-modal-description-${instanceId}`;

function close() {
    emit('close');
}

function confirm() {
    emit('confirm');
}
</script>

<template>
    <Teleport to="body">
        <div
            v-if="props.show"
            class="fixed inset-0 z-[100] flex items-center justify-center overflow-y-auto bg-[#14213A]/60 p-4 backdrop-blur-[2px] sm:p-6"
            @click.self="props.closeOnBackdrop && close()"
            @keydown.esc.prevent="close"
        >
            <section
                class="my-auto w-full max-w-md rounded-2xl border border-[#E1E8F2] bg-white p-6 shadow-[0_24px_80px_rgba(15,35,70,0.25)] sm:p-7"
                role="dialog"
                aria-modal="true"
                :aria-labelledby="titleId"
                :aria-describedby="props.description ? descriptionId : undefined"
                @click.stop
            >
                <div class="flex flex-col items-center text-center">
                    <span class="flex h-16 w-16 items-center justify-center rounded-full bg-[#FFF1F3] text-[#F43F5E]">
                        <span class="flex h-10 w-10 items-center justify-center rounded-full bg-[#FFE4E9]">
                            <slot name="icon">
                                <AlertTriangle :size="22" :stroke-width="2.5" />
                            </slot>
                        </span>
                    </span>
                    <h2 :id="titleId" class="mt-5 text-xl font-bold text-[#14213A]">
                        {{ props.title }}
                    </h2>
                    <p
                        v-if="props.description"
                        :id="descriptionId"
                        class="mt-2 max-w-sm text-sm leading-6 text-[#687A96]"
                    >
                        {{ props.description }}
                    </p>
                </div>

                <div class="mt-7 flex flex-col gap-3 sm:flex-row sm:justify-center">
                    <ButtonPrimary
                        type="button"
                        variant="secondary"
                        class="!min-h-12 w-full justify-center !rounded-full !border-0 !bg-[#F3F4F6] !px-3 !text-sm !text-[#344054] !shadow-none hover:!bg-[#E9EBEF] sm:min-w-0 sm:flex-1"
                        @click="close"
                    >
                        {{ props.cancelText || $t('Cancel') }}
                    </ButtonPrimary>
                    <ButtonPrimary
                        type="button"
                        :class="[
                            '!min-h-12 w-full justify-center !rounded-full !px-3 !text-sm !text-white sm:min-w-0 sm:flex-1',
                            props.intent === 'danger'
                                ? '!bg-[#F43F5E] hover:!bg-[#E11D48]'
                                : '',
                        ]"
                        @click="confirm"
                    >
                        {{ props.confirmText || $t('Confirm') }}
                    </ButtonPrimary>
                </div>
            </section>
        </div>
    </Teleport>
</template>
