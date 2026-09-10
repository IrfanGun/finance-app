<script setup>
import { onMounted, onUnmounted, ref } from 'vue';

const props = defineProps({
    options: { type: Array, default: () => [] },
    placeholder: { type: String, default: 'Select option' },
    optionLabel: { type: String, default: 'label' },
    optionValue: { type: String, default: 'value' },
    required: Boolean,
    searchable: Boolean,
    creatable: Boolean,
    createLabel: { type: String, default: 'Create' },
});

const model = defineModel({ default: '' });
const open = ref(false);
const search = ref('');
const root = ref(null);

const selected = () => props.options.find((option) => option[props.optionValue] === model.value);
const label = () => selected()?.[props.optionLabel] ?? props.placeholder;
const filteredOptions = () => props.options.filter((option) => String(option[props.optionLabel]).toLowerCase().includes(search.value.toLowerCase()));
const emit = defineEmits(['create']);
const selectOption = (option) => { model.value = option[props.optionValue]; search.value = ''; open.value = false; };
const createOption = () => { emit('create', search.value); search.value = ''; open.value = false; };
const closeOnOutsideClick = (event) => {
    if (!root.value?.contains(event.target)) open.value = false;
};

onMounted(() => document.addEventListener('click', closeOnOutsideClick));
onUnmounted(() => document.removeEventListener('click', closeOnOutsideClick));
</script>

<template>
    <div ref="root" class="relative mt-2">
        <button type="button" class="flex h-[42px] w-full items-center justify-between rounded-lg border border-slate-300 bg-white px-4 text-left" :aria-expanded="open" @click="open = !open">
            <span :class="selected() ? 'text-[#152338]' : 'text-[#637083]'">{{ label() }}</span>
            <span class="text-xs">⌄</span>
        </button>
        <div v-if="open" class="absolute left-0 right-0 top-full z-50 mt-1 max-h-64 overflow-y-auto rounded-lg border border-slate-200 bg-white py-1 shadow-lg">
            <input v-if="searchable" v-model="search" type="search" placeholder="Search..." class="mx-2 mb-1 h-9 w-[calc(100%-1rem)] rounded-md border border-slate-200 px-3 text-sm outline-none focus:border-[#2457DA]" @click.stop />
            <button v-for="option in filteredOptions()" :key="option[optionValue]" type="button" class="block w-full px-4 py-2 text-left text-sm hover:bg-[#F5F7FA]" @click="selectOption(option)">{{ option[optionLabel] }}</button>
            <button v-if="creatable && search.trim()" type="button" class="block w-full border-t border-slate-100 px-4 py-2 text-left text-sm font-medium text-[#2457DA] hover:bg-[#F5F7FA]" @click="createOption">+ {{ createLabel }} "{{ search }}"</button>
            <p v-if="!filteredOptions().length && !(creatable && search.trim())" class="px-4 py-2 text-sm text-[#637083]">No options available.</p>
        </div>
        <input v-if="required" :value="model" required tabindex="-1" class="pointer-events-none absolute h-0 w-0 opacity-0" aria-hidden="true" />
    </div>
</template>
