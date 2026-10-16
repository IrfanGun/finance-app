<script setup>
const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    cms: {
        type: Boolean,
        default: true,
    },
    label: {
        type: String,
        default: 'Dialog',
    },
});

const emit = defineEmits(['close']);
</script>

<template>
    <Teleport to="body" :disabled="!props.cms">
        <div
            v-if="props.show"
            class="cms-modal fixed inset-0"
            :class="props.cms
                ? 'z-[100] flex items-center justify-center overflow-y-auto bg-[#101828]/55 p-4 backdrop-blur-sm sm:p-6'
                : 'z-30 flex items-end bg-black/30'"
            @click.self="emit('close')"
            @keydown.esc="emit('close')"
        >
            <div
                class="w-full"
                :class="props.cms ? 'flex min-h-full items-center justify-center' : ''"
                role="dialog"
                aria-modal="true"
                :aria-label="props.label"
                @click.self="emit('close')"
            >
                <slot />
            </div>
        </div>
    </Teleport>
</template>
