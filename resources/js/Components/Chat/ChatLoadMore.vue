<script setup>
defineProps({
    hasMore: {
        type: Boolean,
        default: false,
    },
    isLoading: {
        type: Boolean,
        default: false,
    },
    error: {
        type: String,
        default: '',
    },
});

const emit = defineEmits(['load-more']);
</script>

<template>
    <div v-if="hasMore || error" class="flex flex-col items-center gap-2">
        <button
            v-if="hasMore"
            type="button"
            class="rounded-full bg-white px-4 py-2 text-xs font-semibold text-[#2457DA] shadow-sm ring-1 ring-[#DCE6FF] transition hover:bg-[#F5F7FF] disabled:cursor-wait disabled:opacity-60"
            :disabled="isLoading"
            @click="emit('load-more')"
        >
            {{ isLoading ? 'Loading...' : 'Load more' }}
        </button>
        <p v-if="error" class="text-xs text-red-600">
            {{ error }}
        </p>
    </div>
</template>
