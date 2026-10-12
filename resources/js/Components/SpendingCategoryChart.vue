<script setup>
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue';

const props = defineProps({
    categories: { type: Array, default: () => [] },
});

const showAllCategories = ref(false);
const chartElement = ref(null);
let chart = null;
let apexChartsConstructor = null;

const formatCurrency = (amount) =>
    new Intl.NumberFormat('id-ID').format(Number(amount ?? 0));
const formatCompactCurrency = (amount) =>
    new Intl.NumberFormat('id-ID', {
        notation: 'compact',
        maximumFractionDigits: 1,
    }).format(Number(amount ?? 0));

const spendingCategories = computed(() => {
    const total = props.categories.reduce(
        (sum, category) => sum + Number(category.amount),
        0,
    );

    return props.categories.map((category) => ({
        ...category,
        amount: Number(category.amount),
        percentage: total
            ? Math.round((Number(category.amount) / total) * 100)
            : 0,
    }));
});

const spendingTotal = computed(() =>
    spendingCategories.value.reduce((sum, category) => sum + category.amount, 0),
);
const visibleCategories = computed(() => spendingCategories.value.slice(0, 5));
const chartOptions = computed(() => ({
    series: spendingCategories.value.map((category) => category.amount),
    chart: {
        type: 'donut',
        height: 208,
        parentHeightOffset: 0,
        toolbar: { show: false },
        animations: { enabled: false },
    },
    labels: spendingCategories.value.map((category) => category.name),
    colors: spendingCategories.value.map((category) => category.color),
    plotOptions: {
        pie: {
            borderRadius: 8,
            spacing: 1.5,
            donut: { size: '62%' },
        },
    },
    stroke: { width: 0 },
    dataLabels: { enabled: false },
    legend: { show: false },
    tooltip: {
        y: {
            formatter: (amount) => `Rp ${formatCurrency(amount)}`,
        },
    },
    responsive: [
        {
            breakpoint: 768,
            options: { chart: { height: 176 } },
        },
    ],
}));

const renderChart = async () => {
    await nextTick();

    if (!chartElement.value) {
        chart?.destroy();
        chart = null;

        return;
    }

    if (chart) {
        await chart.updateOptions(chartOptions.value);

        return;
    }

    if (!apexChartsConstructor) {
        const apexChartsModule = await import('apexcharts/core');

        await import('apexcharts/pie');

        apexChartsConstructor = apexChartsModule.default;
    }

    chart = new apexChartsConstructor(chartElement.value, chartOptions.value);
    await chart.render();
};

watch(chartOptions, renderChart, { deep: true });

onMounted(renderChart);
onUnmounted(() => chart?.destroy());
</script>

<template>
    <div class="w-full">
        <div v-if="spendingCategories.length" class="mt-5 flex items-center gap-2">
            <div
                class="relative h-44 w-44 shrink-0 rounded-full md:h-52 md:w-52"
                aria-hidden="true"
            >
                <div ref="chartElement" class="h-full w-full"></div>
                <div class="absolute inset-8 flex flex-col items-center justify-center rounded-full bg-white md:inset-10">
                    <span class="text-[10px] text-[#637083]">Total</span>
                    <strong class="max-w-full truncate px-1 text-xs">
                        Rp {{ formatCompactCurrency(spendingTotal) }}
                    </strong>
                </div>
            </div>

            <div class="min-w-0 flex-1">
                <ul class="space-y-3">
                    <li
                        v-for="category in visibleCategories"
                        :key="category.id"
                        class="min-w-0"
                    >
                        <div class="flex min-w-0 items-center justify-between gap-1 text-sm">
                            <div class="flex min-w-0 items-center gap-2">
                                <span
                                    class="h-2.5 w-2.5 shrink-0 rounded-full"
                                    :style="{ backgroundColor: category.color }"
                                ></span>
                                <span class="truncate">{{ category.name }}</span>
                            </div>
                            <strong class="shrink-0">{{ category.percentage }}%</strong>
                        </div>
                        <p class="truncate pl-4 text-xs text-[#637083]">
                            Rp {{ formatCurrency(category.amount) }}
                        </p>
                    </li>
                </ul>
                <button
                    v-if="spendingCategories.length > 5"
                    type="button"
                    class="mt-3 text-sm font-medium text-[#2457DA]"
                    @click="showAllCategories = true"
                >
                    View all categories
                </button>
            </div>
        </div>
        <p v-else class="mt-4 text-sm text-[#637083]">No spending recorded yet.</p>

        <div
            v-if="showAllCategories"
            class="fixed inset-0 z-40 flex items-end justify-center bg-black/40 p-4 sm:items-center"
            @click.self="showAllCategories = false"
        >
            <section
                class="flex max-h-[85vh] w-full max-w-md flex-col overflow-hidden rounded-2xl bg-white text-[#152338] shadow-xl"
                role="dialog"
                aria-modal="true"
                aria-labelledby="spending-categories-title"
            >
                <header class="flex items-center justify-between border-b border-[#EEF1F5] px-5 py-4">
                    <div>
                        <h2 id="spending-categories-title" class="text-lg font-semibold">
                            All spending categories
                        </h2>
                        <p class="mt-1 text-sm text-[#637083]">
                            {{ spendingCategories.length }} categories
                        </p>
                    </div>
                    <button
                        type="button"
                        class="rounded-lg px-3 py-2 text-sm font-medium text-[#2457DA]"
                        @click="showAllCategories = false"
                    >
                        Close
                    </button>
                </header>
                <ul class="space-y-4 overflow-y-auto px-5 py-4">
                    <li
                        v-for="category in spendingCategories"
                        :key="category.id"
                        class="flex items-center justify-between gap-3"
                    >
                        <div class="flex min-w-0 items-center gap-3">
                            <span
                                class="h-3 w-3 shrink-0 rounded-full"
                                :style="{ backgroundColor: category.color }"
                            ></span>
                            <div class="min-w-0">
                                <p class="truncate text-sm font-medium">{{ category.name }}</p>
                                <p class="mt-1 text-xs text-[#637083]">
                                    Rp {{ formatCurrency(category.amount) }}
                                </p>
                            </div>
                        </div>
                        <strong class="shrink-0 text-sm">{{ category.percentage }}%</strong>
                    </li>
                </ul>
            </section>
        </div>
    </div>
</template>
