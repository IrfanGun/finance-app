<script setup>
import { computed, ref, watch } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ArrowLeft } from 'lucide-vue-next';
import { dashboard } from '@/routes';
import { dashboard as cmsDashboard } from '@/routes/cms';
import { create as cmsCreateSavingPage, index as cmsSavingsIndex, manage as cmsManageSaving, show as cmsShowSaving } from '@/routes/cms/savings';
import { index as cmsBudgetIndex } from '@/routes/cms/budget-plans';
import { index as budgetIndex } from '@/routes/budget-plans';
import {
    pause as pauseReminder,
    resume as resumeReminder,
    store as storeReminder,
    update as updateReminder,
} from '@/routes/recurring-reminders';
import { reverse as reverseAllocation } from '@/routes/saving-logs';
import { store as storeTransaction } from '@/actions/App/Http/Controllers/TransactionController';
import {
    allocate as allocateSaving,
    archive as archiveSaving,
    create as createSavingPage,
    index as savingsIndex,
    manage as manageSaving,
    show as showSaving,
    release as releaseSaving,
    store as storeSaving,
    update as updateSaving,
} from '@/routes/savings';
import MobileBottomNav from '@/Components/MobileBottomNav.vue';
import SavingCreateForm from '@/Components/Savings/SavingCreateForm.vue';
import CmsSavingsIndex from '@/Components/Savings/CmsSavingsIndex.vue';
import CmsSavingManage from '@/Components/Savings/CmsSavingManage.vue';
import SavingHistory from '@/Components/Savings/SavingHistory.vue';
import SavingList from '@/Components/Savings/SavingList.vue';
import SavingManagePanel from '@/Components/Savings/SavingManagePanel.vue';
import {
    createAllocationFormDefaults,
    createReminderFormDefaults,
    createReleaseFormDefaults,
    createSavingFormDefaults,
    formatSavingMoney,
    getActiveHistoryFilterCount,
    getInitialPeriodId,
    getInitialSavingId,
    getReminderLogId,
} from '@/Features/Savings/lib/saving';

const props = defineProps({
    cmsMode: { type: Boolean, default: false },
    savings: { type: Array, default: () => [] },
    reminders: { type: Array, default: () => [] },
    accounts: { type: Array, default: () => [] },
    categories: { type: Array, default: () => [] },
    totals: { type: Object, default: () => ({}) },
    historyFilters: { type: Object, default: () => ({}) },
    periodOptions: { type: Array, default: () => [] },
    createMode: { type: Boolean, default: false },
    manageMode: { type: Boolean, default: false },
    managedSavingId: { type: Number, default: null },
});

const dashboardUrl = computed(() => props.cmsMode ? cmsDashboard.url() : dashboard.url());
const budgetIndexUrl = computed(() => props.cmsMode ? cmsBudgetIndex.url() : budgetIndex.url());
const savingsIndexUrl = computed(() => props.cmsMode ? cmsSavingsIndex.url() : savingsIndex.url());
const createSavingUrl = computed(() => props.cmsMode ? cmsCreateSavingPage.url() : createSavingPage.url());
const showSavingUrl = (savingId) => props.cmsMode ? cmsShowSaving.url(savingId) : showSaving.url(savingId);
const manageSavingUrl = (savingId, options = undefined) => props.cmsMode
    ? cmsManageSaving.url(savingId, options)
    : manageSaving.url(savingId, options);

const selectedId = ref(getInitialSavingId(props.managedSavingId));
const selectedPeriodId = ref(getInitialPeriodId());
const selected = computed(() => props.savings.find((saving) => saving.id === selectedId.value));
const selectedReminder = computed(() => props.reminders.find(
    (item) => item.saving_id === selectedId.value,
) ?? null);
const actionError = ref('');
const manageSection = ref(null);
const activeCmsTab = ref('activity');
const cmsModalAction = ref(null);
const transactionMode = ref('allocate');
const settingsMode = ref('edit');
const showCreateSaving = ref(props.createMode);
const createStep = ref(1);
const sourceFilter = ref(props.historyFilters.source ?? '');
const periodFilter = ref(props.historyFilters.period_id ?? '');
const accountFilter = ref(props.historyFilters.account_id ?? '');
const historyFilterModalOpen = ref(false);
const activeHistoryFilterCount = computed(() => getActiveHistoryFilterCount({
    source: sourceFilter.value,
    period: periodFilter.value,
    account: accountFilter.value,
}));
const formatMoney = formatSavingMoney;
const formatDate = (value) => {
    if (!value) {
        return 'Belum ditentukan';
    }

    return new Intl.DateTimeFormat('id-ID', {
        dateStyle: 'medium',
        timeZone: 'Asia/Jakarta',
    }).format(new Date(value));
};
const localDate = (date) => new Intl.DateTimeFormat('sv-SE', {
    timeZone: 'Asia/Jakarta',
}).format(date);
const initialReminderDate = new Date();
initialReminderDate.setDate(initialReminderDate.getDate() + 1);
const reminderStartDate = ref(localDate(initialReminderDate));
const reminderStartTime = ref('09:00');
const form = useForm(createSavingFormDefaults(props.accounts[0]?.currency ?? 'IDR'));
watch(() => props.createMode, (createMode) => {
    showCreateSaving.value = createMode;

    if (createMode) {
        createStep.value = 1;
        form.reset();
        form.clearErrors();
        form.currency = props.accounts[0]?.currency ?? 'IDR';
    }
});
const allocation = useForm({
    ...createAllocationFormDefaults({
        accountId: props.accounts[0]?.id ?? '',
        periodId: selectedPeriodId.value ?? '',
        reminderLogId: getReminderLogId(),
    }),
});
const release = useForm(createReleaseFormDefaults(props.accounts[0]?.id ?? ''));
const expense = useForm({
    type: 'expense',
    amount: '',
    date: localDate(new Date()),
    title: '',
    note: '',
    account_id: '',
    category_id: '',
    meal_record_id: null,
    budget_period_id: null,
    saving_id: selectedId.value,
});
const reminder = useForm(createReminderFormDefaults(props.accounts[0]?.id ?? null));
const updateReminderStart = () => {
    reminder.starts_at = reminderStartDate.value && reminderStartTime.value
        ? `${reminderStartDate.value}T${reminderStartTime.value}`
        : '';
};
watch([reminderStartDate, reminderStartTime], updateReminderStart, { immediate: true });
const submitCreate = () => {
    form.post(storeSaving.url(), {
        preserveScroll: true,
        onError: (errors) => {
            if (errors.target_amount || errors.target_date) {
                createStep.value = 2;
            }
        },
        onSuccess: () => {
            form.reset();
            createStep.value = 1;
            showCreateSaving.value = false;
        },
    });
};
const submitAllocation = () => {
    if (!selected.value) {
        return;
    }

    allocation.post(allocateSaving.url(selected.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            allocation.amount = '';
            allocation.idempotency_key = crypto.randomUUID();
            cmsModalAction.value = null;
        },
    });
};
const submitRelease = () => {
    if (!selected.value) {
        return;
    }

    release.post(releaseSaving.url(selected.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            release.amount = '';
            release.idempotency_key = crypto.randomUUID();
        },
    });
};
const submitExpense = () => {
    if (!selected.value) {
        return;
    }

    expense.saving_id = selected.value.id;
    expense.title = `Pengeluaran dari tabungan ${selected.value.name}`;
    expense.post(storeTransaction.url(), {
        preserveScroll: true,
        onSuccess: () => {
            expense.reset();
            cmsModalAction.value = null;
        },
    });
};
const openSaving = (saving) => {
    selectedId.value = saving.id;
    settingsMode.value = saving.goal_plan ? 'archive' : 'edit';
    selectedPeriodId.value = null;
    allocation.budget_period_id = '';
    allocation.reminder_log_id = null;
    form.name = saving.name;
    form.currency = saving.currency;
    form.icon = saving.icon ?? 'piggy-bank';
    form.color = saving.color ?? 'blue';
    form.target_amount = saving.target_amount ?? '';
    form.target_date = saving.target_date ?? '';
    form.notes = saving.notes ?? '';
    reminder.saving_id = saving.id;
    reminder.title = selectedReminder.value?.title ?? `Alokasi tabungan ${saving.name}`;
    reminder.amount = selectedReminder.value?.amount ?? '';
    reminder.frequency = selectedReminder.value?.frequency ?? 'monthly';
    reminder.interval = selectedReminder.value?.interval ?? 1;
    reminder.priority = 'medium';
    reminder.timezone = selectedReminder.value?.timezone ?? 'Asia/Jakarta';
    reminderStartDate.value = selectedReminder.value?.starts_at?.slice(0, 10)
        ?? localDate(initialReminderDate);
    reminderStartTime.value = selectedReminder.value?.starts_at?.slice(11, 16) ?? '09:00';
    updateReminderStart();
};
watch(selected, (saving) => {
    if (saving) {
        openSaving(saving);
    }
}, { immediate: true });
const submitUpdate = () => {
    if (selected.value) {
        form.patch(updateSaving.url(selected.value.id), { preserveScroll: true });
    }
};
const archive = () => {
    if (!selected.value) {
        return;
    }

    router.post(
        archiveSaving.url(selected.value.id),
        {},
        {
            preserveScroll: true,
            onError: (errors) => {
                actionError.value = Object.values(errors)[0] ?? 'Gagal mengarsipkan.';
            },
        },
    );
};
const submitReminder = () => {
    if (selected.value) {
        reminder.saving_id = selected.value.id;
        const options = {
            preserveScroll: true,
            onSuccess: () => {
                actionError.value = '';
            },
            onError: (errors) => {
                actionError.value = Object.values(errors)[0] ?? 'Pengingat tidak dapat disimpan.';
            },
        };

        if (selectedReminder.value) {
            reminder.patch(updateReminder.url(selectedReminder.value.id), options);
        } else {
            reminder.post(storeReminder.url(), options);
        }
    }
};
const toggleReminder = (record) => {
    const route = record.status === 'active' ? pauseReminder : resumeReminder;

    router.post(route.url(record.id), {}, {
        preserveScroll: true,
        onError: (errors) => {
            actionError.value = Object.values(errors)[0] ?? 'Status pengingat tidak dapat diubah.';
        },
    });
};
const openTransactions = (mode) => {
    if (!selected.value) {
        return;
    }

    actionError.value = '';
    cmsModalAction.value = mode;

    if (mode === 'allocate') {
        allocation.clearErrors();
        allocation.account_id = props.accounts.find((account) => (
            account.currency === selected.value.currency
            && Number(account.available_balance) > 0
        ))?.id ?? '';
        allocation.amount = '';
        allocation.date = localDate(new Date());
        allocation.notes = '';
        allocation.budget_period_id = selected.value.goal_plan?.periods.find(
            (period) => period.status !== 'closed',
        )?.id ?? '';
        allocation.idempotency_key = crypto.randomUUID();
    } else {
        expense.reset();
        expense.clearErrors();
        expense.amount = '';
        expense.date = localDate(new Date());
        expense.title = `Pengeluaran dari tabungan ${selected.value.name}`;
        expense.account_id = props.accounts.find((account) => (
            account.currency === selected.value.currency
            && Number(account.saving_balance) > 0
        ))?.id ?? '';
        expense.category_id = '';
        expense.saving_id = selected.value.id;
    }
};
const initialCmsAction = props.cmsMode && props.manageMode
    ? new URLSearchParams(window.location.search).get('action')
    : null;
watch(selected, (saving) => {
    if (saving && ['allocate', 'expense'].includes(initialCmsAction)) {
        openTransactions(initialCmsAction);
    }
}, { immediate: true, once: true });
const reverseLog = (log) => {
    actionError.value = '';
    router.post(
        reverseAllocation.url(log.id),
        { idempotency_key: crypto.randomUUID() },
        {
            preserveScroll: true,
            onError: (errors) => {
                actionError.value = Object.values(errors)[0] ?? 'Alokasi tidak dapat dibalik.';
            },
        },
    );
};
const applyHistoryFilters = () => {
    router.get(
        savingsIndexUrl.value,
        {
            saving: selectedId.value ?? undefined,
            source: sourceFilter.value || undefined,
            period_id: periodFilter.value || undefined,
            account_id: accountFilter.value || undefined,
        },
        {
            preserveState: true,
            preserveScroll: true,
            onSuccess: () => {
                historyFilterModalOpen.value = false;
            },
        },
    );
};
</script>

<template>
    <div
        class="min-h-screen w-full text-[#152338]"
        :class="cmsMode
            ? 'bg-[#F5F8FD] pb-10'
            : 'mx-auto max-w-lg bg-[#F7F9FC] pb-28'"
    >
        <Head :title="showCreateSaving ? 'Buat kantong savings' : 'Savings'" />
        <header v-if="!cmsMode && !showCreateSaving && !manageMode" class="flex items-center justify-between bg-white px-6 py-5">
            <Link :href="dashboardUrl" class="text-sm font-semibold text-[#2457DA]">{{ $t("Back") }}</Link>
            <h1 class="text-lg font-semibold">{{ $t("Savings") }}</h1>
            <Link :href="budgetIndexUrl" class="text-sm font-semibold text-[#2457DA]">{{ $t("Budget") }}</Link>
        </header>

        <main
            v-if="!cmsMode || !manageMode || !selected || manageSection === 'transactions'"
            class="flex flex-col gap-5"
            :class="cmsMode
                ? 'mx-auto w-full max-w-[1440px] px-4 py-6 sm:px-8 lg:px-10 lg:py-8'
                : 'px-6 pt-6'"
        >
            <header v-if="manageMode && selected" class="flex items-center gap-4">
                <Link
                    :href="showSavingUrl(selected.id)"
                    class="rounded-full p-2 text-[#2457DA] hover:bg-[#F5F7FA]"
                    :aria-label="`Kembali ke detail ${selected.name}`"
                >
                    <ArrowLeft :size="22" />
                </Link>
                <div class="min-w-0 flex-1">
                    <p class="text-xs font-medium text-[#637083]">{{ $t("Savings") }}</p>
                    <h1 class="truncate text-xl font-semibold">{{ $t("Manage") }} {{ selected.name }}</h1>
                </div>
            </header>
            <header v-if="showCreateSaving && !cmsMode" class="flex items-center gap-4">
                <Link
                    :href="savingsIndexUrl"
                    class="rounded-full p-2 text-[#2457DA] hover:bg-[#F5F7FA]"
                    aria-label="Kembali ke Savings"
                >
                    <ArrowLeft :size="22" />
                </Link>
                <div class="min-w-0 flex-1">
                    <p class="text-xs font-medium text-[#637083]">{{ $t("Savings") }}</p>
                    <h1 class="text-xl font-semibold">{{ $t("Buat kantong savings") }}</h1>
                </div>
                <span class="shrink-0 text-sm font-medium text-[#637083]">{{ $t("Step") }} {{ createStep }} {{ $t("dari 2") }}</span>
            </header>
            <p v-if="!cmsMode && !showCreateSaving && !manageMode" class="rounded-2xl bg-[#EAF0FF] p-4 text-sm"> {{ $t("Dana savings adalah bagian dari saldo aktual. Alokasi hanya mengurangi uang bebas.") }} </p>
            <Link
                v-if="!cmsMode && !showCreateSaving && !manageMode"
                :href="createSavingUrl"
                class="inline-flex min-h-12 items-center justify-center rounded-xl bg-[#2457DA] px-5 py-3 font-semibold text-white shadow-sm"
            > {{ $t("Buat kantong savings") }} </Link>
            <CmsSavingsIndex
                v-if="cmsMode && !manageMode"
                :savings="savings"
                :index-url="savingsIndexUrl"
                :create-modal-open="showCreateSaving"
                :create-form="form"
                :accounts="accounts"
                v-model:create-step="createStep"
                :show-saving-url="showSavingUrl"
                :manage-saving-url="manageSavingUrl"
                @submit-create="submitCreate"
            />
            <SavingCreateForm
                v-if="showCreateSaving && !cmsMode"
                v-model:step="createStep"
                :form="form"
                :accounts="accounts"
                @submit="submitCreate"
            />

            <SavingList
                v-if="!cmsMode && !showCreateSaving && !manageMode"
                :savings="savings"
                :format-money="formatMoney"
                :show-saving-url="showSavingUrl"
                :manage-saving-url="manageSavingUrl"
            />

            <SavingManagePanel
                v-if="!showCreateSaving && selected && (!cmsMode || manageSection === 'transactions')"
                :saving="selected"
                :accounts="accounts"
                :form="form"
                :allocation="allocation"
                :release="release"
                :reminder="reminder"
                :format-money="formatMoney"
                :action-error="actionError"
                v-model:manage-section="manageSection"
                v-model:transaction-mode="transactionMode"
                v-model:settings-mode="settingsMode"
                @allocate="submitAllocation"
                @release="submitRelease"
                @update="submitUpdate"
                @reminder="submitReminder"
                @archive="archive"
            />

            <SavingHistory
                v-if="!showCreateSaving && selected && !cmsMode"
                :saving="selected"
                :period-options="periodOptions"
                :accounts="accounts"
                :active-filter-count="activeHistoryFilterCount"
                v-model:source-filter="sourceFilter"
                v-model:period-filter="periodFilter"
                v-model:account-filter="accountFilter"
                v-model:is-open="historyFilterModalOpen"
                :format-money="formatMoney"
                @apply="applyHistoryFilters"
                @reverse="reverseLog"
            />
        </main>

        <CmsSavingManage
            v-if="cmsMode && manageMode && selected && manageSection !== 'transactions'"
            v-model:active-tab="activeCmsTab"
            :saving="selected"
            :reminders="reminders"
            :accounts="accounts"
            :categories="categories"
            :form="form"
            :reminder="reminder"
            :allocation="allocation"
            :expense="expense"
            :modal-action="cmsModalAction"
            :start-date="reminderStartDate"
            :start-time="reminderStartTime"
            :format-money="formatMoney"
            :format-date="formatDate"
            :index-url="savingsIndexUrl"
            :action-error="actionError"
            @update:start-date="reminderStartDate = $event"
            @update:start-time="reminderStartTime = $event"
            @update-saving="submitUpdate"
            @save-reminder="submitReminder"
            @toggle-reminder="toggleReminder"
            @open-transactions="openTransactions"
            @close-modal="cmsModalAction = null"
            @allocate="submitAllocation"
            @record-expense="submitExpense"
            @archive="archive"
        />
        <MobileBottomNav v-if="!cmsMode" @add="router.visit(dashboard.url({ query: { add: 1 } }))" />
    </div>
</template>
