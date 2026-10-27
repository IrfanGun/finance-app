<script setup>
import { computed, ref } from 'vue';

const props = defineProps({
    days: { type: Array, default: () => [] },
});

const weekdayLabels = ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'];
const today = new Date().toLocaleDateString('sv-SE');
const selectedDate = ref(today);

const formatCurrency = (amount) =>
    new Intl.NumberFormat('id-ID').format(Number(amount ?? 0));
const formatDayDate = (date) =>
    new Intl.DateTimeFormat('id-ID', {
        day: 'numeric',
        month: 'long',
    }).format(new Date(`${date}T00:00:00`));

const chartDays = computed(() => {
    const maximum = Math.max(...props.days.map((day) => Number(day.total)), 0);

    return props.days.map((day, index) => ({
        ...day,
        label: weekdayLabels[index],
        barHeight: maximum > 0
            ? Math.max((Number(day.total) / maximum) * 100, day.total > 0 ? 8 : 4)
            : 4,
    }));
});

const selectedDay = computed(() =>
    chartDays.value.find((day) => day.date === selectedDate.value)
        ?? chartDays.value.find((day) => day.total > 0)
        ?? chartDays.value[0],
);
const weeklyTotal = computed(() =>
    chartDays.value.reduce((total, day) => total + Number(day.total), 0),
);

const selectDay = (day) => {
    selectedDate.value = day.date;
};
</script>

<template>
    <div class="w-full">
        <div v-if="chartDays.length">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-xs text-budget-muted">{{ $t("Total minggu ini") }}</p>
                    <p class="mt-1 text-lg font-semibold">{{ $t("Rp") }} {{ formatCurrency(weeklyTotal) }}</p>
                </div>
                <p class="pt-1 text-xs text-budget-muted">Senin – Minggu</p>
            </div>

            <div class="relative mt-4 h-36 border-b border-budget-border">
                <div class="pointer-events-none absolute inset-x-0 top-0 border-t border-dashed border-budget-border"></div>
                <div class="pointer-events-none absolute inset-x-0 top-1/2 border-t border-dashed border-budget-border"></div>
                <div class="flex h-full items-end justify-between gap-1 px-1">
                    <button
                        v-for="day in chartDays"
                        :key="day.date"
                        type="button"
                        class="group relative z-10 flex h-full min-w-0 flex-1 flex-col items-center justify-end gap-2 rounded-t-lg pt-4 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-budget-primary"
                        :aria-label="`${day.label}, ${formatDayDate(day.date)}: Rp ${formatCurrency(day.total)}`"
                        :aria-pressed="selectedDay?.date === day.date"
                        @click="selectDay(day)"
                    >
                        <span
                            v-if="selectedDay?.date === day.date && day.total > 0"
                            class="absolute top-0 z-20 whitespace-nowrap rounded-md bg-budget-heading px-2 py-1 text-[10px] font-medium text-white shadow-sm"
                        > {{ $t("Rp") }} {{ formatCurrency(day.total) }}
                        </span>
                        <span class="flex w-full flex-1 items-end justify-center">
                            <span
                                class="block w-6 rounded-t-full transition-[height,background-color] duration-500 ease-out sm:w-8"
                                :class="selectedDay?.date === day.date ? 'bg-budget-primary' : 'bg-budget-primary/30 group-hover:bg-budget-primary/50'"
                                :style="{ height: `${day.barHeight}%` }"
                            ></span>
                        </span>
                        <span
                            class="pb-2 text-[10px] font-medium"
                            :class="selectedDay?.date === day.date ? 'text-budget-primary' : 'text-budget-muted'"
                        >
                            {{ day.label }}
                        </span>
                    </button>
                </div>
            </div>

            <section v-if="selectedDay" class="mt-5" aria-live="polite">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <p class="text-sm font-semibold">{{ selectedDay.label }}, {{ formatDayDate(selectedDay.date) }}</p>
                        <p class="mt-0.5 text-xs text-budget-muted">{{ selectedDay.transactions.length }} {{ $t("transaksi") }}</p>
                    </div>
                    <strong class="shrink-0 text-sm">{{ $t("Rp") }} {{ formatCurrency(selectedDay.total) }}</strong>
                </div>

                <ul v-if="selectedDay.transactions.length" class="mt-3 divide-y divide-budget-border">
                    <li
                        v-for="transaction in selectedDay.transactions"
                        :key="transaction.id"
                        class="flex items-center justify-between gap-3 py-3 first:pt-0 last:pb-0"
                    >
                        <div class="min-w-0">
                            <p class="truncate text-sm font-medium">{{ transaction.title }}</p>
                            <p class="mt-0.5 truncate text-xs text-budget-muted">{{ transaction.category }}</p>
                        </div>
                        <strong class="shrink-0 text-sm">{{ $t("Rp") }} {{ formatCurrency(transaction.amount) }}</strong>
                    </li>
                </ul>
                <p v-else class="mt-3 rounded-xl bg-budget-control-surface px-3 py-3 text-sm text-budget-muted"> {{ $t("Belum ada pengeluaran pada hari ini.") }} </p>
            </section>
        </div>
        <p v-else class="mt-4 text-sm text-budget-muted">{{ $t("Belum ada pengeluaran minggu ini.") }}</p>
    </div>
</template>
