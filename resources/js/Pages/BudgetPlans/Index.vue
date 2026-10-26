<script setup>
import { computed, ref, watch } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import {
    ArrowLeft,
    CalendarDays,
    ChevronDown,
    ChevronRight,
    Eye,
    PiggyBank,
    Plus,
    ShoppingBag,
    Target,
    Wallet,
} from 'lucide-vue-next';
import { dashboard } from '@/routes';
import { create as createSavingPage, index as savingsIndex } from '@/routes/savings';
import { reverse as reverseClaim } from '@/routes/budget-claims';
import {
    index as budgetPlansIndex,
    status as statusPlan,
    store as storePlan,
    update as updatePlan,
    create as createPlanPage,
    show as showPlanPage,
} from '@/routes/budget-plans';
import {
    claim as claimPeriod,
    close as closePeriod,
    fund as fundPeriod,
    release as releasePeriod,
} from '@/routes/budget-periods';
import { store as storeTransaction } from '@/routes/transactions';
import MobileBottomNav from '@/Components/MobileBottomNav.vue';

const props = defineProps({
    plans: { type: Array, default: () => [] },
    legacyBudgets: { type: Array, default: () => [] },
    accounts: { type: Array, default: () => [] },
    categories: { type: Array, default: () => [] },
    savings: { type: Array, default: () => [] },
    createMode: { type: Boolean, default: false },
    selectedPlanId: { type: Number, default: null },
    totals: { type: Object, default: () => ({}) },
    fundingNotices: { type: Array, default: () => [] },
});

const localDate = (date) => {
    const parts = new Intl.DateTimeFormat('en-CA', {
        timeZone: 'Asia/Jakarta',
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
    }).formatToParts(date);
    const values = Object.fromEntries(parts.map((part) => [part.type, part.value]));

    return `${values.year}-${values.month}-${values.day}`;
};
const today = localDate(new Date());
const formatMoney = (amount) => new Intl.NumberFormat('id-ID').format(Number(amount ?? 0));
const displayAccountCurrency = (account) => account.currency
    ?? (account.type === 'investment' ? 'mata uang belum diatur' : 'IDR');
const periodUsagePercentage = (period) => {
    const allocation = Number(period.allocated_amount ?? 0);
    const spent = Number(period.spent ?? 0);

    if (allocation <= 0) {
        return 0;
    }

    return Math.min(Math.max((spent / allocation) * 100, 0), 100);
};
const periodUsageColor = (period) => {
    const percentage = periodUsagePercentage(period);

    if (percentage >= 100) {
        return 'bg-[#B34735]';
    }

    if (percentage >= 75) {
        return 'bg-[#D99A1B]';
    }

    return 'bg-[#2457DA]';
};
const periodStatusLabel = (status) => ({
    awaiting_funding: 'Menunggu dana',
    open: 'Berjalan',
    funded: 'Didanai',
    closed: 'Selesai',
}[status] ?? status);
const planStatusLabel = (status) => ({
    active: 'Aktif',
    paused: 'Dijeda',
    ended: 'Dihentikan',
    completed: 'Selesai',
}[status] ?? status);
const planStatusColor = (status) => ({
    active: 'bg-budget-success-surface text-budget-success',
    paused: 'bg-[#FFF4DB] text-[#9A6700]',
    ended: 'bg-[#EEF1F5] text-[#637083]',
    completed: 'bg-budget-goal-surface text-budget-goal',
}[status] ?? 'bg-[#EEF1F5] text-[#637083]');
const selectedPlan = computed(() => props.plans.find(
    (plan) => Number(plan.id) === Number(props.selectedPlanId),
) ?? null);
const availableCurrencies = computed(() => Object.keys(props.totals));
const selectedCurrency = ref(
    props.totals.IDR
        ? 'IDR'
        : Object.keys(props.totals)[0] ?? 'IDR',
);
const secondaryCurrency = computed(() => availableCurrencies.value.find(
    (currency) => currency !== selectedCurrency.value,
) ?? null);
const activePlanFilter = ref('all');
const monthLabel = new Intl.DateTimeFormat('id-ID', {
    month: 'long',
    year: 'numeric',
    timeZone: 'Asia/Jakarta',
}).format(new Date());
const activePlanCount = computed(() => props.plans.filter(
    (plan) => plan.status === 'active',
).length);
const filteredPlans = computed(() => {
    if (activePlanFilter.value === 'active') {
        return props.plans.filter((plan) => plan.status === 'active');
    }

    if (activePlanFilter.value === 'completed') {
        return props.plans.filter((plan) => ['completed', 'ended'].includes(plan.status));
    }

    return props.plans;
});
const formatCurrency = (amount, currency = 'IDR') => {
    const safeCurrency = String(currency || 'IDR').trim();
    const value = Number(amount ?? 0);

    try {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: safeCurrency,
            maximumFractionDigits: 0,
        }).format(value);
    } catch {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            maximumFractionDigits: 0,
        }).format(value);
    }
};
const formatDate = (date) => {
    if (!date) {
        return '';
    }

    return new Intl.DateTimeFormat('id-ID', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
        timeZone: 'Asia/Jakarta',
    }).format(new Date(`${date}T00:00:00+07:00`));
};
const isDetailPage = computed(() => props.selectedPlanId !== null);
const currentPeriodForPlan = (plan) =>
    plan.periods?.find((period) => period.status === 'open')
        ?? plan.periods?.find((period) => period.status === 'awaiting_funding')
        ?? plan.periods?.[0]
        ?? null;
const goalProgressPercentage = (plan) => {
    const target = Number(plan.target_amount ?? 0);

    return target > 0
        ? Math.min((Number(plan.goal_funded ?? 0) / target) * 100, 100)
        : 0;
};
const showCreate = ref(props.createMode);
const activeStep = ref(1);
const steps = computed(() => [
    'Jenis rencana',
    form.type === 'goal' ? 'Target & savings' : 'Nominal & akun',
    'Jadwal',
    'Pengingat & cek',
]);
const editingId = ref(null);
const activeClaim = ref(null);
const claimParts = ref([{ saving_id: '', amount: '' }]);
const claimTotal = computed(() => claimParts.value.reduce(
    (total, part) => total + Number(part.amount || 0),
    0,
));
const releaseAmount = ref('');
const actionError = ref('');
const showTransactionModal = ref(false);
const transactionForm = useForm({
    type: 'expense',
    amount: '',
    date: today,
    title: 'Belanja',
    note: '',
    account_id: '',
    category_id: '',
    budget_period_id: '',
});
const selectedTransactionPeriod = computed(() => selectedPlan.value?.periods?.find(
    (period) => Number(period.id) === Number(transactionForm.budget_period_id),
) ?? null);
const form = useForm({
    name: '',
    type: 'spending',
    amount: '',
    target_amount: '',
    target_date: '',
    saving_id: '',
    confirm_existing_funds: false,
    contribution_amount: '',
    currency: props.accounts[0]?.currency ?? 'IDR',
    category_id: '',
    financial_account_id: props.accounts[0]?.id ?? '',
    is_recurring: true,
    interval: 1,
    starts_on: today,
    ends_on: '',
    timezone: 'Asia/Jakarta',
    notes: '',
    remind: false,
    reminder_purpose: 'budget_savings_claim',
    items: [],
});
watch(() => props.createMode, (createMode) => {
    showCreate.value = createMode;

    if (createMode) {
        editingId.value = null;
        activeStep.value = 1;
        form.reset();
        form.clearErrors();
        form.currency = props.accounts[0]?.currency ?? 'IDR';
        form.financial_account_id = props.accounts[0]?.id ?? '';
        form.starts_on = today;
    }
});

const availableSavings = computed(() => props.savings.filter(
    (saving) => saving.currency === form.currency
        && (!saving.goal_plan || Number(saving.id) === Number(form.saving_id)),
));
const scheduledDates = computed(() => {
    if (form.type !== 'goal' || !form.starts_on || !form.target_date) {
        return [];
    }

    const [year, month, day] = form.starts_on.split('-').map(Number);
    const dates = [];

    for (let index = 0; index < 240; index += 1) {
        const monthStart = new Date(Date.UTC(year, month - 1 + index * Number(form.interval || 1), 1));
        const lastDay = new Date(Date.UTC(
            monthStart.getUTCFullYear(),
            monthStart.getUTCMonth() + 1,
            0,
        )).getUTCDate();
        const date = [
            monthStart.getUTCFullYear(),
            String(monthStart.getUTCMonth() + 1).padStart(2, '0'),
            String(Math.min(day, lastDay)).padStart(2, '0'),
        ].join('-');

        if (date > form.target_date) {
            break;
        }

        dates.push(date);

        if (!form.is_recurring) {
            break;
        }
    }

    return dates;
});
const suggestedAmount = computed(() => {
    const saving = props.savings.find((item) => Number(item.id) === Number(form.saving_id));
    const remaining = Math.max(0, Number(form.target_amount || 0) - Number(saving?.balance || 0));

    return scheduledDates.value.length > 0
        ? remaining / scheduledDates.value.length
        : 0;
});
const availableCategories = computed(() => props.categories.filter(
    (category) => category.type === 'expense',
));
const accountChanged = () => {
    form.currency = props.accounts.find(
        (account) => Number(account.id) === Number(form.financial_account_id),
    )?.currency ?? 'IDR';
};
const submitPlan = () => {
    const options = {
        preserveScroll: true,
        onError: (errors) => {
            const fields = Object.keys(errors);
            activeStep.value = fields.some((field) => ['amount', 'target_amount', 'saving_id', 'confirm_existing_funds', 'items'].includes(field))
                ? 2
                : fields.some((field) => ['starts_on', 'ends_on', 'target_date', 'interval', 'timezone'].includes(field))
                    ? 3
                    : 4;
        },
        onSuccess: () => {
            showCreate.value = false;
            editingId.value = null;
            activeStep.value = 1;
            form.reset();
        },
    };

    if (editingId.value) {
        form.patch(updatePlan.url(editingId.value), options);
    } else {
        form.post(storePlan.url(), options);
    }
};
const closeForm = () => {
    if (props.createMode) {
        router.visit(budgetPlansIndex.url());
        return;
    }

    showCreate.value = false;
    editingId.value = null;
    activeStep.value = 1;
    form.clearErrors();
};
const goBackFromForm = () => {
    if (activeStep.value > 1) {
        activeStep.value -= 1;
        form.clearErrors();
        return;
    }

    closeForm();
};
const nextStep = () => {
    form.clearErrors();

    if (activeStep.value === 1 && !form.name.trim()) {
        form.setError('name', 'Masukkan nama rencana.');
        return;
    }

    if (activeStep.value === 2) {
        if (!form.financial_account_id) {
            form.setError('financial_account_id', 'Pilih akun sumber.');
            return;
        }

        if (form.type === 'spending' && Number(form.amount) <= 0) {
            form.setError('amount', 'Masukkan anggaran yang lebih dari nol.');
            return;
        }

        if (form.type === 'goal' && (!form.saving_id || Number(form.target_amount) <= 0)) {
            form.setError('target_amount', 'Isi target dan pilih savings tujuan.');
            return;
        }
    }

    if (activeStep.value === 3) {
        const hasInvalidDates = !form.starts_on
            || (form.type === 'goal' && (!form.target_date || form.target_date < form.starts_on))
            || (form.type === 'spending' && !form.is_recurring
                && (!form.ends_on || form.ends_on < form.starts_on));

        if (hasInvalidDates) {
            form.setError('starts_on', 'Periksa tanggal mulai, tenggat, dan tanggal akhir.');
            return;
        }
    }

    activeStep.value = Math.min(activeStep.value + 1, steps.value.length);
};
const editPlan = (plan) => {
    editingId.value = plan.id;
    form.clearErrors();
    form.name = plan.name;
    form.type = plan.type;
    form.amount = plan.amount ?? '';
    form.target_amount = plan.target_amount ?? '';
    form.target_date = plan.target_date ?? '';
    form.saving_id = plan.saving_id ?? '';
    form.contribution_amount = plan.contribution_amount ?? '';
    form.currency = plan.currency;
    form.category_id = plan.category_id ?? '';
    form.financial_account_id = plan.financial_account_id;
    form.is_recurring = plan.is_recurring;
    form.interval = plan.interval;
    form.starts_on = plan.starts_on;
    form.ends_on = plan.ends_on ?? '';
    form.timezone = plan.timezone;
    form.notes = plan.notes ?? '';
    form.items = plan.items.map((item) => ({
        name: item.name,
        amount: item.amount,
        category_id: item.category_id ?? '',
    }));
    showCreate.value = true;
    activeStep.value = 1;
    window.scrollTo({ top: 0, behavior: 'smooth' });
};
const changeStatus = (plan, status) => {
    router.post(
        statusPlan.url(plan.id),
        { status },
        {
            preserveScroll: true,
            onError: (errors) => {
                actionError.value = Object.values(errors)[0] ?? 'Status gagal diubah.';
            },
        },
    );
};
const addItem = () => form.items.push({ name: '', amount: '', category_id: '' });
const openTransactionModal = (plan, period) => {
    transactionForm.reset();
    transactionForm.clearErrors();
    transactionForm.type = 'expense';
    transactionForm.amount = '';
    transactionForm.date = today >= period.starts_on && today <= period.ends_on
        ? today
        : period.starts_on;
    transactionForm.title = 'Belanja';
    transactionForm.note = '';
    transactionForm.account_id = period.financial_account_id;
    transactionForm.category_id = plan.category_id ?? '';
    transactionForm.budget_period_id = period.id;
    showTransactionModal.value = true;
};
const closeTransactionModal = () => {
    showTransactionModal.value = false;
    transactionForm.clearErrors();
};
const submitTransaction = () => {
    transactionForm.post(storeTransaction.url(), {
        preserveScroll: true,
        onSuccess: () => {
            showTransactionModal.value = false;
            transactionForm.reset();
        },
    });
};
const openClaim = (period) => {
    activeClaim.value = period.id;
    claimParts.value = [{ saving_id: '', amount: period.remaining }];
    releaseAmount.value = period.remaining;
    actionError.value = '';
};
const submitClaim = () => {
    actionError.value = '';
    router.post(
        claimPeriod.url(activeClaim.value),
        {
            idempotency_key: crypto.randomUUID(),
            parts: claimParts.value,
        },
        {
            preserveScroll: true,
            onError: (errors) => {
                actionError.value = Object.values(errors)[0] ?? 'Klaim gagal.';
            },
            onSuccess: () => {
                activeClaim.value = null;
            },
        },
    );
};
const actOnPeriod = (route, period, data = {}) => {
    actionError.value = '';
    router.post(
        route.url(period.id),
        { idempotency_key: crypto.randomUUID(), ...data },
        {
            preserveScroll: true,
            onError: (errors) => {
                actionError.value = Object.values(errors)[0] ?? 'Aksi gagal.';
            },
            onSuccess: () => {
                activeClaim.value = null;
            },
        },
    );
};
</script>

<template>
    <div class="mx-auto min-h-screen w-full max-w-lg bg-budget-page pb-28 text-budget-heading">
        <Head
            :title="showCreate
                ? (editingId ? 'Ubah Budget Plan' : 'Budget Plan baru')
                : (isDetailPage ? selectedPlan?.name ?? 'Detail Budget Plan' : 'Budget Plan')"
        />
        <header
            v-if="!showCreate && !isDetailPage"
            class="grid grid-cols-[52px_1fr_104px] items-center gap-3 px-5 pb-2 pt-6"
        >
            <Link
                :href="dashboard.url()"
                aria-label="Kembali ke dashboard"
                class="flex h-[52px] w-[52px] items-center justify-center rounded-[20px] bg-budget-surface text-budget-ink shadow-[0_8px_24px_rgba(41,82,160,0.08)]"
            >
                <ArrowLeft :size="23" />
            </Link>
            <h1 class="text-center text-xl font-bold tracking-tight sm:text-2xl">
                Budget Planner
            </h1>
            <Link
                :href="savingsIndex.url()"
                class="inline-flex min-h-[46px] items-center justify-center gap-2 rounded-full border-2 border-budget-primary bg-budget-surface px-3 text-sm font-semibold text-budget-link sm:px-4"
            >
                <PiggyBank :size="20" />
                Savings
            </Link>
        </header>
        <header v-else-if="!showCreate" class="flex items-center justify-between bg-white px-6 py-5">
            <Link :href="budgetPlansIndex.url()" class="text-sm font-semibold text-[#2457DA]">
                ← Semua plan
            </Link>
            <h1 class="text-lg font-semibold">Detail plan</h1>
            <Link :href="savingsIndex.url()" class="text-sm font-semibold text-[#2457DA]">Savings</Link>
        </header>

        <main
            class="flex flex-col gap-5 px-5 pt-4"
            :class="!showCreate && !isDetailPage ? 'pb-4' : 'sm:px-6 sm:pt-6'"
        >
            <header v-if="showCreate" class="flex items-center gap-4">
                <button
                    type="button"
                    class="rounded-full p-2 text-[#2457DA] hover:bg-[#F5F7FA]"
                    :aria-label="activeStep > 1 ? 'Kembali ke langkah sebelumnya' : 'Kembali ke daftar budget plan'"
                    @click="goBackFromForm"
                >
                    <ArrowLeft :size="22" />
                </button>
                <div class="min-w-0 flex-1">
                    <p class="text-xs font-medium text-[#637083]">Budget Plan</p>
                    <h1 class="text-xl font-semibold">
                        {{ editingId ? 'Ubah rencana' : steps[activeStep - 1] }}
                    </h1>
                </div>
                <span class="shrink-0 text-sm font-medium text-[#637083]">
                    Langkah {{ activeStep }} dari {{ steps.length }}
                </span>
            </header>
            <div v-if="showCreate" class="flex gap-2" aria-hidden="true">
                <div
                    v-for="step in steps.length"
                    :key="step"
                    class="h-1.5 flex-1 rounded-full"
                    :class="activeStep >= step ? 'bg-[#2457DA]' : 'bg-slate-200'"
                />
            </div>

            <section v-if="!showCreate && !isDetailPage && fundingNotices.length" class="rounded-2xl bg-[#FFF3DE] p-4 text-sm">
                <h2 class="font-semibold">Budget perlu didanai</h2>
                <Link
                    v-for="notice in fundingNotices"
                    :key="notice.id"
                    :href="notice.data.url"
                    class="mt-2 block text-[#8A5900]"
                >{{ notice.data.title }}</Link>
            </section>
            <div v-if="!showCreate && !isDetailPage" class="flex justify-center">
                <div class="inline-flex items-center gap-3 rounded-[20px] bg-budget-surface/80 px-5 py-3 text-budget-ink shadow-[0_6px_22px_rgba(41,82,160,0.06)]">
                    <CalendarDays :size="21" class="text-[#52627E]" />
                    <span class="font-semibold capitalize">{{ monthLabel }}</span>
                    <ChevronDown :size="18" class="text-[#52627E]" />
                </div>
            </div>

            <section
                v-if="!showCreate && !isDetailPage && totals[selectedCurrency]"
                class="relative isolate overflow-hidden rounded-[28px] bg-budget-header p-6 text-white shadow-[0_18px_42px_rgba(36,87,218,0.22)] sm:p-8"
            >
                <div class="pointer-events-none absolute -right-10 -top-16 -z-10 h-56 w-56 rounded-full bg-white/10"></div>
                <div class="pointer-events-none absolute -right-16 top-28 -z-10 h-48 w-48 rounded-full bg-budget-header-middle/25"></div>
                <div class="relative">
                    <p class="flex items-center gap-3 pr-32 text-base font-medium text-white/90 sm:text-lg">
                        Uang tersedia
                        <Eye :size="20" />
                    </p>
                    <div
                        v-if="availableCurrencies.length > 1"
                        class="absolute right-0 top-0 flex h-10 w-[116px] shrink-0 rounded-full bg-white/15 p-1"
                    >
                        <ButtonPrimary
                            v-for="currency in availableCurrencies"
                            :key="currency"
                            type="button"
                            :variant="selectedCurrency === currency ? 'primary' : 'secondary'"
                            class="!min-h-0 !w-1/2 !rounded-full !px-1 !py-2 !text-xs !shadow-none"
                            :class="selectedCurrency === currency
                                ? '!bg-white !text-budget-link'
                                : '!border-transparent !bg-transparent !text-white'"
                            @click="selectedCurrency = currency"
                        >{{ currency || 'IDR' }}</ButtonPrimary>
                    </div>
                    <p class="mt-3 text-[clamp(2.3rem,10vw,3.6rem)] font-bold leading-none tracking-tight">
                        {{ formatCurrency(totals[selectedCurrency].free, selectedCurrency) }}
                    </p>
                    <p class="mt-2 text-base text-white/75 sm:text-lg">Siap dialokasikan</p>
                </div>
                <div class="mt-7 grid grid-cols-3 border-t border-white/25 pt-5">
                    <div class="pr-2">
                        <p class="text-xs text-white/75 sm:text-sm">Saldo aktual</p>
                        <p class="mt-1 text-sm font-bold sm:text-lg">
                            {{ formatCurrency(totals[selectedCurrency].actual, selectedCurrency) }}
                        </p>
                    </div>
                    <div class="border-l border-white/25 px-3 sm:px-5">
                        <p class="text-xs text-white/75 sm:text-sm">Budget tersisa</p>
                        <p class="mt-1 text-sm font-bold sm:text-lg">
                            {{ formatCurrency(totals[selectedCurrency].budget, selectedCurrency) }}
                        </p>
                    </div>
                    <div class="border-l border-white/25 pl-3 sm:pl-5">
                        <p class="text-xs text-white/75 sm:text-sm">Savings</p>
                        <p class="mt-1 text-sm font-bold sm:text-lg">
                            {{ formatCurrency(totals[selectedCurrency].savings, selectedCurrency) }}
                        </p>
                    </div>
                </div>
            </section>

            <Link
                v-if="!showCreate && !isDetailPage && secondaryCurrency && totals[secondaryCurrency]"
                :href="savingsIndex.url()"
                class="flex items-center gap-4 rounded-[24px] bg-white p-4 shadow-[0_8px_28px_rgba(41,82,160,0.08)]"
            >
                <span class="flex h-14 w-14 items-center justify-center rounded-[20px] bg-budget-icon-surface text-budget-primary">
                    <Wallet :size="25" />
                </span>
                <span class="min-w-0 flex-1">
                    <span class="block text-sm text-budget-muted">Saldo {{ secondaryCurrency || 'IDR' }}</span>
                    <strong class="block text-xl font-bold text-budget-heading">
                        {{ formatCurrency(totals[secondaryCurrency].actual, secondaryCurrency) }}
                    </strong>
                </span>
                <span class="flex h-11 w-11 items-center justify-center rounded-full bg-budget-control-surface text-budget-link">
                    <ChevronRight :size="21" />
                </span>
            </Link>

            <section v-if="!showCreate && !isDetailPage" class="flex flex-col gap-4 pt-1">
                <div class="flex items-center gap-3">
                    <h2 class="text-2xl font-bold tracking-tight sm:text-[28px]">Budget saya</h2>
                    <span class="rounded-full bg-budget-success-surface px-3 py-1.5 text-sm font-semibold text-budget-success">
                        {{ activePlanCount }} aktif
                    </span>
                </div>
                <div class="flex gap-2 overflow-x-auto pb-0.5">
                    <ButtonPrimary
                        v-for="filter in [
                            { value: 'all', label: 'Semua' },
                            { value: 'active', label: 'Aktif' },
                            { value: 'completed', label: 'Selesai' },
                        ]"
                        :key="filter.value"
                        type="button"
                        :variant="activePlanFilter === filter.value ? 'primary' : 'secondary'"
                        class="!min-h-11 !min-w-[92px] !rounded-full !px-5 !py-2 !font-medium"
                        :class="activePlanFilter === filter.value
                            ? '!border-budget-primary !bg-budget-primary'
                            : '!border-[#E2EAF8] !bg-white/60 !text-budget-muted'"
                        @click="activePlanFilter = filter.value"
                    >{{ filter.label }}</ButtonPrimary>
                </div>
            </section>

            <form
                v-if="showCreate"
                class="flex flex-col gap-4 rounded-2xl bg-white p-5 shadow-sm"
                @submit.prevent="submitPlan"
            >
                <p class="text-xs font-medium text-[#637083]">
                    Langkah {{ activeStep }} dari {{ steps.length }} · {{ steps[activeStep - 1] }}
                </p>

                <section v-if="activeStep === 1" class="flex flex-col gap-3">
                    <p class="text-sm text-[#637083]">Pilih apa yang ingin kamu rencanakan.</p>
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <button
                            v-for="option in [
                                { value: 'spending', title: 'Anggaran belanja', detail: 'Sisihkan dana untuk kebutuhan rutin atau satu kali.' },
                                { value: 'goal', title: 'Rencana tujuan', detail: 'Atur target, tenggat, dan setoran untuk tujuan.' },
                            ]"
                            :key="option.value"
                            type="button"
                            class="rounded-xl border p-4 text-left transition"
                            :class="form.type === option.value ? 'border-[#2457DA] bg-[#F3F6FF]' : 'border-[#DCE3EE]'"
                            :disabled="Boolean(editingId)"
                            @click="form.type = option.value"
                        >
                            <span class="block font-semibold">{{ option.title }}</span>
                            <span class="mt-1 block text-xs text-[#637083]">{{ option.detail }}</span>
                        </button>
                    </div>
                    <label class="text-sm">Nama rencana
                        <input v-model="form.name" class="field" required maxlength="255" placeholder="Contoh: Belanja bulanan" />
                    </label>
                    <p v-if="form.errors.name" class="text-sm text-red-600">{{ form.errors.name }}</p>
                </section>

                <section v-else-if="activeStep === 2" class="flex flex-col gap-3">
                    <label class="text-sm">Akun sumber
                        <select v-model="form.financial_account_id" class="field" required @change="accountChanged">
                            <option v-for="account in accounts" :key="account.id" :value="account.id">
                                {{ account.name }} ({{ displayAccountCurrency(account) }})
                            </option>
                        </select>
                    </label>
                    <p v-if="form.errors.financial_account_id" class="text-sm text-red-600">{{ form.errors.financial_account_id }}</p>
                    <template v-if="form.type === 'spending'">
                        <label class="text-sm">Anggaran per periode
                            <input v-model="form.amount" class="field" type="number" min="0.01" step="0.01" required placeholder="Contoh: 300000" />
                        </label>
                        <label class="text-sm">Kategori opsional
                            <select v-model="form.category_id" class="field">
                                <option value="">Tanpa kategori</option>
                                <option v-for="category in availableCategories" :key="category.id" :value="category.id">
                                    {{ category.name }}
                                </option>
                            </select>
                        </label>
                        <p v-if="form.errors.amount" class="text-sm text-red-600">{{ form.errors.amount }}</p>
                    </template>
                    <template v-else>
                    <label class="text-sm">Target tujuan
                        <input v-model="form.target_amount" class="field" type="number" min="0.01" step="0.01" required placeholder="Contoh: 6000000" />
                    </label>
                    <label class="text-sm">Savings tujuan
                        <select v-model="form.saving_id" class="field" required>
                            <option value="">Pilih savings</option>
                            <option v-for="saving in availableSavings" :key="saving.id" :value="saving.id">
                                {{ saving.name }}
                            </option>
                        </select>
                    </label>
                    <Link :href="createSavingPage.url()" class="text-sm text-[#2457DA]">
                        Belum ada savings? Buat di halaman Savings
                    </Link>
                    <label class="flex items-start gap-2 text-sm">
                        <input v-model="form.confirm_existing_funds" type="checkbox" />
                        Seluruh dana yang sudah ada di savings ini untuk tujuan tersebut.
                    </label>
                    <label class="text-sm">Setoran pilihan per tanggal (opsional)
                        <input v-model="form.contribution_amount" class="field" type="number" min="0.01" step="0.01" />
                    </label>
                    <p v-if="form.errors.saving_id || form.errors.target_amount || form.errors.confirm_existing_funds" class="text-sm text-red-600">
                        {{ form.errors.saving_id || form.errors.target_amount || form.errors.confirm_existing_funds }}
                    </p>
                    <div class="flex items-center justify-between">
                        <h3 class="text-sm font-semibold">Rincian biaya (opsional)</h3>
                        <ButtonPrimary type="button" variant="secondary" @click="addItem">Tambah item</ButtonPrimary>
                    </div>
                    <div v-for="(item, index) in form.items" :key="index" class="grid grid-cols-2 gap-2">
                        <input v-model="item.name" class="field" placeholder="Contoh: Tiket" required />
                        <input v-model="item.amount" class="field" type="number" min="0.01" step="0.01" required placeholder="Nominal" />
                        <ButtonPrimary type="button" variant="secondary" @click="form.items.splice(index, 1)">
                            Hapus item
                        </ButtonPrimary>
                    </div>
                    <p v-if="form.errors.items" class="text-sm text-red-600">{{ form.errors.items }}</p>
                    </template>
                </section>

                <section v-else-if="activeStep === 3" class="flex flex-col gap-3">
                <label class="text-sm">Tanggal mulai
                    <input v-model="form.starts_on" class="field" type="date" required />
                </label>
                <label v-if="form.type === 'goal'" class="text-sm">Tenggat tujuan
                    <input v-model="form.target_date" class="field" type="date" required />
                </label>
                <label class="flex items-center gap-2 text-sm">
                    <input v-model="form.is_recurring" type="checkbox" />
                    {{ form.type === 'goal' ? 'Jadwal setoran bulanan' : 'Ulangi bulanan' }}
                </label>
                <label v-if="form.is_recurring" class="text-sm">Interval bulan
                    <input v-model="form.interval" class="field" type="number" min="1" max="120" required />
                </label>
                <label v-if="form.type === 'spending' && !form.is_recurring" class="text-sm">Tanggal akhir
                    <input v-model="form.ends_on" class="field" type="date" required />
                </label>
                <div v-if="form.type === 'goal'" class="rounded-xl bg-[#F3F6FF] p-3 text-sm">
                    <p class="font-semibold">Jadwal setoran</p>
                    <p v-if="!scheduledDates.length" class="text-[#637083]">
                        Pilih tanggal mulai dan tenggat yang valid.
                    </p>
                    <p v-for="date in scheduledDates" :key="date">{{ date }}</p>
                    <p v-if="scheduledDates.length" class="mt-2 text-[#637083]">
                        Saran sekitar {{ form.currency }} {{ formatMoney(suggestedAmount) }} per tanggal.
                        Nilai final dihitung ulang saat periode dibuka.
                    </p>
                </div>
                <p v-if="form.errors.starts_on || form.errors.target_date || form.errors.ends_on" class="text-sm text-red-600">
                    {{ form.errors.starts_on || form.errors.target_date || form.errors.ends_on }}
                </p>
                </section>

                <section v-else class="flex flex-col gap-3">
                    <label class="flex items-center gap-2 text-sm">
                        <input v-model="form.remind" type="checkbox" />
                        {{ form.type === 'goal' ? 'Ingatkan saat jadwal setoran' : 'Aktifkan pengingat budget' }}
                    </label>
                    <label v-if="form.type === 'spending' && form.remind" class="text-sm">Tindakan pengingat
                        <select v-model="form.reminder_purpose" class="field">
                            <option value="budget_savings_claim">Klaim sisa di akhir periode</option>
                            <option value="budget_allocation">Danai di awal periode</option>
                        </select>
                    </label>
                    <label class="text-sm">Catatan (opsional)
                        <textarea v-model="form.notes" class="field" rows="3" placeholder="Tambahkan konteks untuk rencana ini" />
                    </label>
                    <div class="rounded-xl bg-[#F3F6FF] p-4 text-sm">
                        <p class="font-semibold">Ringkasan</p>
                        <p class="mt-2">{{ form.name || 'Rencana baru' }} · {{ form.type === 'goal' ? 'Tujuan' : 'Anggaran belanja' }}</p>
                        <p v-if="form.type === 'spending'">{{ form.currency }} {{ formatMoney(form.amount) }} per periode</p>
                        <p v-else>Target {{ form.currency }} {{ formatMoney(form.target_amount) }} · tenggat {{ form.target_date || 'belum dipilih' }}</p>
                        <p>Mulai {{ form.starts_on || 'belum dipilih' }} · {{ form.is_recurring ? `setiap ${form.interval} bulan` : 'satu kali' }}</p>
                        <p>Akun {{ accounts.find((account) => Number(account.id) === Number(form.financial_account_id))?.name || 'belum dipilih' }}</p>
                    </div>
                </section>
                <p v-for="(error, key) in form.errors" :key="key" class="text-sm text-red-600">
                    {{ error }}
                </p>
                <div class="flex gap-3">
                    <ButtonPrimary
                        v-if="activeStep > 1"
                        type="button"
                        variant="secondary"
                        class="flex-1 justify-center"
                        @click="activeStep -= 1; form.clearErrors()"
                    >Kembali</ButtonPrimary>
                    <ButtonPrimary
                        v-if="activeStep < steps.length"
                        type="button"
                        class="flex-1 justify-center"
                        @click="nextStep"
                    >Lanjut</ButtonPrimary>
                    <ButtonPrimary v-else type="submit" class="flex-1 justify-center" :disabled="form.processing">
                        {{ editingId ? 'Simpan perubahan' : 'Buat rencana' }}
                    </ButtonPrimary>
                </div>
            </form>

            <p
                v-if="!showCreate && !isDetailPage && !filteredPlans.length"
                class="rounded-2xl bg-white p-5 text-sm text-[#637083]"
            >
                {{ plans.length ? 'Tidak ada budget pada filter ini.' : 'Belum ada budget plan.' }}
            </p>
            <Link
                v-for="plan in filteredPlans"
                v-show="!showCreate && !isDetailPage"
                :key="`summary-${plan.id}`"
                :href="showPlanPage.url(plan.id)"
                class="block rounded-[26px] focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-budget-primary/25"
            >
                <article class="relative flex flex-col gap-4 overflow-hidden rounded-[26px] border border-budget-border bg-budget-surface p-5 shadow-[0_10px_28px_rgba(41,82,160,0.08)] transition hover:shadow-[0_14px_34px_rgba(41,82,160,0.14)] sm:p-6">
                <div class="relative flex items-start justify-between gap-3">
                    <div class="flex min-w-0 items-center gap-3.5">
                        <span
                            class="flex h-[58px] w-[58px] shrink-0 items-center justify-center rounded-[20px] bg-budget-icon-surface text-budget-primary"
                            :class="plan.type === 'goal'
                            ? 'bg-budget-goal-surface text-budget-goal'
                                : ''"
                        >
                            <component :is="plan.type === 'goal' ? Target : ShoppingBag" :size="27" />
                        </span>
                        <div class="min-w-0">
                            <h2 class="truncate text-lg font-bold tracking-tight text-budget-heading sm:text-xl">{{ plan.name }}</h2>
                            <p class="mt-0.5 truncate text-sm text-budget-muted">
                                {{ plan.type === 'goal' ? 'Rencana tujuan' : 'Anggaran belanja' }}
                            </p>
                            <span
                                class="mt-2 inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold"
                                :class="planStatusColor(plan.status)"
                            >
                                <span class="h-2 w-2 rounded-full bg-current"></span>
                                {{ planStatusLabel(plan.status) }}
                            </span>
                        </div>
                    </div>
                    <span class="shrink-0 pt-1 text-xs text-budget-muted">
                        {{ formatDate(plan.starts_on) }}
                    </span>
                </div>

                <div
                    v-if="plan.type === 'spending' && currentPeriodForPlan(plan)"
                    class="relative flex flex-col gap-3 pt-1"
                >
                    <div class="flex items-end justify-between gap-3">
                        <p class="min-w-0 truncate text-sm text-budget-muted">
                            <strong class="text-2xl font-bold tracking-tight text-budget-heading sm:text-[28px]">
                                {{ formatCurrency(currentPeriodForPlan(plan).spent, plan.currency) }}
                            </strong>
                            <span> / {{ formatCurrency(currentPeriodForPlan(plan).allocated_amount, plan.currency) }}</span>
                        </p>
                        <strong class="shrink-0 text-lg font-bold text-budget-heading">
                            {{ Math.round(periodUsagePercentage(currentPeriodForPlan(plan))) }}%
                        </strong>
                    </div>
                    <div class="h-2.5 overflow-hidden rounded-full bg-budget-progress-track">
                        <div
                            class="h-full rounded-full"
                            :class="periodUsageColor(currentPeriodForPlan(plan))"
                            :style="{ width: `${periodUsagePercentage(currentPeriodForPlan(plan))}%` }"
                        ></div>
                    </div>
                </div>
                <div v-else-if="plan.type === 'goal'" class="flex flex-col gap-3 pt-1">
                    <div class="flex items-end justify-between gap-3">
                        <p class="min-w-0 truncate text-sm text-budget-muted">
                            <strong class="text-2xl font-bold tracking-tight text-budget-heading">
                                {{ formatCurrency(plan.goal_funded, plan.currency) }}
                            </strong>
                            <span> / {{ formatCurrency(plan.target_amount, plan.currency) }}</span>
                        </p>
                        <strong class="shrink-0 text-lg font-bold text-budget-heading">
                            {{ Math.round(goalProgressPercentage(plan)) }}%
                        </strong>
                    </div>
                    <div class="h-2.5 overflow-hidden rounded-full bg-budget-progress-track">
                        <div
                            class="h-full rounded-full bg-budget-goal"
                            :style="{ width: `${goalProgressPercentage(plan)}%` }"
                        ></div>
                    </div>
                </div>
                </article>
            </Link>

            <ButtonPrimary
                v-if="!showCreate && !isDetailPage"
                :href="createPlanPage.url()"
                class="!inline-flex !min-h-[58px] !w-full !items-center !justify-center !gap-3 !rounded-[20px] !bg-budget-primary !px-5 !py-3 !text-lg !font-semibold !text-white !shadow-[0_12px_24px_rgba(36,98,243,0.2)] transition hover:!bg-budget-primary-hover"
            >
                <Plus :size="25" />
                Buat budget baru
            </ButtonPrimary>
            <article
                v-for="plan in plans"
                v-show="!showCreate && isDetailPage"
                :key="`detail-${plan.id}`"
                class="flex flex-col gap-4 rounded-2xl border border-[#E9EDF3] bg-white p-5 shadow-sm"
            >
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-semibold">{{ plan.name }}</h2>
                        <div class="mt-1 flex flex-wrap items-center gap-2 text-xs">
                            <span class="text-[#637083]">
                                {{ plan.type === 'goal' ? 'Rencana Tujuan' : 'Anggaran Belanja' }}
                            </span>
                            <span class="rounded-full bg-[#EEF3FF] px-2 py-1 font-medium text-[#2457DA]">
                                {{ planStatusLabel(plan.status) }}
                            </span>
                        </div>
                    </div>
                    <strong class="text-sm text-[#2457DA]">
                        Rp {{ formatMoney(plan.type === 'goal' ? plan.target_amount : plan.amount) }}
                    </strong>
                </div>
                <div class="flex flex-wrap gap-2">
                    <ButtonPrimary type="button" variant="secondary" @click="editPlan(plan)">Ubah</ButtonPrimary>
                    <ButtonPrimary
                        v-if="plan.status === 'active'"
                        type="button"
                        variant="secondary"
                        @click="changeStatus(plan, 'paused')"
                    >Jeda</ButtonPrimary>
                    <ButtonPrimary
                        v-if="plan.status === 'paused'"
                        type="button"
                        variant="secondary"
                        @click="changeStatus(plan, 'active')"
                    >Lanjutkan</ButtonPrimary>
                    <ButtonPrimary
                        v-if="['active', 'paused'].includes(plan.status)"
                        type="button"
                        variant="secondary"
                        @click="changeStatus(plan, 'ended')"
                    >Hentikan</ButtonPrimary>
                    <ButtonPrimary
                        v-if="plan.type === 'goal' && plan.status !== 'completed'"
                        type="button"
                        variant="secondary"
                        @click="changeStatus(plan, 'completed')"
                    >Tandai selesai</ButtonPrimary>
                </div>
                <div v-if="plan.type === 'goal'" class="grid grid-cols-2 gap-3 rounded-xl bg-[#F3F6FF] p-4 text-sm">
                    <p>Dana tersedia<br /><strong>Rp {{ formatMoney(plan.goal_available) }}</strong></p>
                    <p>Biaya dibayar<br /><strong>Rp {{ formatMoney(plan.goal_paid) }}</strong></p>
                    <p>Kebutuhan didanai<br /><strong>Rp {{ formatMoney(plan.goal_funded) }}</strong></p>
                    <p>Kekurangan<br /><strong>Rp {{ formatMoney(plan.goal_shortfall) }}</strong></p>
                    <p class="col-span-2 text-xs text-[#637083]">
                        Target {{ plan.target_date }} · Savings {{ plan.pocket?.name }}
                    </p>
                </div>
                <div v-if="plan.items?.length" class="text-sm">
                    <p class="font-semibold">Rincian kebutuhan</p>
                    <p v-for="item in plan.items" :key="item.id" class="flex justify-between">
                        <span>{{ item.name }}</span>
                        <span>Rp {{ formatMoney(item.amount) }}</span>
                    </p>
                </div>
                <div
                    v-for="period in plan.periods"
                    :key="period.id"
                    class="flex flex-col gap-3 rounded-2xl border border-[#E9EDF3] bg-[#F8FAFD] p-4 text-sm"
                >
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="font-semibold">{{ period.starts_on }} – {{ period.ends_on }}</p>
                            <p class="mt-1 text-xs text-[#637083]">
                                {{ plan.type === 'goal' ? 'Setoran tujuan' : 'Anggaran belanja' }}
                            </p>
                        </div>
                        <span class="shrink-0 rounded-full bg-white px-2.5 py-1 text-xs font-medium text-[#637083]">
                            {{ periodStatusLabel(period.status) }}
                        </span>
                    </div>

                    <div v-if="plan.type === 'spending'" class="mt-4 rounded-xl bg-white p-3">
                        <div class="flex items-center justify-between gap-3">
                            <p class="text-xs font-medium text-[#637083]">Penggunaan cadangan</p>
                            <strong class="text-xs" :class="periodUsagePercentage(period) >= 100 ? 'text-[#B34735]' : 'text-[#17243A]'">
                                {{ Math.round(periodUsagePercentage(period)) }}%
                            </strong>
                        </div>
                        <div
                            class="mt-2 h-2.5 overflow-hidden rounded-full bg-[#E9EDF3]"
                            role="progressbar"
                            :aria-label="`Penggunaan anggaran ${plan.name}`"
                            aria-valuemin="0"
                            aria-valuemax="100"
                            :aria-valuenow="Math.round(periodUsagePercentage(period))"
                        >
                            <div
                                class="h-full rounded-full transition-[width] duration-300"
                                :class="periodUsageColor(period)"
                                :style="{ width: `${periodUsagePercentage(period)}%` }"
                            ></div>
                        </div>
                        <div class="mt-3 grid grid-cols-3 gap-2">
                            <div>
                                <p class="text-[10px] text-[#637083]">Dialokasikan</p>
                                <p class="mt-0.5 text-xs font-semibold">Rp {{ formatMoney(period.allocated_amount) }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] text-[#637083]">Terpakai</p>
                                <p class="mt-0.5 text-xs font-semibold">Rp {{ formatMoney(period.spent) }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] text-[#637083]">Sisa</p>
                                <p class="mt-0.5 text-xs font-semibold">Rp {{ formatMoney(period.remaining) }}</p>
                            </div>
                        </div>
                        <p class="mt-2 text-[10px] text-[#637083]">
                            Rencana Rp {{ formatMoney(period.planned_amount) }} ·
                            Dilepas Rp {{ formatMoney(period.released_amount) }}
                        </p>
                    </div>
                    <div v-else class="mt-4 rounded-xl bg-white p-3 text-xs text-[#637083]">
                        Rencana Rp {{ formatMoney(period.planned_amount) }} ·
                        Setoran Rp {{ formatMoney(period.contributed) }}
                    </div>

                    <div v-if="period.claims?.length" class="mt-1 flex flex-col gap-1 text-xs text-[#637083]">
                        <div v-for="claim in period.claims" :key="claim.id" class="flex items-center justify-between gap-2">
                            <span>
                                {{ claim.pocket?.name }} · Rp {{ formatMoney(claim.amount) }}
                                {{ claim.reversed_at ? '(dibalik)' : '' }}
                            </span>
                            <ButtonPrimary
                                v-if="!claim.reversed_at && period.status === 'open'"
                                type="button"
                                variant="secondary"
                                class="!min-h-0 !px-2 !py-1 !text-xs"
                                @click="actOnPeriod(reverseClaim, claim)"
                            >Balik klaim</ButtonPrimary>
                        </div>
                    </div>
                    <div v-if="period.status !== 'closed'" class="mt-1 flex flex-wrap gap-2">
                        <ButtonPrimary
                            v-if="plan.type === 'spending' && period.status === 'awaiting_funding'"
                            type="button"
                            @click="actOnPeriod(fundPeriod, period)"
                        >Danai</ButtonPrimary>
                        <ButtonPrimary
                            v-if="plan.type === 'spending' && period.status === 'open'"
                            type="button"
                            @click="openTransactionModal(plan, period)"
                        >Catat belanja</ButtonPrimary>
                        <ButtonPrimary
                            v-if="plan.type === 'spending' && Number(period.remaining) > 0"
                            type="button"
                            @click="openClaim(period)"
                        >Klaim sisa</ButtonPrimary>
                        <Link
                            v-if="plan.type === 'goal'"
                            :href="savingsIndex({ query: { saving: plan.saving_id, period: period.id } }).url"
                            class="text-sm font-semibold text-[#2457DA]"
                        >Sisihkan Dana</Link>
                        <ButtonPrimary
                            v-if="plan.type === 'spending' && Number(period.remaining) === 0"
                            type="button"
                            variant="secondary"
                            @click="actOnPeriod(closePeriod, period)"
                        >Tutup periode</ButtonPrimary>
                    </div>
                    <div v-if="activeClaim === period.id" class="mt-1 flex flex-col gap-2 rounded-xl bg-[#F7F9FC] p-3">
                        <p class="font-semibold">Bagi sisa ke savings</p>
                        <div v-for="(part, index) in claimParts" :key="index" class="flex gap-2">
                            <select v-model="part.saving_id" class="field" required>
                                <option value="">Tujuan</option>
                                <option v-for="saving in savings.filter((item) => item.currency === period.currency)" :key="saving.id" :value="saving.id">{{ saving.name }}</option>
                            </select>
                            <input v-model="part.amount" class="field" type="number" min="0.01" step="0.01" />
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <ButtonPrimary type="button" variant="secondary" @click="claimParts.push({ saving_id: '', amount: '' })">Tambah tujuan</ButtonPrimary>
                            <ButtonPrimary type="button" @click="submitClaim">Simpan klaim</ButtonPrimary>
                        </div>
                        <p class="text-xs text-[#637083]">
                            Total klaim Rp {{ formatMoney(claimTotal) }} dari sisa Rp {{ formatMoney(period.remaining) }}
                        </p>
                        <label class="text-xs">Atau lepas ke uang bebas
                            <input v-model="releaseAmount" class="field" type="number" min="0.01" step="0.01" />
                        </label>
                        <ButtonPrimary type="button" variant="secondary" @click="actOnPeriod(releasePeriod, period, { amount: releaseAmount })">
                            Lepas sisa
                        </ButtonPrimary>
                    </div>
                </div>
            </article>
            <p v-if="actionError" class="text-sm text-red-600">{{ actionError }}</p>
            <section v-if="!showCreate && !isDetailPage && legacyBudgets.length" class="rounded-2xl bg-white p-5 text-sm">
                <h2 class="font-semibold">Anggaran lama</h2>
                <p class="mt-1 text-xs text-[#637083]">
                    Anggaran ini dipakai laporan lama dan belum mencadangkan dana karena akun sumbernya tidak tercatat.
                </p>
                <p v-for="budget in legacyBudgets" :key="budget.id" class="mt-2 flex justify-between gap-3">
                    <span>{{ budget.name }} · {{ budget.period_start }} – {{ budget.period_end }}</span>
                    <strong>Rp {{ formatMoney(budget.amount) }}</strong>
                </p>
            </section>
        </main>
        <div
            v-if="showTransactionModal"
            class="fixed inset-0 z-40 flex items-end bg-black/40 px-0 sm:items-center sm:px-4"
            @click.self="closeTransactionModal"
        >
            <section
                role="dialog"
                aria-modal="true"
                aria-labelledby="transaction-modal-title"
                class="mx-auto max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-t-[28px] bg-white px-6 pb-8 pt-3 text-[#152338] shadow-xl sm:rounded-2xl"
            >
                <div class="mx-auto mb-5 h-1 w-9 rounded-full bg-[#D5DBE5] sm:hidden" />
                <form class="flex flex-col gap-4" @submit.prevent="submitTransaction">
                    <header class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-xs font-medium text-[#637083]">Pengeluaran dari budget</p>
                            <h2 id="transaction-modal-title" class="text-[22px] font-semibold">Catat belanja</h2>
                        </div>
                        <ButtonPrimary
                            type="button"
                            variant="secondary"
                            class="!min-h-0 !px-3 !py-1 text-xl"
                            aria-label="Tutup form transaksi"
                            @click="closeTransactionModal"
                        >×</ButtonPrimary>
                    </header>

                    <div class="rounded-xl bg-[#F1F5FF] px-4 py-3 text-xs text-[#637083]">
                        <p class="font-semibold text-[#2457DA]">{{ selectedPlan?.name }}</p>
                        <p v-if="selectedTransactionPeriod">
                            Periode {{ selectedTransactionPeriod.starts_on }} – {{ selectedTransactionPeriod.ends_on }}
                        </p>
                    </div>

                    <label class="text-sm font-medium">
                        Nominal
                        <input
                            v-model="transactionForm.amount"
                            class="field"
                            type="number"
                            min="0.01"
                            step="0.01"
                            required
                            placeholder="Contoh: 50000"
                        />
                        <span v-if="transactionForm.errors.amount" class="mt-1 block text-xs text-red-600">
                            {{ transactionForm.errors.amount }}
                        </span>
                    </label>

                    <label class="text-sm font-medium">
                        Nama belanja
                        <input
                            v-model="transactionForm.title"
                            class="field"
                            type="text"
                            maxlength="255"
                            required
                            placeholder="Contoh: Belanja bulanan"
                        />
                        <span v-if="transactionForm.errors.title" class="mt-1 block text-xs text-red-600">
                            {{ transactionForm.errors.title }}
                        </span>
                    </label>

                    <label class="text-sm font-medium">
                        Kategori
                        <select v-model="transactionForm.category_id" class="field">
                            <option value="">Pilih kategori</option>
                            <option
                                v-for="category in availableCategories"
                                :key="category.id"
                                :value="category.id"
                            >{{ category.name }}</option>
                        </select>
                        <span v-if="transactionForm.errors.category_id" class="mt-1 block text-xs text-red-600">
                            {{ transactionForm.errors.category_id }}
                        </span>
                    </label>

                    <label class="text-sm font-medium">
                        Tanggal
                        <input
                            v-model="transactionForm.date"
                            class="field"
                            type="date"
                            :min="selectedTransactionPeriod?.starts_on"
                            :max="selectedTransactionPeriod?.ends_on"
                            required
                        />
                        <span v-if="transactionForm.errors.date" class="mt-1 block text-xs text-red-600">
                            {{ transactionForm.errors.date }}
                        </span>
                    </label>

                    <label class="text-sm font-medium">
                        Catatan <span class="font-normal text-[#637083]">(opsional)</span>
                        <textarea
                            v-model="transactionForm.note"
                            class="field min-h-20 resize-y"
                            maxlength="1000"
                            rows="2"
                        />
                        <span v-if="transactionForm.errors.note" class="mt-1 block text-xs text-red-600">
                            {{ transactionForm.errors.note }}
                        </span>
                    </label>

                    <p v-if="transactionForm.errors.budget_period_id" class="text-sm text-red-600">
                        {{ transactionForm.errors.budget_period_id }}
                    </p>
                    <div class="flex gap-3 pt-1">
                        <ButtonPrimary
                            type="button"
                            variant="secondary"
                            class="flex-1"
                            @click="closeTransactionModal"
                        >Batal</ButtonPrimary>
                        <ButtonPrimary
                            type="submit"
                            class="flex-1"
                            :disabled="transactionForm.processing"
                        >{{ transactionForm.processing ? 'Menyimpan...' : 'Simpan belanja' }}</ButtonPrimary>
                    </div>
                </form>
            </section>
        </div>
        <MobileBottomNav
            :show-labels="!showCreate && !isDetailPage"
            @add="router.visit(dashboard.url({ query: { add: 1 } }))"
        />
    </div>
</template>

<style scoped>
.field {
    @apply mt-1 w-full rounded-xl border border-[#DCE3EE] bg-white px-3 py-2 text-sm;
}
</style>
