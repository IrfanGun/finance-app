<script setup>
import { computed, ref, watch } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ArrowLeft } from 'lucide-vue-next';
import { dashboard } from '@/routes';
import { dashboard as cmsDashboard } from '@/routes/cms';
import { create as cmsCreateSavingPage, index as cmsSavingsIndex, manage as cmsManageSaving, show as cmsShowSaving } from '@/routes/cms/savings';
import { index as cmsBudgetIndex } from '@/routes/cms/budget-plans';
import { index as budgetIndex } from '@/routes/budget-plans';
import { store as storeReminder } from '@/routes/recurring-reminders';
import { reverse as reverseAllocation } from '@/routes/saving-logs';
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
    accounts: { type: Array, default: () => [] },
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
const manageSavingUrl = (savingId) => props.cmsMode ? cmsManageSaving.url(savingId) : manageSaving.url(savingId);

const selectedId = ref(getInitialSavingId(props.managedSavingId));
const selectedPeriodId = ref(getInitialPeriodId());
const selected = computed(() => props.savings.find((saving) => saving.id === selectedId.value));
const actionError = ref('');
const manageSection = ref(null);
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
const allocation = useForm(createAllocationFormDefaults({
    accountId: props.accounts[0]?.id ?? '',
    periodId: selectedPeriodId.value ?? '',
    reminderLogId: getReminderLogId(),
}));
const release = useForm(createReleaseFormDefaults(props.accounts[0]?.id ?? ''));
const reminder = useForm(createReminderFormDefaults(props.accounts[0]?.id ?? null));
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
const openSaving = (saving) => {
    selectedId.value = saving.id;
    settingsMode.value = saving.goal_plan ? 'archive' : 'edit';
    selectedPeriodId.value = null;
    allocation.budget_period_id = '';
    allocation.reminder_log_id = null;
    form.name = saving.name;
    form.currency = saving.currency;
    form.target_amount = saving.target_amount ?? '';
    form.target_date = saving.target_date ?? '';
    form.notes = saving.notes ?? '';
    reminder.saving_id = saving.id;
    reminder.title = 'Alokasi savings ' + saving.name;
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
        reminder.post(storeReminder.url());
    }
};
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
    <div class="mx-auto min-h-screen w-full max-w-lg bg-[#F7F9FC] pb-28 text-[#152338]">
        <Head :title="showCreateSaving ? 'Buat kantong savings' : 'Savings'" />
        <header v-if="!showCreateSaving && !manageMode" class="flex items-center justify-between bg-white px-6 py-5">
            <Link :href="dashboardUrl" class="text-sm font-semibold text-[#2457DA]">{{ $t("Back") }}</Link>
            <h1 class="text-lg font-semibold">{{ $t("Savings") }}</h1>
            <Link :href="budgetIndexUrl" class="text-sm font-semibold text-[#2457DA]">{{ $t("Budget") }}</Link>
        </header>

        <main class="flex flex-col gap-5 px-6 pt-6">
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
            <header v-if="showCreateSaving" class="flex items-center gap-4">
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
            <p v-if="!showCreateSaving && !manageMode" class="rounded-2xl bg-[#EAF0FF] p-4 text-sm"> {{ $t("Dana savings adalah bagian dari saldo aktual. Alokasi hanya mengurangi uang bebas.") }} </p>
            <Link
                v-if="!showCreateSaving && !manageMode"
                :href="createSavingUrl"
                class="inline-flex min-h-12 items-center justify-center rounded-xl bg-[#2457DA] px-5 py-3 font-semibold text-white shadow-sm"
            > {{ $t("Buat kantong savings") }} </Link>
            <SavingCreateForm
                v-if="showCreateSaving"
                v-model:step="createStep"
                :form="form"
                :accounts="accounts"
                @submit="submitCreate"
            />

            <SavingList
                v-if="!showCreateSaving && !manageMode"
                :savings="savings"
                :format-money="formatMoney"
                :show-saving-url="showSavingUrl"
                :manage-saving-url="manageSavingUrl"
            />

            <SavingManagePanel
                v-if="!showCreateSaving && selected"
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
                v-if="!showCreateSaving && selected"
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
        <MobileBottomNav v-if="!cmsMode" @add="router.visit(dashboard.url({ query: { add: 1 } }))" />
    </div>
</template>
