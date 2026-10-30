<script setup>
import { computed, ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import {
    ArrowRight,
    CalendarDays,
    ChartNoAxesCombined,
    ChevronDown,
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

const monthlyPlans = computed(() => props.plans
    .filter((plan) => plan.type === 'spending'
        && plan.currency === props.selectedCurrency
        && isPlanScheduledForSelectedMonth(plan))
    .map((plan) => ({
        plan,
        period: periodForSelectedMonth(plan),
    })));

const selectedMonthTotals = computed(() => monthlyPlans.value.reduce((totals, { plan, period }) => ({
    allocated: totals.allocated + Number(period?.allocated_amount ?? plan.amount ?? 0),
    spent: totals.spent + Number(period?.spent ?? 0),
}), { allocated: 0, spent: 0 }));
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
            <article class="relative isolate overflow-hidden rounded-2xl bg-gradient-to-br from-[#1265EC] to-[#2457DA] p-5 text-white sm:p-6">
                <span class="pointer-events-none absolute -right-10 -top-8 -z-10 h-40 w-40 rounded-full bg-white/10" />
                <div class="flex items-center gap-4">
                    <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-white/15">
                        <Wallet :size="23" />
                    </span>
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-white/85">{{ $t('Dana tersedia') }}</p>
                        <p class="mt-1 truncate text-2xl font-bold tracking-tight sm:text-3xl">
                            {{ formatCurrency(totals[selectedCurrency]?.free ?? 0, selectedCurrency) }}
                        </p>
                        <p class="mt-1 text-xs text-white/75">{{ $t('Belum dialokasikan') }}</p>
                    </div>
                </div>
            </article>

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
