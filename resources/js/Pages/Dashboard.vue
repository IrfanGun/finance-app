<script setup>
import {
    computed,
    nextTick,
    onMounted,
    onUnmounted,
    ref,
    watch,
} from 'vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { ArrowDown, ArrowUp, ArrowUpRight, Banknote, ChevronRight, Eye, EyeOff, Wallet } from 'lucide-vue-next';
import MobileBottomNav from '@/Components/MobileBottomNav.vue';
import ButtonPrimary from '@/Components/ButtonPrimary.vue';
import LanguageSwitcher from '@/Components/LanguageSwitcher.vue';
import SpendingCategoryChart from '@/Components/SpendingCategoryChart.vue';
import FormInput from '@/Components/FormInput.vue';
import SelectInput from '@/Components/SelectInput.vue';
import DatePicker from '@/Components/DatePicker.vue';
import { index as categoriesIndex } from '@/routes/categories';
import { scan as receiptScan } from '@/routes/receipt';
import { index as accountsIndex } from '@/routes/accounts';
import { index as assetsIndex } from '@/routes/assets';
import { show as assetShow } from '@/routes/assets';
import { index as transactionsIndex } from '@/routes/transactions';
import { index as budgetIndex } from '@/routes/budget-plans';
import { index as savingsIndex } from '@/routes/savings';
import { index as mealsIndex } from '@/routes/meals';
import { index as remindersIndex } from '@/routes/recurring-reminders';
import { chat as chatIndex } from '@/routes';
import { store as transactionStore } from '@/actions/App/Http/Controllers/TransactionController';
import { useTranslation } from '@/Composables/useTranslation';

const page = usePage();
const { t, locale } = useTranslation();
const props = defineProps({
    categories: { type: Array, default: () => [] },
    spendingCategories: { type: Array, default: () => [] },
    weeklySpending: { type: Array, default: () => [] },
    accounts: { type: Array, default: () => [] },
    assets: { type: Array, default: () => [] },
    transactions: { type: Array, default: () => [] },
    summary: { type: Object, default: () => ({ income: 0, expense: 0, balance: 0 }) },
    selectedMealRecord: { type: Object, default: null },
    funds: { type: Object, default: () => ({}) },
    budgetPeriods: { type: Array, default: () => [] },
    savingOptions: { type: Array, default: () => [] },
    wealthTrend: {
        type: Object,
        default: () => ({ series: [], change: 0, change_percentage: 0 }),
    },
});

const userName = page.props.auth?.user?.name ?? 'User';
const showTransactionModal = ref(false);
const transactionStep = ref(1);
const transactionType = ref('expense');
const sourceType = ref('free');
const transactionForm = useForm({
    type: 'expense',
    amount: '',
    date: new Date().toISOString().slice(0, 10),
    title: '',
    note: '',
    account_id: props.accounts[0]?.id ?? '',
    category_id: '',
    meal_record_id: props.selectedMealRecord?.id ?? null,
    budget_period_id: null,
    saving_id: null,
});
const isScrolled = ref(false);
const isFinancialDetailsVisible = ref(false);
const fundCurrencies = Object.keys(props.funds).length
    ? Object.keys(props.funds)
    : ['IDR'];
const activeFundCurrency = ref(fundCurrencies[0]);
const wealthChartElement = ref(null);
let wealthChart = null;
let apexChartsConstructor = null;
const formatCurrency = (amount) => new Intl.NumberFormat(locale.value === 'id' ? 'id-ID' : 'en-US').format(Number(amount ?? 0));
const wealthChange = computed(() => Number(props.wealthTrend.change ?? 0));
const wealthChangePercentage = computed(() => Number(props.wealthTrend.change_percentage ?? 0));
const activeFunds = computed(() => props.funds[activeFundCurrency.value] ?? {});
const formatFundCurrency = (amount, currency = activeFundCurrency.value) => new Intl.NumberFormat(
    locale.value === 'id' ? 'id-ID' : 'en-US',
    {
        style: 'currency',
        currency,
        maximumFractionDigits: currency === 'IDR' ? 0 : 2,
    },
).format(Number(amount ?? 0));
const wealthTrendColor = computed(() => {
    if (wealthChange.value > 0) {
        return '#4DFF88';
    }

    if (wealthChange.value < 0) {
        return '#F87171';
    }

    return '#FFFFFF';
});
const formattedWealthChange = computed(() => {
    const sign = wealthChange.value > 0
        ? '+'
        : wealthChange.value < 0
            ? '-'
            : '';

    return `${sign}Rp ${formatCurrency(Math.abs(wealthChange.value))}`;
});
const formattedWealthChangePercentage = computed(() => {
    const sign = wealthChangePercentage.value > 0
        ? '+'
        : wealthChangePercentage.value < 0
            ? '-'
            : '';

    return `${sign}${Math.abs(wealthChangePercentage.value)}%`;
});
const wealthTrendPoints = computed(() => {
    const points = props.wealthTrend.series;
    const lastIndex = points.length - 1;
    const sampleInterval = Math.max(1, Math.floor(lastIndex / 5));

    return points.filter((_, index) =>
        index === 0
        || index === lastIndex
        || index % sampleInterval === 0,
    );
});
const wealthChartOptions = computed(() => ({
    series: [{
        name: t('Kekayaan bersih'),
        data: wealthTrendPoints.value.map((point) => ({
            x: new Date(`${point.date}T00:00:00Z`).getTime(),
            y: Number(point.value),
        })),
    }],
    chart: {
        type: 'area',
        height: 64,
        width: 160,
        parentHeightOffset: 0,
        sparkline: { enabled: true },
        toolbar: { show: false },
        animations: { enabled: false },
    },
    colors: [wealthTrendColor.value],
    dataLabels: { enabled: false },
    fill: {
        type: 'gradient',
        gradient: {
            shadeIntensity: 0.3,
            opacityFrom: 0.46,
            opacityTo: 0.04,
            stops: [0, 100],
            colorStops: [
                {
                    offset: 0,
                    color: wealthTrendColor.value,
                    opacity: 0.46,
                },
                {
                    offset: 100,
                    color: wealthTrendColor.value,
                    opacity: 0.04,
                },
            ],
        },
    },
    grid: { show: false, padding: { top: 3, right: 0, bottom: 0, left: 0 } },
    markers: { size: 0, hover: { size: 0 } },
    stroke: {
        curve: 'smooth',
        colors: [wealthTrendColor.value],
        width: 2.5,
        lineCap: 'round',
    },
    tooltip: {
        x: { format: 'dd MMM' },
        y: { formatter: (amount) => `Rp ${formatCurrency(amount)}` },
    },
    xaxis: {
        type: 'datetime',
        crosshairs: { show: false },
    },
    yaxis: {
        show: false,
        crosshairs: { show: false },
    },
}));
const renderWealthChart = async () => {
    await nextTick();

    if (!wealthChartElement.value) {
        wealthChart?.destroy();
        wealthChart = null;

        return;
    }

    if (wealthChart) {
        await wealthChart.updateOptions(wealthChartOptions.value);

        return;
    }

    if (!apexChartsConstructor) {
        const apexChartsModule = await import('apexcharts/core');

        await import('apexcharts/area');

        apexChartsConstructor = apexChartsModule.default;
    }

    wealthChart = new apexChartsConstructor(
        wealthChartElement.value,
        wealthChartOptions.value,
    );
    await wealthChart.render();
};
const formatAccountBalance = (account) => new Intl.NumberFormat(locale.value === 'id' ? 'id-ID' : 'en-US', {
    style: 'currency',
    currency: account.currency || 'IDR',
    maximumFractionDigits: 2,
}).format(Number(account.current_balance ?? 0));
const transactionCategory = (transaction) => transaction.category?.name ?? 'Uncategorized';
const transactionAmount = (transaction) => `${transaction.type === 'income' ? '+' : '-'} Rp ${formatCurrency(transaction.amount)}`;

watch(wealthChartOptions, renderWealthChart, { deep: true });

onMounted(renderWealthChart);
onUnmounted(() => wealthChart?.destroy());

const openTransactionModal = () => {
    transactionForm.reset();
    transactionForm.type = 'expense';
    transactionForm.date = new Date().toISOString().slice(0, 10);
    transactionForm.account_id = props.accounts[0]?.id ?? '';
    transactionForm.meal_record_id = null;
    transactionForm.budget_period_id = null;
    transactionForm.saving_id = null;
    sourceType.value = 'free';
    transactionStep.value = 1;
    showTransactionModal.value = true;
};
const submitTransaction = () => {
    transactionForm.budget_period_id = sourceType.value === 'budget'
        ? transactionForm.budget_period_id
        : null;
    transactionForm.saving_id = sourceType.value === 'saving'
        ? transactionForm.saving_id
        : null;
    transactionForm.post(transactionStore.url(), {
        preserveScroll: true,
        onSuccess: () => {
            transactionForm.reset();
            transactionForm.type = 'expense';
            transactionForm.date = new Date().toISOString().slice(0, 10);
            transactionForm.account_id = props.accounts[0]?.id ?? '';
            transactionStep.value = 1;
            showTransactionModal.value = false;
        },
    });
};

const handleScroll = () => {
    isScrolled.value = window.scrollY > 40;
};

onMounted(() => {
    window.addEventListener('scroll', handleScroll, { passive: true });

    const transactionQuery = new URLSearchParams(window.location.search);

    if (transactionQuery.has('add')) {
        openTransactionModal();

        if (transactionQuery.has('budget_period')) {
            sourceType.value = 'budget';
            transactionForm.budget_period_id = Number(transactionQuery.get('budget_period'));
            transactionForm.account_id = Number(transactionQuery.get('account_id'))
                || props.accounts[0]?.id
                || '';
        } else if (transactionQuery.has('saving_id')) {
            sourceType.value = 'saving';
            transactionForm.saving_id = Number(transactionQuery.get('saving_id'));
            const saving = props.savingOptions.find(
                (item) => Number(item.id) === Number(transactionForm.saving_id),
            );
            transactionForm.account_id = props.accounts.find(
                (account) => account.currency === saving?.currency,
            )?.id ?? props.accounts[0]?.id ?? '';
        }
    }

    if (props.selectedMealRecord) {
        transactionForm.title = props.selectedMealRecord.name;
        transactionForm.note = props.selectedMealRecord.place_name ?? '';
        transactionForm.meal_record_id = props.selectedMealRecord.id;
        showTransactionModal.value = true;
    }
});

onUnmounted(() => {
    window.removeEventListener('scroll', handleScroll);
});

const shortcuts = [
    {
        image: '/dashboard-icons/savings.png',
        label: 'Savings',
        href: savingsIndex.url(),
        gradient: 'from-budget-shortcut-savings-start to-budget-shortcut-savings-end',
    },
    {
        image: '/dashboard-icons/scan.png',
        label: 'Scan',
        href: receiptScan.url(),
        gradient: 'from-budget-shortcut-scan-start to-budget-shortcut-scan-end',
    },
    {
        image: '/dashboard-icons/chat.png',
        label: 'Chat',
        href: chatIndex.url(),
        gradient: 'from-budget-shortcut-chat-start to-budget-shortcut-chat-end',
    },
    {
        image: '/dashboard-icons/meals.png',
        label: 'Meals',
        href: mealsIndex.url(),
        gradient: 'from-budget-shortcut-meals-start to-budget-shortcut-meals-end',
    },
    {
        image: '/dashboard-icons/reminder.png',
        label: 'Reminder',
        href: remindersIndex.url(),
        gradient: 'from-budget-shortcut-reminder-start to-budget-shortcut-reminder-end',
    },
    {
        image: '/dashboard-icons/plan.png',
        label: 'Plan',
        href: budgetIndex.url(),
        gradient: 'from-budget-shortcut-plan-start to-budget-shortcut-plan-end',
    },
];
</script>

<template>

    <Head :title="t('Dashboard')" />

    <Transition name="mini-header">
        <div v-if="isScrolled"
            class="fixed inset-x-0 top-0 z-20 mx-auto flex h-16 w-full max-w-lg items-center justify-between bg-budget-header px-6 text-white">
            <div>
                <p class="text-xs text-blue-100">{{ t('Good morning,') }}</p>
                <h1 class="text-lg font-semibold">{{ userName }}</h1>
            </div>
            <div class="flex items-center gap-2">
                <LanguageSwitcher :floating="false" />
                <div class="flex h-9 w-9 items-center justify-center rounded-full bg-white text-xs font-semibold text-budget-primary">
                    {{ userName.slice(0, 2).toUpperCase() }}
                </div>
            </div>
        </div>
    </Transition>

    <div class="mx-auto flex min-h-screen w-full max-w-lg flex-col bg-budget-page pb-28 text-budget-heading">
        <div class="mx-5 mt-6 flex items-center justify-between">
            <div>
                <p class="text-sm text-budget-muted">{{ t('Good morning,') }}</p>
                <h1 class="text-2xl font-bold tracking-tight">{{ userName }}</h1>
            </div>
            <div class="flex items-center gap-2">
                <LanguageSwitcher :floating="false" />
                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-white text-xs font-semibold text-budget-primary">
                    {{ userName.slice(0, 2).toUpperCase() }}
                </div>
            </div>
        </div>

        <header
            class="relative mx-5 mt-5 overflow-hidden rounded-[28px] bg-budget-header px-6 py-7 text-white">
            <div
                ref="wealthChartElement"
                class="wealth-chart pointer-events-none absolute right-3 top-4 h-16 w-40"
                aria-hidden="true"
            ></div>
            <div class="relative">
                <div class="flex items-center gap-2 text-white/90">
                    <Wallet :size="19" :stroke-width="2" aria-hidden="true" />
                    <p class="text-sm font-medium">{{ t('Total Worth') }}</p>
                </div>
                <p class="mt-2 text-[clamp(2rem,9vw,2.5rem)] font-bold tracking-tight">Rp {{ formatCurrency(summary.balance) }}</p>
                <div class="mt-3 flex flex-wrap items-center justify-between gap-2">
                    <div class="flex flex-wrap items-center gap-2">
                        <span
                            class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold"
                            :class="wealthChange > 0
                                ? 'bg-[#4DFF88]/20 text-[#73FFA3]'
                                : wealthChange < 0
                                    ? 'bg-red-300/20 text-red-200'
                                    : 'bg-white/15 text-white/90'"
                        >
                            <span class="flex h-4 w-4 items-center justify-center rounded-full border border-current">
                                <ArrowUp v-if="wealthChange > 0" :size="11" />
                                <ArrowDown v-else-if="wealthChange < 0" :size="11" />
                                <span v-else class="h-px w-2 bg-current"></span>
                            </span>
                            {{ formattedWealthChangePercentage }}
                        </span>
                        <span class="text-xs font-medium text-white/85">
                            {{ t('This month') }} ({{ formattedWealthChange }})
                        </span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <ButtonPrimary
                            v-for="currency in fundCurrencies"
                            :key="currency"
                            type="button"
                            variant="secondary"
                            class="!min-h-0 !rounded-full !px-2.5 !py-1 !text-[11px] !shadow-none"
                            :class="activeFundCurrency === currency
                                ? '!border-white !bg-white !text-budget-primary'
                                : '!border-white/30 !bg-transparent !text-white/80'"
                            :aria-pressed="activeFundCurrency === currency"
                            @click="activeFundCurrency = currency"
                        >{{ currency }}</ButtonPrimary>
                        <ButtonPrimary
                            type="button"
                            variant="secondary"
                            class="!min-h-0 !h-8 !w-8 !rounded-none !border-0 !bg-transparent !p-0 !text-white !shadow-none"
                            :aria-label="t(isFinancialDetailsVisible ? 'Hide details' : 'Show details')"
                            aria-controls="financial-summary-details"
                            :aria-expanded="isFinancialDetailsVisible"
                            @click="isFinancialDetailsVisible = !isFinancialDetailsVisible"
                        >
                            <EyeOff v-if="isFinancialDetailsVisible" :size="16" />
                            <Eye v-else :size="16" />
                        </ButtonPrimary>
                    </div>
                </div>
            </div>
            <div
                v-show="isFinancialDetailsVisible"
                id="financial-summary-details"
                class="mt-6 border-t border-white/20 pt-4"
            >
                <div class="mt-4 grid grid-cols-2">
                    <div class="min-w-0 border-r border-b border-white/25 pb-3 pr-3">
                        <p class="text-sm text-white/75">{{ t('Total assets') }}</p>
                        <strong class="mt-1 block truncate text-lg font-semibold tracking-tight">
                            {{ formatFundCurrency(summary.asset_value, 'IDR') }}
                        </strong>
                    </div>
                    <div class="min-w-0 border-b border-white/25 pb-3 pl-3">
                        <p class="text-sm text-white/75">{{ t('Current balance') }}</p>
                        <strong class="mt-1 block truncate text-lg font-semibold tracking-tight">
                            {{ formatFundCurrency(activeFunds.actual) }}
                        </strong>
                    </div>
                    <div class="min-w-0 border-r border-white/25 pt-3 pr-3">
                        <p class="text-sm text-white/75">{{ t('Budget reserved') }}</p>
                        <strong class="mt-1 block truncate text-lg font-semibold tracking-tight">
                            {{ formatFundCurrency(activeFunds.budget) }}
                        </strong>
                    </div>
                    <div class="min-w-0 pt-3 pl-3">
                        <p class="text-sm text-white/75">{{ t('Savings funds') }}</p>
                        <strong class="mt-1 block truncate text-lg font-semibold tracking-tight">
                            {{ formatFundCurrency(activeFunds.savings) }}
                        </strong>
                    </div>
                </div>
            </div>
        </header>

        <nav class="mx-5 mt-5 grid grid-cols-3 gap-x-3 gap-y-5 px-1 py-2 text-center text-xs font-semibold text-budget-heading">
            <div v-for="item in shortcuts" :key="item.label">
                <Link :href="item.href" class="block">
                    <span
                        class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-br transition hover:-translate-y-0.5"
                        :class="item.gradient"
                    >
                        <img
                            :src="item.image"
                            alt=""
                            class="h-8 w-8 object-contain"
                        />
                    </span>
                    <span class="mt-2 block">{{ t(item.label) }}</span>
                </Link>
            </div>
        </nav>

        <main class="space-y-7 px-5 pt-6">
            <section>
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-bold tracking-tight">{{ t('This month') }}</h2><span class="text-sm text-budget-muted">{{ new
                        Date().toLocaleDateString(locale === 'id' ? 'id-ID' : 'en-US', { month: 'short', year: 'numeric' }) }}</span>
                </div>
                <div class="mt-4 grid grid-cols-2 gap-3">
                    <div class="rounded-[20px] border border-budget-border bg-budget-surface p-4">
                        <p class="flex items-center gap-1 text-sm text-income">
                            <ArrowDown :size="15" />{{ t('Income') }}
                        </p>
                        <p class="mt-2 text-lg font-semibold">Rp {{ formatCurrency(summary.income) }}</p>
                    </div>
                    <div class="rounded-[20px] border border-budget-border bg-budget-surface p-4">
                        <p class="flex items-center gap-1 text-sm text-expense">
                            <ArrowUp :size="15" />{{ t('Expense') }}
                        </p>
                        <p class="mt-2 text-lg font-semibold">Rp {{ formatCurrency(summary.expense) }}</p>
                    </div>
                </div>
            </section>
            <section>
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-bold tracking-tight">{{ t('Spending this week') }}</h2>
                    <ButtonPrimary
                        :href="categoriesIndex.url()"
                        variant="secondary"
                        class="!min-h-0 !border-0 !bg-transparent !px-2 !py-1 !text-sm !text-budget-link !shadow-none"
                    >{{ t('Details') }}</ButtonPrimary>
                </div>
                <div class="mt-4 rounded-[24px] border border-budget-border bg-budget-surface p-4">
                    <SpendingCategoryChart :days="weeklySpending" />
                </div>
            </section>
            <section>
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-semibold">{{ t('Recent transactions') }}</h2>
                    <Link :href="transactionsIndex.url()" class="text-sm font-semibold text-budget-link">{{ t('See all') }}</Link>
                </div>
                <div v-if="transactions.length" class="mt-4 rounded-[24px] border border-budget-border bg-budget-surface p-4">
                    <div v-for="transaction in transactions" :key="transaction.id" class="flex items-center gap-4 border-b border-budget-border py-3 first:pt-0 last:border-0 last:pb-0"><span
                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-[15px] bg-budget-icon-surface"
                            :class="transaction.type === 'income' ? 'text-income' : 'text-expense'">
                            <component :is="transaction.type === 'income' ? ArrowDown : ArrowUpRight" :size="20" />
                        </span>
                        <div class="flex-1">
                            <p class="text-sm font-medium">{{ transaction.note || t('Transaction') }}</p>
                            <p class="text-xs text-budget-muted">{{ transactionCategory(transaction) }} · {{
                                transaction.date }}
                            </p>
                        </div><strong class="text-sm" :class="transaction.type === 'income' ? 'text-income' : ''">{{
                            transactionAmount(transaction) }}</strong>
                    </div>
                </div>
                <p v-else class="mt-4 text-sm text-budget-muted">{{ t('No transactions yet.') }}</p>
            </section>
        </main>

        <section class="mt-7 px-6">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold">{{ t('Accounts') }}</h2>
                <Link :href="accountsIndex.url()" class="flex items-center text-sm font-semibold text-budget-link">{{ t('See all') }}
                    <ChevronRight :size="16" />
                </Link>
            </div>
            <div v-if="accounts.length" class="account-slider mt-4 flex snap-x snap-mandatory gap-4 overflow-x-auto pb-3">
                <article
                    v-for="(account, index) in accounts"
                    :key="account.id"
                    class="account-card relative isolate flex h-40 w-[280px] shrink-0 snap-start flex-col justify-between overflow-hidden rounded-[24px] bg-budget-header p-5 text-white"
                    :class="`account-card--${index % 4}`"
                >
                    <div class="relative z-10 flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-[22px] font-black italic leading-none tracking-tight">VISA</p>
                            <p class="mt-2 truncate text-xs font-medium text-white/75">{{ account.name }}</p>
                        </div>
                        <Banknote :size="22" class="shrink-0 text-white/85" />
                    </div>
                    <div class="relative z-10">
                        <p class="text-[21px] font-semibold tracking-tight">
                            {{ formatAccountBalance(account) }}
                        </p>
                        <div class="mt-2 flex items-center justify-between text-[11px] font-medium uppercase tracking-[0.16em] text-white/75">
                            <span class="capitalize">{{ account.type }}</span>
                            <span>{{ account.currency || 'IDR' }}</span>
                        </div>
                    </div>
                </article>
            </div>
            <p v-else class="mt-4 text-sm text-budget-muted">{{ t('No accounts yet.') }}</p>
        </section>
        <section class="mt-7 px-6">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold">{{ t('Assets') }}</h2>
                <Link :href="assetsIndex.url()" class="flex items-center text-sm font-semibold text-budget-link">
                    {{ t('See all') }} <ChevronRight :size="16" />
                </Link>
            </div>
            <div v-if="assets.length" class="mt-4 space-y-3">
                <Link
                    v-for="asset in assets"
                    :key="asset.id"
                    :href="assetShow.url(asset.id)"
                    class="flex items-center justify-between rounded-[20px] border border-budget-border bg-budget-surface p-4"
                >
                    <span class="font-medium">{{ asset.name }}</span>
                    <span class="text-sm font-semibold">{{ asset.currency }} {{ formatCurrency(asset.current_value) }}</span>
                </Link>
            </div>
            <p v-else class="mt-4 text-sm text-budget-muted">{{ t('No assets yet.') }}</p>
        </section>
        <div v-if="showTransactionModal" class="fixed inset-0 z-30 flex items-end bg-black/30"
            @click.self="showTransactionModal = false">
            <form class="mx-auto w-full max-w-lg rounded-t-[28px] border border-budget-border bg-budget-surface px-6 pb-8 pt-3 text-budget-heading shadow-[0_-12px_40px_rgba(41,82,160,0.14)]"
                @submit.prevent="submitTransaction">
                <div class="mx-auto mb-5 h-1 w-9 rounded-full bg-budget-progress-track"></div>
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-xs font-medium text-budget-muted">Step {{ transactionStep }} of 2</p>
                        <h2 class="text-[22px] font-semibold">{{ transactionForm.meal_record_id ? 'Catat pengeluaran' : 'New transaction' }}</h2>
                    </div>
                    <ButtonPrimary
                        type="button"
                        variant="secondary"
                        class="!min-h-0 !rounded-full !px-3 !py-2 !text-xl !text-budget-muted"
                        aria-label="Tutup"
                        @click="showTransactionModal = false"
                    >×</ButtonPrimary>
                </div>
                <template v-if="transactionStep === 1">
                    <div class="mt-5 grid grid-cols-2 gap-1 rounded-full bg-budget-control-surface p-1">
                        <ButtonPrimary
                            v-for="type in ['expense', 'income']"
                            :key="type"
                            type="button"
                            variant="secondary"
                            class="!min-h-[46px] !w-full !rounded-full !px-3 !py-2 !text-sm !font-semibold !capitalize !shadow-none"
                            :class="transactionForm.type === type
                                ? '!border-transparent !bg-white !text-budget-link'
                                : '!border-transparent !bg-transparent !text-budget-muted'"
                            @click="transactionForm.type = type"
                        >{{ type }}</ButtonPrimary>
                    </div>
                    <ButtonPrimary
                        type="button"
                        class="mt-6 !h-[53px] !w-full !rounded-[16px]"
                        @click="transactionStep = 2"
                    >Continue</ButtonPrimary>
                </template>
                <template v-else-if="transactionStep === 2">
                    <label class="mt-5 block">Account
                        <SelectInput v-model="transactionForm.account_id"
                            :options="accounts.map((account) => ({ label: account.name, value: account.id }))"
                            placeholder="Select account" required />
                    </label>
                    <div class="mt-6 grid grid-cols-2 gap-3">
                        <ButtonPrimary
                            type="button"
                            variant="secondary"
                            class="!h-[53px] !rounded-[16px]"
                            @click="transactionStep = 1"
                        >Back</ButtonPrimary>
                        <ButtonPrimary
                            type="button"
                            class="!h-[53px] !rounded-[16px]"
                            @click="transactionStep = 3"
                        >Continue</ButtonPrimary>
                    </div>
                </template>
                <template v-else>
                    <label v-if="transactionForm.type === 'expense'" class="mt-5 block text-sm">Sumber dana
                        <select v-model="sourceType" class="mt-1 w-full rounded-xl border border-slate-300 p-3">
                            <option value="free">Uang bebas</option>
                            <option value="budget">Cadangan budget</option>
                            <option value="saving">Savings</option>
                        </select>
                    </label>
                    <label v-if="transactionForm.type === 'expense' && sourceType === 'budget'" class="mt-3 block text-sm">
                        Periode budget
                        <select v-model="transactionForm.budget_period_id" class="mt-1 w-full rounded-xl border border-slate-300 p-3" required>
                            <option :value="null">Pilih periode</option>
                            <option
                                v-for="period in budgetPeriods.filter((item) => Number(item.account_id) === Number(transactionForm.account_id) && transactionForm.date >= item.starts_on && transactionForm.date <= item.ends_on)"
                                :key="period.id"
                                :value="period.id"
                            >
                                {{ period.name }} · {{ period.starts_on }} – {{ period.ends_on }}
                            </option>
                        </select>
                    </label>
                    <label v-if="transactionForm.type === 'expense' && sourceType === 'saving'" class="mt-3 block text-sm">
                        Savings
                        <select v-model="transactionForm.saving_id" class="mt-1 w-full rounded-xl border border-slate-300 p-3" required>
                            <option :value="null">Pilih savings</option>
                            <option
                                v-for="saving in savingOptions.filter((item) => item.currency === accounts.find((account) => Number(account.id) === Number(transactionForm.account_id))?.currency)"
                                :key="saving.id"
                                :value="saving.id"
                            >
                                {{ saving.name }}
                            </option>
                        </select>
                    </label>
                    <label class="mt-5 block text-sm text-budget-muted">Amount
                        <FormInput v-model="transactionForm.amount" type="number" min="0.01" step="0.01" required
                            placeholder="Rp 0" variant="amount" />
                    </label>
                    <label class="mt-5 block">Title
                        <FormInput v-model="transactionForm.title" placeholder="What was this transaction for?"
                            required />
                        <p v-if="transactionForm.meal_record_id" class="mt-1 text-xs text-budget-muted">Harga aktual dan akun pembayaran ditentukan di sini.</p>
                    </label>
                    <label class="mt-5 block">Category
                        <SelectInput v-model="transactionForm.category_id"
                            :options="categories.filter((item) => item.type === transactionForm.type && item.is_active).map((item) => ({ label: item.name, value: item.id }))"
                            placeholder="Select category" searchable />
                    </label>
                    <div class="mt-5 grid grid-cols-2 gap-3"><label>Date
                            <DatePicker v-model="transactionForm.date" required />
                        </label><label>Note
                            <FormInput v-model="transactionForm.note" placeholder="Optional note" />
                        </label></div>
                    <div class="mt-6 grid grid-cols-2 gap-3">
                        <ButtonPrimary
                            type="button"
                            variant="secondary"
                            class="!h-[53px] !rounded-[16px]"
                            @click="transactionStep = 2"
                        >Back</ButtonPrimary>
                        <ButtonPrimary
                            type="submit"
                            :disabled="transactionForm.processing"
                            class="!h-[53px] !rounded-[16px]"
                        >
                            {{ transactionForm.processing ? 'Saving...' : 'Create transaction' }}
                        </ButtonPrimary>
                    </div>
                    <p class="mt-3 text-center text-xs text-budget-muted">You can edit this transaction anytime.</p>
                </template>
            </form>
        </div>
        <MobileBottomNav :show-labels="true" @add="openTransactionModal" />
    </div>
</template>

<style scoped>
.wealth-chart :deep(.apexcharts-xcrosshairs),
.wealth-chart :deep(.apexcharts-ycrosshairs),
.wealth-chart :deep(.apexcharts-series-markers-wrap) {
    display: none !important;
}

.account-slider {
    scrollbar-width: none;
}

.account-slider::-webkit-scrollbar {
    display: none;
}

.account-card {
    isolation: isolate;
}

.account-card--1 {
    background: linear-gradient(135deg, #a92346 0%, #dc3b4f 55%, #f27b69 100%);
}

.account-card--2 {
    background: linear-gradient(135deg, #a96312 0%, #d99724 55%, #efc35d 100%);
}

.account-card--3 {
    background: linear-gradient(135deg, #11664d 0%, #16966c 55%, #54c995 100%);
}

.account-card::before,
.account-card::after {
    position: absolute;
    z-index: -1;
    content: '';
    pointer-events: none;
}

.account-card::before {
    inset: -35% -12% auto auto;
    width: 78%;
    height: 170%;
    border: 1px solid rgb(255 255 255 / 20%);
    border-radius: 50%;
    background: rgb(255 255 255 / 3%);
    box-shadow: 0 0 24px rgb(255 255 255 / 6%);
    backdrop-filter: blur(2px);
    transform: rotate(-28deg);
}

.account-card::after {
    inset: auto -20% -70% auto;
    width: 85%;
    height: 125%;
    border: 1px solid rgb(255 255 255 / 18%);
    border-radius: 50%;
    background: linear-gradient(135deg, rgb(255 255 255 / 8%), transparent 65%);
    transform: rotate(-24deg);
}

.mini-header-enter-active,
.mini-header-leave-active {
    transition: opacity 180ms ease, transform 180ms ease;
}

.mini-header-enter-from,
.mini-header-leave-to {
    opacity: 0;
    transform: translateY(-12px);
}

section.mt-7.px-6 {
    order: 1;
}

main {
    order: 2;
}

</style>
