<script setup>
defineProps({
    type: {
        type: String,
        default: 'button',
    },
    disabled: {
        type: Boolean,
        default: false,
    },
    variant: {
        type: String,
        default: 'primary',
    },
});
</script>

<template>
    <button
        :type="type"
        :disabled="disabled"
        class="button-primary"
        :class="`button-primary--${variant}`"
    >
        <slot />
    </button>
</template>

<style scoped>
.button-primary {
    min-height: 44px;
    padding: 10px 20px;
    border-radius: 12px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition:
        background-color 350ms ease,
        box-shadow 350ms ease,
        transform 250ms ease;
}

.button-primary--primary {
    border: none;
    background: #2457DA;
    color: #fff;
    box-shadow:
        inset 0 1px 1px rgba(255, 255, 255, 0.08),
        inset 0 -2px 3px rgba(10, 35, 110, 0.25);
}

.button-primary--primary:hover:not(:disabled) {
    background: #285BDD;
    box-shadow:
        inset 0 1px 1px rgba(255, 255, 255, 0.09),
        inset 0 -2px 3px rgba(10, 35, 110, 0.22);
}

.button-primary--primary:active:not(:disabled) {
    transform: translateY(1px);
    box-shadow:
        inset 0 1px 2px rgba(10, 35, 110, 0.2),
        inset 0 -1px 2px rgba(10, 35, 110, 0.15);
}

.button-primary--secondary {
    border: 1px solid #CBD5E1;
    background: #fff;
    color: #314158;
    box-shadow: none;
}

.button-primary--secondary:hover:not(:disabled) {
    background: #F8FAFC;
}

.button-primary--secondary:active:not(:disabled) {
    transform: translateY(1px);
}

.button-primary:focus-visible {
    outline: 3px solid rgba(36, 87, 218, 0.3);
    outline-offset: 3px;
}

.button-primary:disabled {
    cursor: not-allowed;
    opacity: 0.6;
}

@media (prefers-reduced-motion: reduce) {
    .button-primary {
        transition: none;
    }
}
</style>
