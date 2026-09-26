<script setup>
import { Send } from 'lucide-vue-next';

defineProps({
    draft: {
        type: String,
        required: true,
    },
    suggestions: {
        type: Array,
        required: true,
    },
    isTyping: {
        type: Boolean,
        default: false,
    },
    isSetupOpen: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits([
    'send',
    'update:draft',
]);

const updateDraft = (event) => {
    emit('update:draft', event.target.value);
};
</script>

<template>
    <section class="fixed inset-x-0 bottom-[84px] z-20 mx-auto w-full max-w-lg space-y-3 border-t border-[#E9EDF3] bg-white px-6 py-4 shadow-[0_-4px_18px_rgba(21,35,56,0.05)]">
        <div class="flex min-w-0 max-w-full gap-2 overflow-x-auto pb-1 [scrollbar-width:none] [&amp;::-webkit-scrollbar]:hidden">
            <button
                v-for="suggestion in suggestions"
                :key="suggestion"
                type="button"
                class="shrink-0 whitespace-nowrap rounded-full border border-[#DCE6FF] bg-[#F7F9FF] px-3 py-2 text-xs font-medium text-[#2457DA] transition hover:bg-[#EEF3FF] disabled:opacity-50"
                :disabled="isTyping || isSetupOpen"
                @click="emit('send', suggestion)"
            >
                {{ suggestion }}
            </button>
        </div>

        <form
            class="flex min-w-0 items-center gap-2 rounded-2xl bg-[#F5F7FA] p-2"
            @submit.prevent="emit('send')"
        >
            <input
                :value="draft"
                type="text"
                placeholder="Ask about your finances..."
                class="min-w-0 flex-1 border-0 bg-transparent px-2 text-sm text-[#152338] placeholder:text-[#94A0B2] focus:ring-0"
                :disabled="isTyping || isSetupOpen"
                @input="updateDraft"
            />
            <button
                type="submit"
                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#2457DA] text-white transition hover:bg-[#1D43B6] disabled:cursor-not-allowed disabled:opacity-50"
                :disabled="!draft.trim() || isTyping || isSetupOpen"
                aria-label="Send message"
            >
                <Send :size="18" />
            </button>
        </form>

        <p class="text-center text-[10px] text-[#94A0B2]">
            Assistant suggestions are for general guidance only.
        </p>
    </section>
</template>
