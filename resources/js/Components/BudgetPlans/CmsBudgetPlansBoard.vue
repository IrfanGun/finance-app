<script setup>
import { computed, ref, watch } from 'vue';
import { Link } from '@inertiajs/vue3';
import {
    ArrowRight,
    CalendarDays,
    ChartNoAxesCombined,
    ChevronDown,
    ChevronLeft,
    ChevronRight,
    CircleAlert,
    Wallet,
} from 'lucide-vue-next';
import BudgetPlanHistoryChart from '@/Components/BudgetPlans/BudgetPlanHistoryChart.vue';
import BudgetPlanList from '@/Components/BudgetPlans/BudgetPlanList.vue';
import BudgetPlanMonthlySummary from '@/Components/BudgetPlans/BudgetPlanMonthlySummary.vue';

const props = defineProps({
    plans: { type: Array, default: () => [] },
    totals: { type: Object, default: () => ({}) },
    availableCurrencies: { type: Array, default: () => [] },
    selectedCurrency: { type: String, required: true },
    activePlanCount: { type: Number, default: 0 },
    createPlanUrl: { type: String, required: true },
    showPlanUrl: { type: Function, required: true },
    periodUsagePercentage: { type: Function, required: true },
    formatCurrency: { type: Function, required: true },
    planStatusLabel: { type: Function, required: true },
    planIcon: { type: Function, required: true },
    planIconStyle: { type: Function, required: true },
});

const emit = defineEmits(['edit', 'delete', 'update:selectedCurrency']);
const monthPickerOpen = ref(false);
const currentCalendarParts = new Intl.DateTimeFormat('en', {
    year: 'numeric',
    month: 'numeric',
    timeZone: 'Asia/Jakarta',
}).formatToParts(new Date());
const currentYear = Number(currentCalendarParts.find((part) => part.type === 'year')?.value);
const selectedYear = ref(currentYear);
const selectedMonth = ref(Number(currentCalendarParts.find((part) => part.type === 'month')?.value) - 1);

const monthNames = Array.from({ length: 12 }, (_, month) => new Intl.DateTimeFormat('id-ID', {
    month: 'long',
    timeZone: 'UTC',
}).format(new Date(Date.UTC(2020, month, 1))));

const availableYears = computed(() => {
    const planYears = props.plans.flatMap((plan) => (plan.periods ?? [])
        .map((period) => Number(period.starts_on?.slice(0, 4)))
        .filter((year) => Number.isFinite(year)));
    const nearbyYears = Array.from({ length: 11 }, (_, index) => currentYear - 5 + index);

    return [...new Set([...planYears, ...nearbyYears])]
        .sort((firstYear, secondYear) => secondYear - firstYear);
});

const selectedMonthLabel = computed(() => new Intl.DateTimeFormat('id-ID', {
    month: 'long',
    year: 'numeric',
    timeZone: 'UTC',
}).format(new Date(Date.UTC(selectedYear.value, selectedMonth.value, 1))));

const selectedMonthShortLabel = computed(() => new Intl.DateTimeFormat('id-ID', {
    month: 'short',
    year: 'numeric',
    timeZone: 'UTC',
}).format(new Date(Date.UTC(selectedYear.value, selectedMonth.value, 1))));

const periodForSelectedMonth = (plan) => {
    const monthStart = `${selectedYear.value}-${String(selectedMonth.value + 1).padStart(2, '0')}-01`;
    const monthEnd = new Date(Date.UTC(selectedYear.value, selectedMonth.value + 1, 0))
        .toISOString()
        .slice(0, 10);

    return plan.periods?.find((period) => period.starts_on <= monthEnd && period.ends_on >= monthStart)
        ?? null;
};

const isPlanScheduledForSelectedMonth = (plan) => {
    if (plan.type !== 'spending') {
        return true;
    }

    const selectedMonthKey = `${selectedYear.value}-${String(selectedMonth.value + 1).padStart(2, '0')}`;
    const planStartMonthKey = plan.starts_on?.slice(0, 7);

    if (!plan.is_recurring) {
        return planStartMonthKey === selectedMonthKey;
    }

    if (plan.status !== 'active' && !periodForSelectedMonth(plan)) {
        return false;
    }

    const [startYear, startMonth, startDay] = plan.starts_on.split('-').map(Number);
    const monthsSinceStart = (selectedYear.value - startYear) * 12
        + selectedMonth.value
        - (startMonth - 1);
    const interval = Math.max(Number(plan.interval ?? 1), 1);

    if (monthsSinceStart < 0 || monthsSinceStart % interval !== 0) {
        return false;
    }

    const lastDayOfSelectedMonth = new Date(Date.UTC(
        selectedYear.value,
        selectedMonth.value + 1,
        0,
    )).getUTCDate();
    const occurrenceDate = `${selectedMonthKey}-${String(Math.min(startDay, lastDayOfSelectedMonth)).padStart(2, '0')}`;

    return !plan.ends_on || occurrenceDate <= plan.ends_on;
};

const plansAwaitingFunding = computed(() => props.plans.filter((plan) =>
    plan.type === 'spending'
        && isPlanScheduledForSelectedMonth(plan)
        && periodForSelectedMonth(plan)?.status === 'awaiting_funding',
));

const monthlyFundingDetails = computed(() => {
    const months = new Map();
    const selectedMonthKey = `${selectedYear.value}-${String(selectedMonth.value + 1).padStart(2, '0')}`;

    props.plans
        .filter((plan) => plan.type === 'spending' && plan.currency === props.selectedCurrency)
        .forEach((plan) => {
            (plan.periods ?? [])
                .filter((period) => period.starts_on?.slice(0, 7) <= selectedMonthKey)
                .forEach((period) => {
                    const remainingAmount = Math.max(Number(period.remaining ?? 0), 0);

                    if (remainingAmount <= 0) {
                        return;
                    }

                    const monthKey = period.starts_on.slice(0, 7);
                    const month = months.get(monthKey) ?? {
                        key: monthKey,
                        label: new Intl.DateTimeFormat('id-ID', {
                            month: 'long',
                            year: 'numeric',
                            timeZone: 'UTC',
                        }).format(new Date(`${monthKey}-01T00:00:00Z`)),
                        shortLabel: new Intl.DateTimeFormat('id-ID', {
                            month: 'short',
                            year: 'numeric',
                            timeZone: 'UTC',
                        }).format(new Date(`${monthKey}-01T00:00:00Z`)),
                        remainingAmount: 0,
                    };

                    month.remainingAmount += remainingAmount;
                    months.set(monthKey, month);
                });
        });

    return [...months.values()]
        .sort((first, second) => second.key.localeCompare(first.key));
});

const fundingThemes = [
    {
        card: 'bg-gradient-to-br from-[#0867F2] via-[#1265EC] to-[#2457DA] text-white',
        text: 'text-white/80',
        mutedText: 'text-white/75',
        icon: 'bg-white/15',
        pill: 'bg-white/15 text-white/90',
        button: '!border-white/35 !bg-white/15 !text-white hover:!bg-white/25',
        decoration: 'bg-white/[0.07]',
        dot: '!bg-[#1768EB]',
        layer: 'bg-[#DCE8FF]',
    },
    {
        card: 'bg-gradient-to-br from-[#FFF0B8] to-[#F6D46D] text-[#28364D]',
        text: 'text-[#52627E]',
        mutedText: 'text-[#52627E]/80',
        icon: 'bg-white/50',
        pill: 'bg-white/55 text-[#344563]',
        button: '!border-[#9C7A24]/40 !bg-white/45 !text-[#344563] hover:!bg-white/70',
        decoration: 'bg-white/25',
        dot: '!bg-[#D9A822]',
        layer: 'bg-[#FFF0B8]',
    },
    {
        card: 'bg-gradient-to-br from-[#D9F8E8] to-[#AEEFCB] text-[#243B34]',
        text: 'text-[#48675B]',
        mutedText: 'text-[#48675B]/80',
        icon: 'bg-white/50',
        pill: 'bg-white/55 text-[#345448]',
        button: '!border-[#4D8C70]/35 !bg-white/45 !text-[#345448] hover:!bg-white/70',
        decoration: 'bg-white/30',
        dot: '!bg-[#42A87C]',
        layer: 'bg-[#D9F8E8]',
    },
    {
        card: 'bg-gradient-to-br from-[#FFE8F0] to-[#F8C7D8] text-[#49313D]',
        text: 'text-[#795363]',
        mutedText: 'text-[#795363]/80',
        icon: 'bg-white/50',
        pill: 'bg-white/55 text-[#614454]',
        button: '!border-[#A86E87]/35 !bg-white/45 !text-[#614454] hover:!bg-white/70',
        decoration: 'bg-white/30',
        dot: '!bg-[#CF7298]',
        layer: 'bg-[#FFE8F0]',
    },
];

const fundingSlides = computed(() => {
    const availableSlide = {
        key: 'available',
        type: 'available',
        label: selectedMonthLabel.value,
        shortLabel: selectedMonthShortLabel.value,
        theme: fundingThemes[0],
    };
    const months = monthlyFundingDetails.value;
    const visibleMonths = months.length <= 9
        ? months
        : [
            ...months.slice(0, 8),
            {
                key: 'accumulated',
                type: 'accumulated',
                label: 'Akumulasi sisa budget',
                monthCount: months.length - 8,
                remainingAmount: months
                    .slice(8)
                    .reduce((total, month) => total + month.remainingAmount, 0),
            },
        ];

    return [
        availableSlide,
        ...visibleMonths.map((month, index) => ({
            ...month,
            type: month.type ?? 'month',
            theme: fundingThemes[(index + 1) % fundingThemes.length],
        })),
    ];
});

const activeFundingSlide = ref(0);
const fundingTouchStartX = ref(null);
const pastelLayerThemes = computed(() => {
    const layerPositions = [
        'left-7 right-7 top-0',
        'left-5 right-5 top-2',
        'left-3 right-3 top-4',
    ];
    const layerCount = Math.min(3, fundingSlides.value.length - 1);

    return Array.from({ length: layerCount }, (_, index) => {
        const positionIndex = 3 - layerCount + index;
        const slideOffset = layerCount - index;
        const slideIndex = (activeFundingSlide.value + slideOffset) % fundingSlides.value.length;

        return {
            ...fundingSlides.value[slideIndex].theme,
            position: layerPositions[positionIndex],
        };
    });
});

watch(fundingSlides, () => {
    activeFundingSlide.value = 0;
});

const changeFundingSlide = (direction) => {
    if (fundingSlides.value.length < 2) {
        return;
    }

    activeFundingSlide.value = (activeFundingSlide.value + direction + fundingSlides.value.length)
        % fundingSlides.value.length;
};

const selectFundingSlide = (slideIndex) => {
    activeFundingSlide.value = slideIndex;
};

const handleFundingTouchStart = (event) => {
    fundingTouchStartX.value = event.changedTouches[0]?.clientX ?? null;
};

const handleFundingTouchEnd = (event) => {
    if (fundingTouchStartX.value === null) {
        return;
    }

    const distance = fundingTouchStartX.value - event.changedTouches[0].clientX;
    fundingTouchStartX.value = null;

    if (Math.abs(distance) < 40) {
        return;
    }

    changeFundingSlide(distance > 0 ? 1 : -1);
};

const monthlyPlans = computed(() => props.plans
    .filter((plan) => plan.type === 'spending'
        && plan.currency === props.selectedCurrency
        && isPlanScheduledForSelectedMonth(plan))
    .map((plan) => ({
        plan,
        period: periodForSelectedMonth(plan),
    })));

const selectedMonthTotals = computed(() => monthlyPlans.value.reduce((totals, { plan, period }) => {
    return {
        allocated: totals.allocated + Number(period?.allocated_amount ?? plan.amount ?? 0),
        spent: totals.spent + Number(period?.spent ?? 0),
    };
}, { allocated: 0, spent: 0 }));
</script>

<template>
    <div class="space-y-5 text-[#152338] sm:space-y-6">
        <header class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="text-3xl font-semibold tracking-tight text-[#111B39] sm:text-4xl">{{ $t('Budget plans') }}</h1>
            </div>
            <div class="flex w-full items-center gap-3 sm:w-auto">
                <div
                    class="relative min-w-0 flex-1 sm:flex-none"
                    @keydown.esc.stop="monthPickerOpen = false"
                >
                    <ButtonPrimary
                        type="button"
                        variant="secondary"
                        class="!inline-flex !h-11 !min-h-11 !w-full !items-center !justify-center !gap-2 !rounded-xl !px-4 !text-sm sm:!w-auto"
                        aria-haspopup="dialog"
                        :aria-expanded="monthPickerOpen"
                        @click="monthPickerOpen = !monthPickerOpen"
                    >
                        <CalendarDays :size="17" />
                        <span class="capitalize">{{ selectedMonthLabel }}</span>
                        <ChevronDown :size="16" class="text-[#687A9B]" />
                    </ButtonPrimary>
                    <div
                        v-if="monthPickerOpen"
                        class="absolute right-0 top-12 z-20 w-[min(20rem,calc(100vw-2rem))] rounded-2xl border border-[#E4EAF4] bg-white p-4 shadow-xl"
                        role="dialog"
                        :aria-label="$t('Pilih bulan dan tahun')"
                    >
                        <label class="mb-3 flex items-center justify-between gap-3 text-sm font-semibold text-[#344563]">
                            <span>{{ $t('Tahun') }}</span>
                            <select
                                v-model.number="selectedYear"
                                class="h-10 rounded-lg border border-[#DCE4F0] bg-white px-3 text-sm font-semibold text-[#17243A] focus:border-[#2457DA] focus:ring-[#2457DA]"
                            >
                                <option v-for="year in availableYears" :key="year" :value="year">{{ year }}</option>
                            </select>
                        </label>
                        <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-[#687A9B]">{{ $t('Bulan') }}</p>
                        <div class="grid grid-cols-3 gap-2">
                            <ButtonPrimary
                                v-for="(monthName, monthIndex) in monthNames"
                                :key="monthName"
                                type="button"
                                :variant="selectedMonth === monthIndex ? 'primary' : 'secondary'"
                                class="!min-h-9 !rounded-lg !px-2 !py-1.5 !text-xs capitalize"
                                :aria-pressed="selectedMonth === monthIndex"
                                @click="selectedMonth = monthIndex; monthPickerOpen = false"
                            >{{ monthName }}</ButtonPrimary>
                        </div>
                    </div>
                </div>
                <div
                    v-if="availableCurrencies.length > 1"
                    class="flex h-11 min-w-28 flex-1 items-center justify-center gap-1 rounded-xl border border-[#DCE4F0] bg-white p-1 sm:flex-none"
                    role="group"
                    :aria-label="$t('Pilih mata uang')"
                >
                    <ButtonPrimary
                        v-for="currency in availableCurrencies"
                        :key="currency"
                        type="button"
                        :variant="selectedCurrency === currency ? 'primary' : 'secondary'"
                        class="!min-h-0 !flex-1 !rounded-lg !px-3 !py-2 !text-xs sm:!flex-none"
                        :aria-pressed="selectedCurrency === currency"
                        @click="emit('update:selectedCurrency', currency)"
                    >{{ currency }}</ButtonPrimary>
                </div>
            </div>
        </header>

        <Link
            v-if="plansAwaitingFunding.length"
            :href="showPlanUrl(plansAwaitingFunding[0].id)"
            class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-[#FFDEA1] bg-[#FFF6E3] px-5 py-4 text-sm"
        >
            <span class="flex items-center gap-3 font-semibold text-[#85420D]">
                <CircleAlert :size="21" class="shrink-0 text-[#E68A00]" />
                {{ plansAwaitingFunding.length }} {{ $t('budget menunggu dana') }}
            </span>
            <span class="inline-flex items-center gap-2 font-semibold text-[#075EDB]">
                {{ $t('Alokasikan dana') }}
                <ArrowRight :size="17" />
            </span>
        </Link>

        <section class="grid gap-4 md:grid-cols-3">
            <div class="relative isolate px-1 pt-5">
                <span
                    v-for="(layer, layerIndex) in pastelLayerThemes"
                    :key="layerIndex"
                    class="pointer-events-none absolute h-10 rounded-[1.5rem] transition-colors duration-500"
                    :class="[layer.layer, layer.position]"
                />
                <Transition name="fund-card" mode="out-in">
                    <article
                        :key="fundingSlides[activeFundingSlide]?.key"
                        class="relative min-h-[9rem] touch-pan-y overflow-hidden rounded-2xl p-3 shadow-[0_12px_26px_rgba(18,101,236,0.16)] sm:min-h-[10rem] sm:p-4"
                        :class="fundingSlides[activeFundingSlide]?.theme.card"
                        role="group"
                        aria-roledescription="slide"
                        :aria-label="`${activeFundingSlide + 1} / ${fundingSlides.length}`"
                        @touchstart.passive="handleFundingTouchStart"
                        @touchend.passive="handleFundingTouchEnd"
                    >
                        <span
                            class="pointer-events-none absolute -right-6 -top-10 h-44 w-44 rounded-full transition-colors duration-500"
                            :class="fundingSlides[activeFundingSlide]?.theme.decoration"
                        />

                        <div class="relative flex min-h-[7rem] flex-col justify-between gap-2">
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex min-w-0 items-center gap-3">
                                    <span
                                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl sm:h-12 sm:w-12"
                                        :class="fundingSlides[activeFundingSlide]?.theme.icon"
                                    >
                                        <Wallet :size="22" />
                                    </span>
                                    <div class="min-w-0">
                                        <p
                                            class="text-sm font-medium sm:text-base"
                                            :class="fundingSlides[activeFundingSlide]?.theme.text"
                                        >
                                            {{ fundingSlides[activeFundingSlide]?.type === 'available'
                                                ? $t('Dana tersedia')
                                                : fundingSlides[activeFundingSlide]?.type === 'accumulated'
                                                    ? $t(fundingSlides[activeFundingSlide]?.label)
                                                    : $t('Sisa budget') }}
                                        </p>
                                        <p class="mt-1 truncate text-xl font-bold tracking-tight sm:text-2xl">
                                            {{ formatCurrency(
                                                fundingSlides[activeFundingSlide]?.type === 'available'
                                                    ? totals[selectedCurrency]?.free ?? 0
                                                    : fundingSlides[activeFundingSlide]?.remainingAmount,
                                                selectedCurrency,
                                            ) }}
                                        </p>
                                        <p
                                            class="mt-1 text-xs"
                                            :class="fundingSlides[activeFundingSlide]?.theme.mutedText"
                                        >
                                            <template v-if="fundingSlides[activeFundingSlide]?.type === 'available'">
                                                {{ $t('Belum dialokasikan') }}
                                            </template>
                                            <template v-else-if="fundingSlides[activeFundingSlide]?.type === 'accumulated'">
                                                {{ $t('Gabungan sisa {count} bulan sebelumnya', {
                                                    count: fundingSlides[activeFundingSlide]?.monthCount,
                                                }) }}
                                            </template>
                                            <template v-else>
                                                {{ $t('Sisa budget') }}
                                            </template>
                                        </p>
                                    </div>
                                </div>

                                <span
                                    class="shrink-0 rounded-full px-3 py-1.5 text-xs font-semibold"
                                    :class="fundingSlides[activeFundingSlide]?.theme.pill"
                                >
                                    {{ fundingSlides[activeFundingSlide]?.type === 'accumulated'
                                        ? $t('{count} bulan', { count: fundingSlides[activeFundingSlide]?.monthCount })
                                        : fundingSlides[activeFundingSlide]?.shortLabel }}
                                </span>
                            </div>

                            <div class="flex items-end justify-between gap-2">
                                <p class="truncate text-sm font-semibold capitalize">
                                    {{ fundingSlides[activeFundingSlide]?.type === 'accumulated'
                                        ? $t(fundingSlides[activeFundingSlide]?.label)
                                        : fundingSlides[activeFundingSlide]?.label }}
                                </p>
                                <div class="flex shrink-0 items-center gap-1">
                                    <span class="mr-0.5 text-[11px] font-semibold">
                                        {{ activeFundingSlide + 1 }} / {{ fundingSlides.length }}
                                    </span>
                                    <ButtonPrimary
                                        type="button"
                                        variant="secondary"
                                        class="!inline-flex !h-7 !min-h-7 !w-7 !shrink-0 !items-center !justify-center !rounded-full !border !p-0 !leading-none"
                                        :class="fundingSlides[activeFundingSlide]?.theme.button"
                                        :aria-label="$t('Sebelumnya')"
                                        :disabled="fundingSlides.length < 2"
                                        @click="changeFundingSlide(-1)"
                                    >
                                        <ChevronLeft :size="16" />
                                    </ButtonPrimary>
                                    <ButtonPrimary
                                        type="button"
                                        variant="secondary"
                                        class="!inline-flex !h-7 !min-h-7 !w-7 !shrink-0 !items-center !justify-center !rounded-full !border !p-0 !leading-none"
                                        :class="fundingSlides[activeFundingSlide]?.theme.button"
                                        :aria-label="$t('Berikutnya')"
                                        :disabled="fundingSlides.length < 2"
                                        @click="changeFundingSlide(1)"
                                    >
                                        <ChevronRight :size="16" />
                                    </ButtonPrimary>
                                </div>
                            </div>
                        </div>
                    </article>
                </Transition>

            <div class="flex justify-center gap-2" role="group" :aria-label="$t('Pilih bulan')">
                <ButtonPrimary
                    v-for="(slide, slideIndex) in fundingSlides"
                    :key="slide.key"
                    type="button"
                    variant="secondary"
                    class="!h-2 !min-h-2 !rounded-full !p-0 transition-all"
                    :class="activeFundingSlide === slideIndex
                        ? `!w-8 ${slide.theme.dot}`
                        : '!w-5 !bg-[#CBD7EA] hover:!bg-[#9FB4D6]'"
                    :aria-label="slide.type === 'accumulated' ? $t(slide.label) : slide.label"
                    :aria-pressed="activeFundingSlide === slideIndex"
                    @click="selectFundingSlide(slideIndex)"
                />
            </div>
            </div>

            <article class="flex items-center gap-4 rounded-2xl border border-[#E4EAF4] bg-white p-5 sm:p-6">
                <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-[#EEF4FF] text-[#1768EB]">
                    <Wallet :size="23" />
                </span>
                <div class="min-w-0">
                    <p class="text-sm font-medium text-[#52627E]">{{ $t('Dana teralokasikan') }}</p>
                    <p class="mt-1 truncate text-2xl font-bold tracking-tight text-[#111B39] sm:text-3xl">
                        {{ formatCurrency(selectedMonthTotals.allocated, selectedCurrency) }}
                    </p>
                    <p class="mt-1 text-xs text-[#8796AF]">
                        {{ $t('Alokasi bulan terpilih') }} · {{ selectedMonthLabel }}
                    </p>
                </div>
            </article>

            <article class="flex items-center gap-4 rounded-2xl border border-[#E4EAF4] bg-white p-5 sm:p-6">
                <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-[#EEF4FF] text-[#1768EB]">
                    <ChartNoAxesCombined :size="23" />
                </span>
                <div class="min-w-0">
                    <p class="text-sm font-medium text-[#52627E]">{{ $t('Dana terealisasikan') }}</p>
                    <p class="mt-1 truncate text-2xl font-bold tracking-tight text-[#111B39] sm:text-3xl">
                        {{ formatCurrency(selectedMonthTotals.spent, selectedCurrency) }}
                    </p>
                    <p class="mt-1 text-xs text-[#8796AF]">
                        {{ $t('Pengeluaran budget bulan terpilih') }} · {{ selectedMonthLabel }}
                    </p>
                </div>
            </article>
        </section>

        <BudgetPlanHistoryChart
            :plans="plans"
            :selected-currency="selectedCurrency"
            :selected-year="selectedYear"
            :selected-month="selectedMonth"
            :selected-month-label="selectedMonthLabel"
            :format-currency="formatCurrency"
        />

        <div class="grid items-start gap-5 xl:grid-cols-[minmax(0,2.25fr)_minmax(300px,0.85fr)]">
            <BudgetPlanList
                :plans="plans"
                :active-plan-count="activePlanCount"
                :create-plan-url="createPlanUrl"
                :show-plan-url="showPlanUrl"
                :period-for-selected-month="periodForSelectedMonth"
                :is-plan-scheduled-for-selected-month="isPlanScheduledForSelectedMonth"
                :period-usage-percentage="periodUsagePercentage"
                :format-currency="formatCurrency"
                :plan-status-label="planStatusLabel"
                :plan-icon="planIcon"
                :plan-icon-style="planIconStyle"
                @edit="emit('edit', $event)"
                @delete="emit('delete', $event)"
            />

            <BudgetPlanMonthlySummary
                :monthly-plans="monthlyPlans"
                :selected-month-label="selectedMonthLabel"
                :selected-currency="selectedCurrency"
                :create-plan-url="createPlanUrl"
                :format-currency="formatCurrency"
            />
        </div>
    </div>
</template>

<style scoped>
.fund-card-enter-active,
.fund-card-leave-active {
    transition:
        opacity 220ms ease,
        transform 320ms cubic-bezier(0.2, 0.75, 0.3, 1);
}

.fund-card-enter-from {
    opacity: 0.5;
    transform: translate3d(0, 18px, 0) scale(0.95);
}

.fund-card-leave-to {
    opacity: 0;
    transform: translate3d(24px, -8px, 0) scale(0.96) rotate(1deg);
}
</style>
