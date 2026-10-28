<script setup>
import {
    computed,
    nextTick,
    onMounted,
    onUnmounted,
    ref,
    watch,
} from 'vue';
import { useI18n } from 'vue-i18n';

const props = defineProps({
    plans: { type: Array, default: () => [] },
    selectedCurrency: { type: String, required: true },
    selectedYear: { type: Number, required: true },
    selectedMonth: { type: Number, required: true },
    selectedMonthLabel: { type: String, required: true },
    formatCurrency: { type: Function, required: true },
});

const { t } = useI18n();
const chartElement = ref(null);
let chart = null;
let apexChartsConstructor = null;

const totalsByMonth = computed(() => props.plans
    .filter((plan) => plan.type === 'spending' && plan.currency === props.selectedCurrency)
    .flatMap((plan) => plan.periods ?? [])
    .reduce((totals, period) => {
        const monthKey = period.starts_on?.slice(0, 7);

        if (!monthKey) {
            return totals;
        }

        totals[monthKey] ??= { allocated: 0, spent: 0 };
        totals[monthKey].allocated += Number(period.allocated_amount ?? 0);
        totals[monthKey].spent += Number(period.spent ?? 0);

        return totals;
    }, {}));

const monthlyHistory = computed(() => Array.from({ length: 6 }, (_, index) => {
    const date = new Date(Date.UTC(props.selectedYear, props.selectedMonth - 5 + index, 1));
    const monthKey = `${date.getUTCFullYear()}-${String(date.getUTCMonth() + 1).padStart(2, '0')}`;

    return {
        label: new Intl.DateTimeFormat('id-ID', {
            month: 'short',
            timeZone: 'UTC',
        }).format(date),
        allocated: totalsByMonth.value[monthKey]?.allocated ?? 0,
        spent: totalsByMonth.value[monthKey]?.spent ?? 0,
    };
}));

const realizationPercentages = computed(() => monthlyHistory.value.map((month) =>
    month.allocated > 0 ? (month.spent / month.allocated) * 100 : 0,
));

const historyChartOptions = computed(() => ({
    series: [
        {
            name: t('Dana teralokasikan'),
            type: 'column',
            data: monthlyHistory.value.map((month) => month.allocated),
        },
        {
            name: t('Dana terealisasikan'),
            type: 'column',
            data: monthlyHistory.value.map((month) => month.spent),
        },
        {
            name: t('Persentase realisasi'),
            type: 'line',
            data: realizationPercentages.value,
        },
    ],
    chart: {
        type: 'line',
        height: 280,
        parentHeightOffset: 0,
        toolbar: { show: false },
        animations: { enabled: false },
        fontFamily: 'inherit',
    },
    colors: ['#1768EB', '#43A47A', '#E68A00'],
    plotOptions: {
        bar: {
            columnWidth: '48%',
            borderRadius: 4,
            borderRadiusApplication: 'end',
        },
    },
    dataLabels: {
        enabled: true,
        enabledOnSeries: [2],
        formatter: (value) => `${Math.round(Number(value))}%`,
        offsetY: -7,
        style: {
            colors: ['#A65F00'],
            fontSize: '11px',
            fontWeight: 600,
        },
        background: { enabled: false },
    },
    xaxis: {
        categories: monthlyHistory.value.map((month) => month.label),
        axisBorder: { show: false },
        axisTicks: { show: false },
        labels: {
            style: { colors: '#687A9B', fontSize: '11px' },
        },
    },
    yaxis: [
        {
            seriesName: [t('Dana teralokasikan'), t('Dana terealisasikan')],
            labels: {
                formatter: (amount) => new Intl.NumberFormat('id-ID', {
                    notation: 'compact',
                    maximumFractionDigits: 1,
                }).format(Number(amount)),
                style: { colors: '#8796AF', fontSize: '10px' },
            },
        },
        {
            seriesName: t('Persentase realisasi'),
            opposite: true,
            min: 0,
            max: Math.max(100, Math.ceil(Math.max(...realizationPercentages.value) / 20) * 20),
            tickAmount: 5,
            labels: {
                formatter: (percentage) => `${Math.round(Number(percentage))}%`,
                style: { colors: '#A65F00', fontSize: '10px' },
            },
        },
    ],
    stroke: {
        curve: 'smooth',
        width: [0, 0, 3],
    },
    markers: {
        size: [0, 0, 5],
        strokeWidth: 0,
        hover: { size: 7 },
    },
    grid: {
        borderColor: '#EEF1F5',
        strokeDashArray: 4,
        padding: { left: 0, right: 4, top: 0, bottom: -4 },
    },
    legend: {
        position: 'top',
        horizontalAlign: 'left',
        fontSize: '11px',
        labels: { colors: '#52627E' },
        markers: { width: 8, height: 8, radius: 8 },
        itemMargin: { horizontal: 8 },
    },
    tooltip: {
        y: {
            formatter: (amount, { seriesIndex }) => seriesIndex === 2
                ? `${Math.round(Number(amount))}%`
                : props.formatCurrency(amount, props.selectedCurrency),
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
        await chart.updateOptions(historyChartOptions.value);

        return;
    }

    if (!apexChartsConstructor) {
        const apexChartsModule = await import('apexcharts/core');

        await import('apexcharts/bar');
        await import('apexcharts/line');

        apexChartsConstructor = apexChartsModule.default;
    }

    chart = new apexChartsConstructor(chartElement.value, historyChartOptions.value);
    await chart.render();
};

watch(historyChartOptions, renderChart, { deep: true });

onMounted(renderChart);
onUnmounted(() => chart?.destroy());
</script>

<template>
    <section class="rounded-2xl border border-[#E4EAF4] bg-white p-5 sm:p-6">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h2 class="text-xl font-semibold tracking-tight text-[#111B39]">
                    {{ $t('Riwayat alokasi dan realisasi') }}
                </h2>
                <p class="mt-1 text-sm text-[#687A9B]">
                    {{ $t('Alokasi, realisasi, dan persentase per bulan') }}
                </p>
            </div>
            <span class="rounded-lg bg-[#F4F7FB] px-2.5 py-1 text-xs font-medium capitalize text-[#687A9B]">
                {{ monthlyHistory[0]?.label }} – {{ selectedMonthLabel }}
            </span>
        </div>
        <div
            ref="chartElement"
            class="mt-3 w-full"
            :aria-label="$t('Grafik riwayat dana teralokasikan dan terealisasikan')"
        />
    </section>
</template>
