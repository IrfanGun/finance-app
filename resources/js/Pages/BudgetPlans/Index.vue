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
    Wallet,
} from 'lucide-vue-next';
import { dashboard } from '@/routes';
import { dashboard as cmsDashboard } from '@/routes/cms';
import { useBudgetPlanDisplay } from '@/Features/BudgetPlans/useBudgetPlanDisplay.js';
import { create as cmsCreateSavingPage, index as cmsSavingsIndex } from '@/routes/cms/savings';
import { create as createSavingPage, index as savingsIndex } from '@/routes/savings';
import { create as cmsCreatePlanPage, index as cmsBudgetPlansIndex, show as cmsShowPlanPage } from '@/routes/cms/budget-plans';
import { reverse as reverseClaim } from '@/routes/budget-claims';
import {
    index as budgetPlansIndex,
    status as statusPlan,
    store as storePlan,
    update as updatePlan,
    create as createPlanPage,
    show as showPlanPage,
} from '@/routes/budget-plans';
import { destroy as destroyBudgetPlan } from '@/routes/cms/budget-plans';
import {
    claim as claimPeriod,
    close as closePeriod,
    fund as fundPeriod,
    release as releasePeriod,
} from '@/routes/budget-periods';
import { store as storeTransaction } from '@/routes/transactions';
import MobileBottomNav from '@/Components/MobileBottomNav.vue';
import BudgetPlanWizard from '@/Components/BudgetPlanWizard.vue';
import BudgetPlanSummaryCard from '@/Components/BudgetPlans/BudgetPlanSummaryCard.vue';
import BudgetPlanDetail from '@/Components/BudgetPlans/BudgetPlanDetail.vue';
import BudgetPlanTransactionModal from '@/Components/BudgetPlans/BudgetPlanTransactionModal.vue';
import CmsBudgetPlansBoard from '@/Components/BudgetPlans/CmsBudgetPlansBoard.vue';
import SuccessModal from '@/Components/SuccessModal.vue';

const props = defineProps({
    cmsMode: { type: Boolean, default: false },
    plans: { type: Array, default: () => [] },
    legacyBudgets: { type: Array, default: () => [] },
    accounts: { type: Array, default: () => [] },
    categories: { type: Array, default: () => [] },
    savings: { type: Array, default: () => [] },
    createMode: { type: Boolean, default: false },
    selectedPlanId: { type: Number, default: null },
    totals: { type: Object, default: () => ({}) },
    fundingNotices: { type: Array, default: () => [] },
    appearanceOptions: {
        type: Object,
        default: () => ({ icons: {}, colors: {} }),
    },
});

const dashboardUrl = computed(() => props.cmsMode ? cmsDashboard.url() : dashboard.url());
const budgetPlansIndexUrl = computed(() => props.cmsMode ? cmsBudgetPlansIndex.url() : budgetPlansIndex.url());
const savingsIndexUrl = computed(() => props.cmsMode ? cmsSavingsIndex.url() : savingsIndex.url());
const createSavingUrl = computed(() => props.cmsMode ? cmsCreateSavingPage.url() : createSavingPage.url());
const createPlanUrl = computed(() => props.cmsMode ? cmsCreatePlanPage.url() : createPlanPage.url());
const showPlanUrl = (planId) => props.cmsMode ? cmsShowPlanPage.url(planId) : showPlanPage.url(planId);
const savingsFilterUrl = (options) => props.cmsMode
    ? cmsSavingsIndex(options).url
    : savingsIndex(options).url;

const {
    activePlanCount,
    activePlanFilter,
    availableCategories,
    availableCurrencies,
    currentPeriodForPlan,
    displayAccountCurrency,
    filteredPlans,
    formatCurrency,
    formatDate,
    formatMoney,
    goalProgressPercentage,
    isDetailPage,
    monthLabel,
    periodStatusLabel,
    periodUsageColor,
    periodUsagePercentage,
    planIcon,
    planIconStyle,
    planStatusColor,
    planStatusLabel,
    secondaryCurrency,
    selectedCurrency,
    selectedPlan,
    today,
} = useBudgetPlanDisplay(props);

const showCreate = ref(props.createMode);
const showSuccessModal = ref(false);
const successMessage = ref('Budget plan berhasil dibuat.');
const activeStep = ref(1);
const steps = ['Rencana', 'Jadwal', 'Ringkasan'];
const editingId = ref(null);
const activeClaim = ref(null);
const claimParts = ref([{ saving_id: '', amount: '' }]);
const claimTotal = computed(() => claimParts.value.reduce(
    (total, part) => total + Number(part.amount || 0),
    0,
));
const releaseAmount = ref('');
const actionError = ref('');

const deletePlan = (plan) => {
    if (!window.confirm(
        `Hapus budget plan "${plan.name}"? Riwayat transaksi tetap disimpan dan pengingat terkait dihentikan.`,
    )) {
        return;
    }

    actionError.value = '';

    router.delete(destroyBudgetPlan.url(plan.id), {
        preserveScroll: true,
        onError: (errors) => {
            actionError.value = Object.values(errors)[0] ?? 'Budget plan gagal dihapus.';
        },
    });
};
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
    icon: 'shopping-bag',
    color: 'blue',
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
            activeStep.value = fields.some((field) => [
                'starts_on',
                'ends_on',
                'target_date',
                'interval',
                'timezone',
            ].includes(field))
                ? 2
                : 1;
        },
        onSuccess: () => {
            const wasEditing = Boolean(editingId.value);

            showCreate.value = false;
            editingId.value = null;
            activeStep.value = 1;
            form.reset();
            successMessage.value = wasEditing
                ? 'Perubahan budget plan berhasil disimpan.'
                : 'Budget plan berhasil dibuat.';
            showSuccessModal.value = true;
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
        router.visit(budgetPlansIndexUrl.value);
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

    if (activeStep.value === 1) {
        if (!form.name.trim()) {
            form.setError('name', 'Masukkan nama rencana.');
            return;
        }

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

    if (activeStep.value === 2) {
        const hasInvalidDates = !form.starts_on
            || (form.type === 'goal' && (!form.target_date || form.target_date < form.starts_on))
            || (form.type === 'spending' && !form.is_recurring
                && (!form.ends_on || form.ends_on < form.starts_on));

        if (hasInvalidDates) {
            form.setError('starts_on', 'Periksa tanggal mulai, tenggat, dan tanggal akhir.');
            return;
        }
    }

    activeStep.value = Math.min(activeStep.value + 1, steps.length);
};
const changeStep = (step) => {
    activeStep.value = step;
    form.clearErrors();
};
const editPlan = (plan) => {
    editingId.value = plan.id;
    form.clearErrors();
    form.name = plan.name;
    form.type = plan.type;
    form.icon = plan.icon ?? 'shopping-bag';
    form.color = plan.color ?? 'blue';
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
    <div
        class="mx-auto min-h-screen w-full max-w-lg text-budget-heading"
        :class="cmsMode ? 'bg-[#F7FAFF] pb-0' : 'bg-budget-page pb-28'"
    >
        <Head
            :title="showCreate
                ? (editingId ? 'Ubah Budget Plan' : 'Budget Plan baru')
                : (isDetailPage ? selectedPlan?.name ?? 'Detail Budget Plan' : 'Budget Plan')"
        />
        <CmsBudgetPlansBoard
            v-if="cmsMode && !showCreate && !isDetailPage"
            :plans="plans"
            :totals="totals"
            :available-currencies="availableCurrencies"
            v-model:selected-currency="selectedCurrency"
            :secondary-currency="secondaryCurrency"
            :active-plan-count="activePlanCount"
            :create-plan-url="createPlanUrl"
            :show-plan-url="showPlanUrl"
            :period-usage-percentage="periodUsagePercentage"
            :format-currency="formatCurrency"
            :plan-status-label="planStatusLabel"
            :plan-icon="planIcon"
            :plan-icon-style="planIconStyle"
            @edit="editPlan"
            @delete="deletePlan"
        />
        <p
            v-if="cmsMode && actionError"
            class="mx-auto max-w-7xl px-6 py-3 text-sm text-red-600"
        >{{ actionError }}</p>
        <template v-if="!cmsMode || showCreate || isDetailPage">
        <header
            v-if="!showCreate && !isDetailPage"
            class="grid grid-cols-[52px_1fr_104px] items-center gap-3 px-5 pb-2 pt-6"
        >
            <Link
                :href="dashboardUrl"
                aria-label="Kembali ke dashboard"
                class="flex h-[52px] w-[52px] items-center justify-center rounded-[20px] bg-budget-surface text-budget-ink"
            >
                <ArrowLeft :size="23" />
            </Link>
            <h1 class="text-center text-xl font-bold tracking-tight sm:text-2xl"> {{ $t("Budget Planner") }} </h1>
            <Link
                :href="savingsIndexUrl"
                class="inline-flex min-h-[46px] items-center justify-center gap-2 rounded-full border-2 border-budget-primary bg-budget-surface px-3 text-sm font-semibold text-budget-link sm:px-4"
            >
                <PiggyBank :size="20" /> {{ $t("Savings") }} </Link>
        </header>
        <header v-else-if="!showCreate" class="flex items-center justify-between bg-white px-6 py-5">
            <Link :href="budgetPlansIndexUrl" class="text-sm font-semibold text-[#2457DA]">
                â† Semua plan
            </Link>
            <h1 class="text-lg font-semibold">{{ $t("Detail plan") }}</h1>
            <Link :href="savingsIndexUrl" class="text-sm font-semibold text-[#2457DA]">{{ $t("Savings") }}</Link>
        </header>

        <main
            class="flex flex-col gap-5 px-5 pt-4"
            :class="showCreate
                ? 'min-h-[calc(100vh-84px)] flex-1 gap-4 px-4 pb-4 pt-3 sm:px-6'
                : (!isDetailPage ? 'pb-4' : 'sm:px-6 sm:pt-6')"
        >
            <section v-if="!showCreate && !isDetailPage && fundingNotices.length" class="rounded-2xl bg-[#FFF3DE] p-4 text-sm">
                <h2 class="font-semibold">{{ $t("Budget perlu didanai") }}</h2>
                <Link
                    v-for="notice in fundingNotices"
                    :key="notice.id"
                    :href="notice.data.url"
                    class="mt-2 block text-[#8A5900]"
                >{{ notice.data.title }}</Link>
            </section>
            <div v-if="!showCreate && !isDetailPage" class="flex justify-center">
                <div class="inline-flex items-center gap-3 rounded-[20px] bg-budget-surface/80 px-5 py-3 text-budget-ink">
                    <CalendarDays :size="21" class="text-[#52627E]" />
                    <span class="font-semibold capitalize">{{ monthLabel }}</span>
                    <ChevronDown :size="18" class="text-[#52627E]" />
                </div>
            </div>

            <section
                v-if="!showCreate && !isDetailPage && totals[selectedCurrency]"
                class="relative isolate overflow-hidden rounded-[28px] bg-budget-header p-6 text-white sm:p-8"
            >
                <div class="pointer-events-none absolute -right-10 -top-16 -z-10 h-56 w-56 rounded-full bg-white/10"></div>
                <div class="pointer-events-none absolute -right-16 top-28 -z-10 h-48 w-48 rounded-full bg-budget-header-middle/25"></div>
                <div class="relative">
                    <p class="flex items-center gap-3 pr-32 text-base font-medium text-white/90 sm:text-lg"> {{ $t("Uang tersedia") }} <Eye :size="20" />
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
                    <p class="mt-2 text-base text-white/75 sm:text-lg">{{ $t("Siap dialokasikan") }}</p>
                </div>
                <div class="mt-7 grid grid-cols-3 border-t border-white/25 pt-5">
                    <div class="pr-2">
                        <p class="text-xs text-white/75 sm:text-sm">{{ $t("Saldo aktual") }}</p>
                        <p class="mt-1 text-sm font-bold sm:text-lg">
                            {{ formatCurrency(totals[selectedCurrency].actual, selectedCurrency) }}
                        </p>
                    </div>
                    <div class="border-l border-white/25 px-3 sm:px-5">
                        <p class="text-xs text-white/75 sm:text-sm">{{ $t("Budget tersisa") }}</p>
                        <p class="mt-1 text-sm font-bold sm:text-lg">
                            {{ formatCurrency(totals[selectedCurrency].budget, selectedCurrency) }}
                        </p>
                    </div>
                    <div class="border-l border-white/25 pl-3 sm:pl-5">
                        <p class="text-xs text-white/75 sm:text-sm">{{ $t("Savings") }}</p>
                        <p class="mt-1 text-sm font-bold sm:text-lg">
                            {{ formatCurrency(totals[selectedCurrency].savings, selectedCurrency) }}
                        </p>
                    </div>
                </div>
            </section>

            <Link
                v-if="!showCreate && !isDetailPage && secondaryCurrency && totals[secondaryCurrency]"
                :href="savingsIndexUrl"
                class="flex items-center gap-4 rounded-[24px] bg-white p-4"
            >
                <span class="flex h-14 w-14 items-center justify-center rounded-[20px] bg-budget-icon-surface text-budget-primary">
                    <Wallet :size="25" />
                </span>
                <span class="min-w-0 flex-1">
                    <span class="block text-sm text-budget-muted">{{ $t("Balance") }} {{ secondaryCurrency || 'IDR' }}</span>
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
                    <h2 class="text-2xl font-bold tracking-tight sm:text-[28px]">{{ $t("Budget saya") }}</h2>
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

            <BudgetPlanWizard
                v-if="showCreate"
                :form="form"
                :active-step="activeStep"
                :steps="steps"
                :editing="Boolean(editingId)"
                :accounts="accounts"
                :categories="categories"
                :savings="savings"
                :icons="appearanceOptions.icons"
                :colors="appearanceOptions.colors"
                :plan-icon="planIcon"
                :plan-icon-style="planIconStyle"
                :format-money="formatMoney"
                :format-currency="formatCurrency"
                :format-date="formatDate"
                :display-account-currency="displayAccountCurrency"
                :account-changed="accountChanged"
                :next-step="nextStep"
                :back-step="goBackFromForm"
                :change-step="changeStep"
                :add-item="addItem"
                :savings-url="createSavingUrl"
                @submit="submitPlan"
            />

            <p
                v-if="!showCreate && !isDetailPage && !filteredPlans.length"
                class="rounded-2xl bg-white p-5 text-sm text-[#637083]"
            >
                {{ plans.length ? 'Tidak ada budget pada filter ini.' : 'Belum ada budget plan.' }}
            </p>
            <BudgetPlanSummaryCard
                v-for="plan in filteredPlans"
                v-show="!showCreate && !isDetailPage"
                :key="`summary-${plan.id}`"
                :plan="plan"
                :plan-icon="planIcon"
                :plan-icon-style="planIconStyle"
                :plan-status-label="planStatusLabel"
                :plan-status-color="planStatusColor"
                :current-period-for-plan="currentPeriodForPlan"
                :period-usage-percentage="periodUsagePercentage"
                :period-usage-color="periodUsageColor"
                :goal-progress-percentage="goalProgressPercentage"
                :format-currency="formatCurrency"
                :format-date="formatDate"
                :show-plan-url="showPlanUrl"
            />

            <ButtonPrimary
                v-if="!showCreate && !isDetailPage"
                :href="createPlanUrl"
                class="!inline-flex !min-h-[58px] !w-full !items-center !justify-center !gap-3 !rounded-[20px] !bg-budget-primary !px-5 !py-3 !text-lg !font-semibold !text-white !shadow-none transition hover:!bg-budget-primary-hover"
            >
                <Plus :size="25" /> {{ $t("Buat budget baru") }} </ButtonPrimary>
            <BudgetPlanDetail
                v-for="plan in plans"
                v-show="!showCreate && isDetailPage"
                :key="`detail-${plan.id}`"
                :plan="plan"
                :savings="savings"
                :plan-icon="planIcon"
                :plan-icon-style="planIconStyle"
                :plan-status-label="planStatusLabel"
                :period-status-label="periodStatusLabel"
                :period-usage-percentage="periodUsagePercentage"
                :period-usage-color="periodUsageColor"
                :format-money="formatMoney"
                :active-claim="activeClaim"
                :claim-parts="claimParts"
                :claim-total="claimTotal"
                :release-amount="releaseAmount"
                :reverse-claim="reverseClaim"
                :fund-period="fundPeriod"
                :close-period="closePeriod"
                :release-period="releasePeriod"
                :savings-url="savingsFilterUrl"
                @edit="editPlan"
                @change-status="changeStatus"
                @period-action="actOnPeriod"
                @open-transaction="openTransactionModal"
                @open-claim="openClaim"
                @submit-claim="submitClaim"
                @update:release-amount="releaseAmount = $event"
            />
            <p v-if="actionError" class="text-sm text-red-600">{{ actionError }}</p>
            <section v-if="!showCreate && !isDetailPage && legacyBudgets.length" class="rounded-2xl bg-white p-5 text-sm">
                <h2 class="font-semibold">{{ $t("Anggaran lama") }}</h2>
                <p class="mt-1 text-xs text-[#637083]"> {{ $t("Anggaran ini dipakai laporan lama dan belum mencadangkan dana karena akun sumbernya tidak tercatat.") }} </p>
                <p v-for="budget in legacyBudgets" :key="budget.id" class="mt-2 flex justify-between gap-3">
                    <span>{{ budget.name }} Â· {{ budget.period_start }} â€“ {{ budget.period_end }}</span>
                    <strong>{{ $t("Rp") }} {{ formatMoney(budget.amount) }}</strong>
                </p>
            </section>
        </main>
        <SuccessModal
            :show="showSuccessModal"
            title="Berhasil!"
            :message="successMessage"
            button-label="Selesai"
            @close="showSuccessModal = false"
        />
        <BudgetPlanTransactionModal
            :show="showTransactionModal"
            :transaction-form="transactionForm"
            :selected-plan="selectedPlan"
            :selected-transaction-period="selectedTransactionPeriod"
            :available-categories="availableCategories"
            @close="closeTransactionModal"
            @submit="submitTransaction"
        />
        <MobileBottomNav
            :show-labels="!showCreate && !isDetailPage"
            @add="router.visit(dashboardUrl + '?add=1')"
        />
        </template>
    </div>
</template>
