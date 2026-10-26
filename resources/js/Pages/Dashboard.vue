<script setup>
import {
    computed,
    nextTick,
    onMounted,
    onUnmounted,
    ref,
    watch,
} from 'vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { ArrowDown, ArrowUp, ArrowUpRight, Banknote, ChevronRight } from 'lucide-vue-next';
import MobileBottomNav from '@/Components/MobileBottomNav.vue';
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
const fundCurrencies = Object.keys(props.funds);
const activeFundCurrency = ref(fundCurrencies[0] ?? 'IDR');
const wealthChartElement = ref(null);
let wealthChart = null;
let apexChartsConstructor = null;
const updateActiveFundCurrency = (event) => {
    const slideIndex = Math.round(event.target.scrollTop / 88);
    const currencyIndex = slideIndex % fundCurrencies.length;
    activeFundCurrency.value = fundCurrencies[Math.min(currencyIndex, fundCurrencies.length - 1)] ?? 'IDR';
};
const formatCurrency = (amount) => new Intl.NumberFormat(locale.value === 'id' ? 'id-ID' : 'en-US').format(Number(amount ?? 0));
const wealthChartOptions = computed(() => ({
    series: [{
        name: t('Kekayaan bersih'),
        data: props.wealthTrend.series.map((point) => ({
            x: new Date(`${point.date}T00:00:00Z`).getTime(),
            y: Number(point.value),
        })),
    }],
    chart: {
        type: 'area',
        height: 64,
        width: 128,
        parentHeightOffset: 0,
        sparkline: { enabled: true },
        toolbar: { show: false },
        animations: { enabled: false },
    },
    colors: [props.wealthTrend.change_percentage >= 0 ? '#6EE7B7' : '#FCA5A5'],
    dataLabels: { enabled: false },
    fill: {
        type: 'gradient',
        gradient: {
            opacityFrom: 0.3,
            opacityTo: 0.02,
        },
    },
    grid: { show: false, padding: { top: 3, right: 0, bottom: 0, left: 0 } },
    markers: { size: 0, hover: { size: 3 } },
    stroke: {
        curve: 'monotoneCubic',
        width: 3,
        lineCap: 'round',
    },
    tooltip: {
        x: { format: 'dd MMM' },
        y: { formatter: (amount) => `Rp ${formatCurrency(amount)}` },
    },
    xaxis: { type: 'datetime' },
    yaxis: { show: false },
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
        image: '/dashboard-icons/add.png',
        label: 'Add',
        gradient: 'from-[#DBEAFE] to-[#EFF6FF]',
    },
    {
        image: '/dashboard-icons/scan.png',
        label: 'Scan',
        href: receiptScan.url(),
        gradient: 'from-[#CCFBF1] to-[#F0FDFA]',
    },
    {
        image: '/dashboard-icons/chat.png',
        label: 'Chat',
        href: chatIndex.url(),
        gradient: 'from-[#EDE9FE] to-[#F5F3FF]',
    },
    {
        image: '/dashboard-icons/meals.png',
        label: 'Meals',
        href: mealsIndex.url(),
        gradient: 'from-[#FFEDD5] to-[#FFF7ED]',
    },
    {
        image: '/dashboard-icons/reminder.png',
        label: 'Reminder',
        href: remindersIndex.url(),
        gradient: 'from-[#FEF9C3] to-[#FEFCE8]',
    },
    {
        image: '/dashboard-icons/plan.png',
        label: 'Plan',
        href: budgetIndex.url(),
        gradient: 'from-[#DCFCE7] to-[#F0FDF4]',
    },
];
</script>

<template>

    <Head :title="t('Dashboard')" />

    <Transition name="mini-header">
        <div v-if="isScrolled"
            class="fixed inset-x-0 top-0 z-20 mx-auto flex h-16 w-full max-w-lg items-center justify-between bg-[#1F4AC2] px-6 text-white shadow-md">
            <div>
                <p class="text-xs text-blue-100">{{ t('Good morning,') }}</p>
                <h1 class="text-lg font-semibold">{{ userName }}</h1>
            </div>
            <div class="flex items-center gap-2">
                <LanguageSwitcher :floating="false" />
                <div class="flex h-9 w-9 items-center justify-center rounded-full bg-white text-xs font-semibold text-[#2457DA]">
                    {{ userName.slice(0, 2).toUpperCase() }}
                </div>
            </div>
        </div>
    </Transition>

    <div class="mx-auto flex min-h-screen w-full max-w-lg flex-col bg-app pb-24 text-app-heading">
        <div class="mx-6 mt-5 flex items-center justify-between">
            <div>
                <p class="text-sm text-[#637083]">{{ t('Good morning,') }}</p>
                <h1 class="text-2xl font-semibold">{{ userName }}</h1>
            </div>
            <div class="flex items-center gap-2">
                <LanguageSwitcher :floating="false" />
                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-white text-xs font-semibold text-[#2457DA] shadow-sm">
                    {{ userName.slice(0, 2).toUpperCase() }}
                </div>
            </div>
        </div>

        <header
            class="relative mx-5 mt-5 overflow-hidden rounded-2xl bg-gradient-to-br from-[#11297A] via-[#1F4AC2] to-[#2969E6] px-6 py-7 text-white shadow-[0_16px_36px_rgba(31,74,194,0.2)]">
            <div
                ref="wealthChartElement"
                class="pointer-events-none absolute right-3 top-4 h-16 w-32"
                aria-hidden="true"
            ></div>
            <div class="relative">
                <p class="text-sm text-blue-100">{{ t('Estimasi kekayaan bersih') }}</p>
                <p class="mt-1 text-[32px] font-semibold tracking-tight">Rp {{ formatCurrency(summary.balance) }}</p>
                <p
                    class="mt-1 text-xs font-medium"
                    :class="wealthTrend.change_percentage >= 0 ? 'text-emerald-200' : 'text-red-200'"
                >
                    {{ wealthTrend.change_percentage >= 0 ? '+' : '' }}{{ wealthTrend.change_percentage }}%
                    {{ t('dibanding 1 bulan lalu') }}
                </p>
                <p class="mt-1 text-xs text-blue-100">
                    {{ t('Includes IDR') }} {{ formatCurrency(summary.asset_value) }} {{ t('in assets') }}
                </p>
            </div>
        </header>

        <nav class="mx-5 mt-5 grid grid-cols-3 gap-x-3 gap-y-5 rounded-2xl bg-white px-3 py-4 text-center text-xs font-medium text-[#26354C]">
            <div v-for="item in shortcuts" :key="item.label">
                <Link v-if="item.href" :href="item.href" class="block">
                    <span
                        class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-br shadow-[inset_0_1px_3px_rgba(255,255,255,0.45),inset_0_-2px_5px_rgba(10,35,110,0.06)] transition hover:-translate-y-0.5"
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
                <template v-else>
                    <span
                        class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-br shadow-[inset_0_1px_3px_rgba(255,255,255,0.45),inset_0_-2px_5px_rgba(10,35,110,0.06)]"
                        :class="item.gradient"
                    >
                        <img
                            :src="item.image"
                            alt=""
                            class="h-8 w-8 object-contain"
                        />
                    </span>
                    <span class="mt-2 block">{{ t(item.label) }}</span>
                </template>
            </div>
        </nav>

        <main class="space-y-7 px-6 pt-6">
            <section>
                <div class="flex items-center justify-between px-1">
                    <h2 class="font-semibold">{{ t('Dana tersedia') }}</h2>
                    <div class="flex gap-3 text-sm text-[#2457DA]">
                        <Link :href="budgetIndex.url()">{{ t('Budget') }}</Link>
                        <Link :href="savingsIndex.url()">{{ t('Savings') }}</Link>
                    </div>
                </div>
                <article
                    v-if="fundCurrencies.length"
                    class="mt-4 flex w-full items-stretch gap-3 rounded-xl bg-white p-3"
                >
                    <div class="flex min-w-0 flex-1 flex-col items-center justify-center rounded-xl bg-[#F5F7FA] p-3 text-center">
                        <Banknote class="mb-2 text-[#2457DA]" :size="19" />
                        <span class="text-xs text-[#637083]">{{ t('Saldo') }} {{ activeFundCurrency }}</span>
                        <strong class="mt-1 text-base font-semibold tracking-tight">
                            {{ formatCurrency(funds[activeFundCurrency]?.free) }}
                        </strong>
                    </div>
                    <div class="min-w-0 flex-1 overflow-hidden rounded-xl bg-[#F5F7FA] px-3">
                        <div
                            class="h-[88px] snap-y snap-mandatory overflow-y-auto text-center [scrollbar-width:none] [&amp;::-webkit-scrollbar]:hidden"
                            @scroll.passive="updateActiveFundCurrency"
                        >
                            <div
                                v-for="currency in fundCurrencies"
                                :key="`${currency}-actual`"
                                class="flex h-[88px] snap-start flex-col items-center justify-center gap-1"
                            >
                                <Banknote class="text-[#2457DA]" :size="19" />
                                <span class="text-xs text-[#637083]">{{ t('Saldo aktual') }}</span>
                                <strong class="text-sm font-semibold">{{ formatCurrency(funds[currency].actual) }}</strong>
                                <ArrowUpDown :size="12" class="text-[#2457DA]" />
                            </div>
                            <div
                                v-for="currency in fundCurrencies"
                                :key="`${currency}-budget`"
                                class="flex h-[88px] snap-start flex-col items-center justify-center gap-1"
                            >
                                <Banknote class="text-[#2457DA]" :size="19" />
                                <span class="text-xs text-[#637083]">{{ t('Budget dicadangkan') }}</span>
                                <strong class="text-sm font-semibold">{{ formatCurrency(funds[currency].budget) }}</strong>
                                <ArrowUpDown :size="12" class="text-[#2457DA]" />
                            </div>
                            <div
                                v-for="currency in fundCurrencies"
                                :key="`${currency}-savings`"
                                class="flex h-[88px] snap-start flex-col items-center justify-center gap-1"
                            >
                                <Banknote class="text-[#2457DA]" :size="19" />
                                <span class="text-xs text-[#637083]">{{ t('Dana savings') }}</span>
                                <strong class="text-sm font-semibold">{{ formatCurrency(funds[currency].savings) }}</strong>
                                <ArrowUpDown :size="12" class="text-[#2457DA]" />
                            </div>
                        </div>
                    </div>
                </article>
            </section>
            <section>
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-semibold">{{ t('This month') }}</h2><span class="text-sm text-[#637083]">{{ new
                        Date().toLocaleDateString(locale === 'id' ? 'id-ID' : 'en-US', { month: 'short', year: 'numeric' }) }}</span>
                </div>
                <div class="mt-4 grid grid-cols-2 gap-3">
                    <div class="rounded-xl bg-[#F5F7FA] p-4">
                        <p class="flex items-center gap-1 text-sm text-[#16815B]">
                            <ArrowDown :size="15" />{{ t('Income') }}
                        </p>
                        <p class="mt-2 text-lg font-semibold">Rp {{ formatCurrency(summary.income) }}</p>
                    </div>
                    <div class="rounded-xl bg-[#F5F7FA] p-4">
                        <p class="flex items-center gap-1 text-sm text-[#B34735]">
                            <ArrowUp :size="15" />{{ t('Expense') }}
                        </p>
                        <p class="mt-2 text-lg font-semibold">Rp {{ formatCurrency(summary.expense) }}</p>
                    </div>
                </div>
            </section>
            <section>
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-semibold">{{ t('Spending this week') }}</h2>
                    <button class="text-sm text-[#2457DA]" @click="router.visit(categoriesIndex.url())">
                        {{ t('Details') }}
                    </button>
                </div>
                <SpendingCategoryChart :days="weeklySpending" />
            </section>
            <section>
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-semibold">{{ t('Recent transactions') }}</h2>
                    <Link :href="transactionsIndex.url()" class="text-sm text-[#2457DA]">{{ t('See all') }}</Link>
                </div>
                <div v-if="transactions.length" class="mt-4 space-y-4">
                    <div v-for="transaction in transactions" :key="transaction.id" class="flex items-center gap-4"><span
                            class="flex h-9 w-9 items-center justify-center rounded-xl bg-[#F5F7FA]"
                            :class="transaction.type === 'income' ? 'text-[#16815B]' : 'text-[#B34735]'">
                            <component :is="transaction.type === 'income' ? ArrowDown : ArrowUpRight" :size="20" />
                        </span>
                        <div class="flex-1">
                            <p class="text-sm font-medium">{{ transaction.note || t('Transaction') }}</p>
                            <p class="text-xs text-[#637083]">{{ transactionCategory(transaction) }} · {{
                                transaction.date }}
                            </p>
                        </div><strong class="text-sm" :class="transaction.type === 'income' ? 'text-[#16815B]' : ''">{{
                            transactionAmount(transaction) }}</strong>
                    </div>
                </div>
                <p v-else class="mt-4 text-sm text-[#637083]">{{ t('No transactions yet.') }}</p>
            </section>
        </main>

        <section class="mt-7 px-6">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold">{{ t('Accounts') }}</h2>
                <Link :href="accountsIndex.url()" class="flex items-center text-sm font-medium text-[#2457DA]">{{ t('See all') }}
                    <ChevronRight :size="16" />
                </Link>
            </div>
            <div v-if="accounts.length" class="account-slider mt-4 flex snap-x snap-mandatory gap-4 overflow-x-auto pb-3">
                <article
                    v-for="(account, index) in accounts"
                    :key="account.id"
                    class="account-card relative isolate flex h-40 w-[280px] shrink-0 snap-start flex-col justify-between overflow-hidden rounded-2xl p-5 text-white"
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
            <p v-else class="mt-4 text-sm text-[#637083]">{{ t('No accounts yet.') }}</p>
        </section>
        <section class="mt-7 px-6">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold">{{ t('Assets') }}</h2>
                <Link :href="assetsIndex.url()" class="flex items-center text-sm font-medium text-[#2457DA]">
                    {{ t('See all') }} <ChevronRight :size="16" />
                </Link>
            </div>
            <div v-if="assets.length" class="mt-4 space-y-3">
                <Link
                    v-for="asset in assets"
                    :key="asset.id"
                    :href="assetShow.url(asset.id)"
                    class="flex items-center justify-between rounded-xl bg-[#F5F7FA] p-4"
                >
                    <span class="font-medium">{{ asset.name }}</span>
                    <span class="text-sm font-semibold">{{ asset.currency }} {{ formatCurrency(asset.current_value) }}</span>
                </Link>
            </div>
            <p v-else class="mt-4 text-sm text-[#637083]">{{ t('No assets yet.') }}</p>
        </section>
        <div v-if="showTransactionModal" class="fixed inset-0 z-30 flex items-end bg-black/30"
            @click.self="showTransactionModal = false">
            <form class="mx-auto w-full max-w-lg rounded-t-[28px] bg-white px-6 pb-8 pt-3 text-[#152338]"
                @submit.prevent="submitTransaction">
                <div class="mx-auto mb-5 h-1 w-9 rounded-full bg-[#D5DBE5]"></div>
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-xs font-medium text-[#637083]">Step {{ transactionStep }} of 2</p>
                        <h2 class="text-[22px] font-semibold">{{ transactionForm.meal_record_id ? 'Catat pengeluaran' : 'New transaction' }}</h2>
                    </div><button type="button" class="text-2xl text-[#637083]"
                        @click="showTransactionModal = false">×</button>
                </div>
                <template v-if="transactionStep === 1">
                    <div class="mt-5 grid grid-cols-2 rounded-xl bg-[#F5F7FA] p-1"><button
                            v-for="type in ['expense', 'income']" :key="type" type="button"
                            class="rounded-lg py-3 text-sm font-semibold capitalize"
                            :class="transactionForm.type === type ? 'bg-white text-[#2457DA] shadow-sm' : 'text-[#637083]'"
                            @click="transactionForm.type = type">{{ type }}</button></div>
                    <button type="button" class="mt-6 h-[53px] w-full rounded-xl bg-[#2457DA] text-white"
                        @click="transactionStep = 2">Continue</button>
                </template>
                <template v-else-if="transactionStep === 2">
                    <label class="mt-5 block">Account
                        <SelectInput v-model="transactionForm.account_id"
                            :options="accounts.map((account) => ({ label: account.name, value: account.id }))"
                            placeholder="Select account" required />
                    </label>
                    <div class="mt-6 grid grid-cols-2 gap-3"><button type="button"
                            class="h-[53px] rounded-xl border border-[#2457DA] text-[#2457DA]"
                            @click="transactionStep = 1">Back</button><button type="button"
                            class="h-[53px] rounded-xl bg-[#2457DA] text-white"
                            @click="transactionStep = 3">Continue</button></div>
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
                    <label class="mt-5 block text-sm text-[#637083]">Amount
                        <FormInput v-model="transactionForm.amount" type="number" min="0.01" step="0.01" required
                            placeholder="Rp 0" variant="amount" />
                    </label>
                    <label class="mt-5 block">Title
                        <FormInput v-model="transactionForm.title" placeholder="What was this transaction for?"
                            required />
                        <p v-if="transactionForm.meal_record_id" class="mt-1 text-xs text-[#637083]">Harga aktual dan akun pembayaran ditentukan di sini.</p>
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
                    <div class="mt-6 grid grid-cols-2 gap-3"><button type="button"
                            class="h-[53px] rounded-xl border border-[#2457DA] text-[#2457DA]"
                            @click="transactionStep = 2">Back</button><button type="submit"
                            :disabled="transactionForm.processing"
                            class="h-[53px] rounded-xl bg-[#2457DA] text-white disabled:cursor-not-allowed disabled:opacity-60"><span
                                v-if="transactionForm.processing">Saving...</span><span v-else>Create
                                transaction</span></button></div>
                    <p class="mt-3 text-center text-xs text-[#637083]">You can edit this transaction anytime.</p>
                </template>
            </form>
        </div>
        <MobileBottomNav @add="openTransactionModal" />
    </div>
</template>

<style scoped>
.account-slider {
    scrollbar-width: none;
}

.account-slider::-webkit-scrollbar {
    display: none;
}

.account-card {
    background: linear-gradient(135deg, #2457da 0%, #1686dc 55%, #25b8dc 100%);
    isolation: isolate;
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

.account-card--1 {
    background: linear-gradient(135deg, #a92346 0%, #dc3b4f 55%, #f27b69 100%);
}

.account-card--2 {
    background: linear-gradient(135deg, #a96312 0%, #d99724 55%, #efc35d 100%);
}

.account-card--3 {
    background: linear-gradient(135deg, #11664d 0%, #16966c 55%, #54c995 100%);
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
