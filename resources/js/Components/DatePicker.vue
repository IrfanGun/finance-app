<script setup>
import { computed, ref } from 'vue';

defineProps({
    placeholder: { type: String, default: 'Select date' },
    required: Boolean,
});

const model = defineModel({ type: String, default: '' });
const open = ref(false);
const draft = ref(model.value);
const visibleMonth = ref(new Date());

const monthLabel = computed(() => visibleMonth.value.toLocaleDateString('en-US', { month: 'long', year: 'numeric' }));
const calendarDays = computed(() => {
    const year = visibleMonth.value.getFullYear();
    const month = visibleMonth.value.getMonth();
    const firstDay = new Date(year, month, 1).getDay();
    const daysInMonth = new Date(year, month + 1, 0).getDate();

    return [
        ...Array.from({ length: firstDay }, () => null),
        ...Array.from({ length: daysInMonth }, (_, index) => `${year}-${String(month + 1).padStart(2, '0')}-${String(index + 1).padStart(2, '0')}`),
    ];
});

const openPicker = () => {
    draft.value = model.value;
    visibleMonth.value = model.value ? new Date(`${model.value}T00:00:00`) : new Date();
    open.value = true;
};

const confirm = () => {
    model.value = draft.value;
    open.value = false;
};

const changeMonth = (offset) => {
    visibleMonth.value = new Date(visibleMonth.value.getFullYear(), visibleMonth.value.getMonth() + offset, 1);
};
</script>

<template>
    <button type="button" class="mt-2 flex h-[42px] w-full items-center justify-between rounded-lg border border-slate-300 bg-white px-4 text-left" :class="model ? 'text-[#152338]' : 'text-[#637083]'" @click="openPicker">
        <span>{{ model || placeholder }}</span>
        <span class="text-xs">⌄</span>
    </button>

    <div v-if="open" class="fixed inset-0 z-50 flex items-center justify-center bg-black/30 px-4" @click.self="open = false">
        <div class="mx-auto w-full max-w-sm rounded-2xl bg-white px-6 pb-6 pt-5 text-[#152338] shadow-xl">
            <div class="mx-auto mb-5 h-1 w-9 rounded-full bg-[#D5DBE5]"></div>
            <div class="flex items-center justify-between"><h2 class="text-xl font-semibold">Select date</h2><button type="button" class="text-2xl text-[#637083]" @click="open = false">×</button></div>
            <div class="mt-6 flex items-center justify-between"><button type="button" class="rounded-lg px-3 py-1 text-xl text-[#637083] hover:bg-[#F5F7FA]" @click="changeMonth(-1)">‹</button><strong class="text-base">{{ monthLabel }}</strong><button type="button" class="rounded-lg px-3 py-1 text-xl text-[#637083] hover:bg-[#F5F7FA]" @click="changeMonth(1)">›</button></div>
            <div class="mt-4 grid grid-cols-7 text-center text-xs font-medium text-[#637083]"><span v-for="day in ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa']" :key="day">{{ day }}</span></div>
            <div class="mt-2 grid grid-cols-7 gap-y-2 text-center"><button v-for="(day, index) in calendarDays" :key="`${day}-${index}`" type="button" class="mx-auto flex h-9 w-9 items-center justify-center rounded-full text-sm" :class="day === draft ? 'bg-[#2457DA] text-white' : 'hover:bg-[#EEF3FF]'" :disabled="!day" @click="draft = day">{{ day ? Number(day.slice(-2)) : '' }}</button></div>
            <button type="button" class="mt-6 h-[53px] w-full rounded-xl bg-[#2457DA] text-white" @click="confirm">Use this date</button>
        </div>
    </div>
</template>
