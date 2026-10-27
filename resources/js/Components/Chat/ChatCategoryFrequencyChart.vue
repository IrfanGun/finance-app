<script setup>
import {
    computed,
    nextTick,
    onMounted,
    onUnmounted,
    ref,
    watch,
} from 'vue';

const props = defineProps({
    categories: {
        type: Array,
        required: true,
    },
});

let chart = null;
let apexChartsConstructor = null;

const chartElement = ref(null);
const chartHeight = computed(() =>
    Math.max(190, Math.min(360, props.categories.length * 34 + 64)),
);

const formatCurrency = (amount) =>
    new Intl.NumberFormat('id-ID').format(Number(amount ?? 0));

const chartOptions = computed(() => ({
    series: [
        {
            name: 'Jumlah transaksi',
            data: props.categories.map((category) => category.count),
        },
    ],
    chart: {
        type: 'bar',
        height: chartHeight.value,
        parentHeightOffset: 0,
        toolbar: { show: false },
        animations: { enabled: false },
        fontFamily: 'inherit',
    },
    colors: ['#2457DA'],
    plotOptions: {
        bar: {
            horizontal: true,
            borderRadius: 5,
            barHeight: '56%',
            dataLabels: { position: 'top' },
        },
    },
    dataLabels: {
        enabled: true,
        formatter: (value) => `${value}x`,
        offsetX: 8,
        style: {
            colors: ['#314158'],
            fontSize: '11px',
            fontWeight: 600,
        },
        background: { enabled: false },
    },
    xaxis: {
        categories: props.categories.map((category) => category.name),
        min: 0,
        max: Math.max(...props.categories.map((category) => category.count), 1) + 1,
        tickAmount: 4,
        labels: {
            formatter: (value) => `${Math.round(Number(value))}`,
            style: { colors: '#94A0B2', fontSize: '10px' },
        },
        axisBorder: { show: false },
        axisTicks: { show: false },
        title: {
            text: 'Jumlah transaksi',
            style: {
                color: '#637083',
                fontSize: '10px',
                fontWeight: 500,
            },
        },
    },
    yaxis: {
        labels: {
            maxWidth: 104,
            style: { colors: '#314158', fontSize: '11px' },
        },
    },
    grid: {
        borderColor: '#EEF1F5',
        strokeDashArray: 4,
        padding: { left: 0, right: 26, top: -8, bottom: -4 },
        xaxis: { lines: { show: true } },
        yaxis: { lines: { show: false } },
    },
    legend: { show: false },
    tooltip: {
        y: {
            formatter: (count, { dataPointIndex }) => {
                const category = props.categories[dataPointIndex];

                return `${count} transaksi · Rp ${formatCurrency(category.amount)}`;
            },
        },
    },
    states: {
        hover: { filter: { type: 'darken', value: 0.08 } },
        active: { filter: { type: 'none' } },
    },
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

        await import('apexcharts/bar');

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
    <section
        class="mt-3 overflow-hidden rounded-xl border border-[#EEF1F5] bg-[#FBFCFF] px-2 py-2"
        aria-label="Grafik jumlah transaksi per kategori"
    >
        <div ref="chartElement" class="w-full"></div>

        <ul class="mt-2 divide-y divide-[#EEF1F5] rounded-lg bg-white px-3">
            <li
                v-for="category in categories"
                :key="category.name"
                class="flex items-center justify-between gap-3 py-2 text-xs"
            >
                <span class="min-w-0 truncate text-[#637083]">
                    {{ category.name }} · {{ category.count }}x
                </span>
                <strong class="shrink-0 font-semibold text-[#314158]"> {{ $t("Rp") }} {{ formatCurrency(category.amount) }}
                </strong>
            </li>
        </ul>
    </section>
</template>
