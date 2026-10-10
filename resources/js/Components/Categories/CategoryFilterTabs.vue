<script setup>
const props = defineProps({
    modelValue: {
        type: String,
        default: 'all',
    },
});

const emit = defineEmits(['update:modelValue']);
const filters = ['all', 'expense', 'income'];

const selectFilter = (filter) => {
    emit('update:modelValue', filter);
};
</script>

<template>
    <div class="flex gap-3">
        <button
            v-for="filter in filters"
            :key="filter"
            type="button"
            class="rounded-full px-5 py-2.5 text-sm font-semibold capitalize transition"
            :class="props.modelValue === filter
                ? 'bg-app-primary text-white shadow-sm'
                : 'bg-app-primary/5 text-app-primary'"
            :aria-pressed="props.modelValue === filter"
            @click="selectFilter(filter)"
        >
            {{ filter }}
        </button>
    </div>
</template>
