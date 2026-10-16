<script setup>
defineProps({
    type: {
        type: String,
        default: 'submit',
    },
    disabled: {
        type: Boolean,
        default: false,
    },
    variant: {
        type: String,
        default: 'default',
    },
});
</script>

<template>
    <button
        :type="type"
        :disabled="disabled"
        class="button inline-flex items-center"
        :class="{ 'cms-primary-button': variant === 'cms' }"
    >
        <slot />
    </button>
</template>

<style scoped>
.button {
    background: #2457da;
    color: #fff;
    padding: 16px 30px;
    border: none;
    border-radius: 12px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    box-shadow:
        inset 0 1px 1px rgba(255, 255, 255, 0.08),
        inset 0 -2px 3px rgba(10, 35, 110, 0.25);
    transition:
        background-color 350ms ease,
        box-shadow 350ms ease,
        transform 250ms ease;
}

.button:hover:not(:disabled) {
    background: #285bdd;
    box-shadow:
        inset 0 1px 1px rgba(255, 255, 255, 0.09),
        inset 0 -2px 3px rgba(10, 35, 110, 0.22);
}

.button:active:not(:disabled) {
    transform: translateY(1px);
    box-shadow:
        inset 0 1px 2px rgba(10, 35, 110, 0.2),
        inset 0 -1px 2px rgba(10, 35, 110, 0.15);
}

.button:focus-visible {
    outline: 2px solid #285bdd;
    outline-offset: 3px;
}

.button:disabled {
    cursor: not-allowed;
    opacity: 0.6;
}

.cms-primary-button {
    width: 100%;
    min-height: 44px;
    justify-content: center;
    padding: 10px 24px;
    font-size: 14px;
}

@media (prefers-reduced-motion: reduce) {
    .button {
        transition: none;
    }
}
</style>
