<script setup>
import { computed } from 'vue';

const props = defineProps({
    type: { type: String, default: 'text' },
    placeholder: { type: String, default: '' },
    required: Boolean,
    variant: { type: String, default: 'default' },
});

const model = defineModel({ type: [String, Number], default: '' });
const inputClass = computed(() => props.variant === 'amount'
    ? 'mt-1 block w-full border-0 bg-transparent p-0 text-[32px] font-semibold text-[#637083] outline-none focus:ring-0'
    : 'mt-2 h-[42px] w-full rounded-lg border border-slate-300 px-4 outline-none focus:border-[#2457DA] focus:ring-2 focus:ring-[#2457DA]/10');
</script>

<template>
    <input v-model="model" :type="type" :placeholder="placeholder" :required="required" :class="inputClass" />
</template>
