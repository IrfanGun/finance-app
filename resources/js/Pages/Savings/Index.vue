<script setup>
import { computed, ref, watch } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ArrowDownUp, ArrowLeft, ArrowRight, Filter, SlidersHorizontal, X } from 'lucide-vue-next';
import { dashboard } from '@/routes';
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

const props = defineProps({
    savings: { type: Array, default: () => [] },
    accounts: { type: Array, default: () => [] },
    totals: { type: Object, default: () => ({}) },
    historyFilters: { type: Object, default: () => ({}) },
    periodOptions: { type: Array, default: () => [] },
    createMode: { type: Boolean, default: false },
    manageMode: { type: Boolean, default: false },
    managedSavingId: { type: Number, default: null },
});

const query = new URLSearchParams(window.location.search);
const selectedId = ref(props.managedSavingId || Number(query.get('saving')) || null);
const selectedPeriodId = ref(Number(query.get('period')) || null);
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
const activeHistoryFilterCount = computed(() => [
    sourceFilter.value,
    periodFilter.value,
    accountFilter.value,
].filter(Boolean).length);
const formatMoney = (amount) => new Intl.NumberFormat('id-ID').format(Number(amount ?? 0));
const form = useForm({
    name: '',
    currency: props.accounts[0]?.currency ?? 'IDR',
    target_amount: '',
    target_date: '',
    notes: '',
});
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
    account_id: props.accounts[0]?.id ?? '',
    amount: '',
    budget_period_id: selectedPeriodId.value ?? '',
    reminder_log_id: Number(query.get('reminder_log')) || null,
    idempotency_key: crypto.randomUUID(),
});
const release = useForm({
    account_id: props.accounts[0]?.id ?? '',
    amount: '',
    idempotency_key: crypto.randomUUID(),
});
const reminder = useForm({
    purpose: 'saving_allocation',
    type: null,
    priority: 'medium',
    title: 'Alokasi savings',
    amount: '',
    financial_account_id: props.accounts[0]?.id ?? null,
    category_id: null,
    saving_id: null,
    frequency: 'monthly',
    interval: 1,
    starts_at: '',
    ends_at: null,
    timezone: 'Asia/Jakarta',
    notes: '',
});
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
        savingsIndex.url(),
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
            <Link :href="dashboard.url()" class="text-sm font-semibold text-[#2457DA]">Kembali</Link>
            <h1 class="text-lg font-semibold">Savings</h1>
            <Link :href="budgetIndex.url()" class="text-sm font-semibold text-[#2457DA]">Budget</Link>
        </header>

        <main class="flex flex-col gap-5 px-6 pt-6">
            <header v-if="manageMode && selected" class="flex items-center gap-4">
                <Link
                    :href="showSaving.url(selected.id)"
                    class="rounded-full p-2 text-[#2457DA] hover:bg-[#F5F7FA]"
                    :aria-label="`Kembali ke detail ${selected.name}`"
                >
                    <ArrowLeft :size="22" />
                </Link>
                <div class="min-w-0 flex-1">
                    <p class="text-xs font-medium text-[#637083]">Savings</p>
                    <h1 class="truncate text-xl font-semibold">Kelola {{ selected.name }}</h1>
                </div>
            </header>
            <header v-if="showCreateSaving" class="flex items-center gap-4">
                <Link
                    :href="savingsIndex.url()"
                    class="rounded-full p-2 text-[#2457DA] hover:bg-[#F5F7FA]"
                    aria-label="Kembali ke Savings"
                >
                    <ArrowLeft :size="22" />
                </Link>
                <div class="min-w-0 flex-1">
                    <p class="text-xs font-medium text-[#637083]">Savings</p>
                    <h1 class="text-xl font-semibold">Buat kantong savings</h1>
                </div>
                <span class="shrink-0 text-sm font-medium text-[#637083]">Langkah {{ createStep }} dari 2</span>
            </header>
            <p v-if="!showCreateSaving && !manageMode" class="rounded-2xl bg-[#EAF0FF] p-4 text-sm">
                Dana savings adalah bagian dari saldo aktual. Alokasi hanya mengurangi uang bebas.
            </p>
            <Link
                v-if="!showCreateSaving && !manageMode"
                :href="createSavingPage.url()"
                class="inline-flex min-h-12 items-center justify-center rounded-xl bg-[#2457DA] px-5 py-3 font-semibold text-white shadow-sm"
            >
                Buat kantong savings
            </Link>
            <form
                v-if="showCreateSaving"
                class="flex flex-col gap-3 rounded-2xl bg-white p-5 shadow-sm"
                @submit.prevent="submitCreate"
            >
                <div class="flex items-center gap-2" aria-label="Langkah formulir">
                    <span
                        v-for="step in 2"
                        :key="step"
                        class="h-2 flex-1 rounded-full"
                        :class="step <= createStep ? 'bg-[#2457DA]' : 'bg-[#E2E8F0]'"
                    />
                </div>
                <template v-if="createStep === 1">
                    <p class="text-sm text-[#637083]">Buat kantong untuk memberi nama pada tujuan dana. Dana belum akan dipindahkan.</p>
                    <label class="text-sm">Nama kantong
                        <input v-model="form.name" class="field" maxlength="255" required placeholder="Contoh: Dana darurat" />
                    </label>
                    <label class="text-sm">Mata uang
                        <select v-model="form.currency" class="field">
                            <option v-for="currency in [...new Set(accounts.map((account) => account.currency))]" :key="currency" :value="currency">
                                {{ currency }}
                            </option>
                        </select>
                    </label>
                    <p v-if="form.errors.name || form.errors.currency" class="text-sm text-red-600">
                        {{ form.errors.name || form.errors.currency }}
                    </p>
                    <ButtonPrimary type="button" @click="form.name.trim() ? (createStep = 2, form.clearErrors()) : form.setError('name', 'Masukkan nama kantong.')">
                        Lanjut
                    </ButtonPrimary>
                </template>
                <template v-else>
                    <p class="text-sm text-[#637083]">Target hanya sebagai pengingat progres. Kamu bisa melewati bagian ini.</p>
                    <label class="text-sm">Target nominal (opsional)
                        <input v-model="form.target_amount" class="field" type="number" min="0.01" step="0.01" placeholder="Contoh: 1000000" />
                    </label>
                    <label class="text-sm">Target tanggal (opsional)
                        <input v-model="form.target_date" class="field" type="date" />
                    </label>
                    <label class="text-sm">Catatan (opsional)
                        <textarea v-model="form.notes" class="field" rows="2" placeholder="Untuk apa kantong ini?" />
                    </label>
                    <p v-for="(error, key) in form.errors" :key="key" class="text-sm text-red-600">{{ error }}</p>
                    <div class="flex gap-3">
                        <ButtonPrimary type="button" variant="secondary" class="flex-1 justify-center" @click="createStep = 1">
                            Kembali
                        </ButtonPrimary>
                        <ButtonPrimary type="submit" class="flex-1 justify-center" :disabled="form.processing">
                            Buat kantong
                        </ButtonPrimary>
                    </div>
                </template>
            </form>

            <section v-if="!showCreateSaving && !manageMode" class="flex flex-col gap-3">
                <h2 class="font-semibold">Kantong saya</h2>
                <p v-if="!savings.length" class="text-sm text-[#637083]">Belum ada savings.</p>
                <article
                    v-for="saving in savings"
                    :key="saving.id"
                    class="rounded-2xl bg-white p-4 shadow-sm"
                >
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <h3 class="font-semibold">{{ saving.name }}</h3>
                            <p class="text-xs text-[#637083]">{{ saving.currency }} · {{ saving.status }}</p>
                        </div>
                        <strong>Rp {{ formatMoney(saving.balance) }}</strong>
                    </div>
                    <p v-if="saving.goal_plan" class="mt-2 text-xs text-[#637083]">
                        Terkait goal {{ saving.goal_plan.name }} · target Rp {{ formatMoney(saving.goal_plan.target_amount) }}
                    </p>
                    <p v-else-if="saving.target_amount" class="mt-2 text-xs text-[#637083]">
                        Target Rp {{ formatMoney(saving.target_amount) }}
                    </p>
                    <div class="mt-3 flex gap-2">
                        <Link
                            :href="showSaving.url(saving.id)"
                            class="flex min-h-10 flex-1 items-center justify-center rounded-lg border border-[#DCE3EE] px-3 text-sm font-semibold text-[#2457DA]"
                        >Detail</Link>
                        <Link
                            :href="manageSaving.url(saving.id)"
                            class="flex min-h-10 flex-1 items-center justify-center rounded-lg bg-[#2457DA] px-3 text-sm font-semibold text-white"
                        >Kelola</Link>
                    </div>
                </article>
            </section>

            <section v-if="!showCreateSaving && selected" class="flex flex-col gap-3 rounded-2xl bg-white p-4 shadow-sm sm:p-5">
                <div class="flex flex-col gap-3">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <h2 class="font-semibold">Kelola {{ selected.name }}</h2>
                        <Link
                            :href="dashboard.url({ query: { add: 1, saving_id: selected.id } })"
                            class="text-sm font-semibold text-[#2457DA]"
                        >Catat belanja dari savings</Link>
                    </div>
                    <div v-if="!manageSection" class="flex flex-col gap-3" aria-label="Bagian kelola savings">
                        <ButtonPrimary
                            type="button"
                            variant="secondary"
                            class="group relative !flex min-h-28 w-full !flex-row items-center justify-between gap-4 overflow-hidden rounded-2xl border-0 !bg-gradient-to-br !from-[#2457DA] !to-[#123A9A] p-4 text-left !text-white shadow-lg transition duration-200 hover:-translate-y-0.5 hover:shadow-xl sm:min-h-32 sm:p-5"
                            @click="manageSection = 'transactions'"
                        >
                            <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-[#C9DCFF] text-[#123A9A] shadow-lg shadow-[#0B245C]/30 ring-1 ring-white/40">
                                <ArrowDownUp :size="24" />
                            </span>
                            <span class="flex min-w-0 flex-1 flex-col gap-1">
                                <span class="text-base font-bold sm:text-lg">Transaksi</span>
                                <span class="text-xs font-medium leading-snug text-white/80 sm:text-sm">
                                    Sisihkan dana atau lepaskan ke uang bebas
                                </span>
                            </span>
                            <ArrowRight :size="20" class="shrink-0 transition-transform group-hover:translate-x-1" />
                        </ButtonPrimary>
                        <ButtonPrimary
                            type="button"
                            variant="secondary"
                            class="group relative !flex min-h-28 w-full !flex-row items-center justify-between gap-4 overflow-hidden rounded-2xl border-0 !bg-gradient-to-br !from-[#59C7F5] !to-[#2676E8] p-4 text-left !text-white shadow-lg transition duration-200 hover:-translate-y-0.5 hover:shadow-xl sm:min-h-32 sm:p-5"
                            @click="manageSection = 'settings'"
                        >
                            <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-[#1746A2] text-white shadow-lg shadow-[#123A9A]/35 ring-1 ring-white/25">
                                <SlidersHorizontal :size="24" />
                            </span>
                            <span class="flex min-w-0 flex-1 flex-col gap-1">
                                <span class="text-base font-bold sm:text-lg">Pengaturan</span>
                                <span class="text-xs font-medium leading-snug text-white/85 sm:text-sm">
                                    Ubah kantong, buat pengingat, atau arsipkan
                                </span>
                            </span>
                            <ArrowRight :size="20" class="shrink-0 transition-transform group-hover:translate-x-1" />
                        </ButtonPrimary>
                    </div>
                    <ButtonPrimary
                        v-else
                        type="button"
                        variant="secondary"
                        class="self-start"
                        @click="manageSection = null"
                    >Kembali ke menu</ButtonPrimary>
                </div>
                <div v-if="manageSection === 'transactions'" class="flex flex-col gap-3">
                    <fieldset class="flex flex-col gap-2">
                        <legend class="text-sm font-semibold">Pilih transaksi</legend>
                        <div class="grid gap-2 sm:grid-cols-2">
                            <label class="flex min-h-12 cursor-pointer items-center gap-3 rounded-xl border border-[#DCE3EE] px-4 py-3 text-sm font-medium">
                                <input v-model="transactionMode" type="radio" name="transaction_mode" value="allocate" class="accent-[#2457DA]" />
                                {{ selected.goal_plan ? 'Setoran goal' : 'Alokasi langsung' }}
                            </label>
                            <label class="flex min-h-12 cursor-pointer items-center gap-3 rounded-xl border border-[#DCE3EE] px-4 py-3 text-sm font-medium">
                                <input v-model="transactionMode" type="radio" name="transaction_mode" value="release" class="accent-[#2457DA]" />
                                Lepas ke uang bebas
                            </label>
                        </div>
                    </fieldset>
                    <form v-if="transactionMode === 'allocate'" class="flex flex-col gap-3 rounded-xl border border-[#E9EDF3] p-4" @submit.prevent="submitAllocation">
                    <h3 class="text-sm font-semibold">
                        {{ selected.goal_plan ? 'Setoran goal' : 'Alokasi langsung' }}
                    </h3>
                    <p class="rounded-lg bg-[#F3F6FB] p-3 text-sm text-[#637083]">
                        {{ selected.goal_plan
                            ? 'Setoran goal memindahkan dana dari akun sumber ke kantong ini dan mencatatnya sebagai setoran pada periode yang dipilih.'
                            : 'Alokasi langsung memindahkan dana dari akun sumber ke kantong savings. Uang bebas di akun berkurang, tetapi dana tetap menjadi bagian dari saldo keuanganmu.' }}
                    </p>
                    <label v-if="selected.goal_plan" class="text-sm">Periode setoran
                        <select v-model="allocation.budget_period_id" class="field" required>
                            <option value="">Pilih periode</option>
                            <option
                                v-for="period in selected.goal_plan.periods.filter((item) => item.status !== 'closed')"
                                :key="period.id"
                                :value="period.id"
                            >
                                {{ period.starts_on }} – {{ period.ends_on }} · rencana Rp {{ formatMoney(period.planned_amount) }}
                            </option>
                        </select>
                    </label>
                    <label class="text-sm">Akun sumber
                        <select v-model="allocation.account_id" class="field" required>
                            <option v-for="account in accounts.filter((item) => item.currency === selected.currency)" :key="account.id" :value="account.id">
                                {{ account.name }}
                            </option>
                        </select>
                    </label>
                    <label class="text-sm">Nominal
                        <input v-model="allocation.amount" class="field" type="number" min="0.01" step="0.01" required />
                    </label>
                    <p v-for="(error, key) in allocation.errors" :key="key" class="text-sm text-red-600">{{ error }}</p>
                    <ButtonPrimary type="submit" :disabled="allocation.processing">Sisihkan Dana</ButtonPrimary>
                    </form>
                    <form v-else class="flex flex-col gap-3 rounded-xl border border-[#E9EDF3] p-4" @submit.prevent="submitRelease">
                    <h3 class="text-sm font-semibold">Lepas ke uang bebas</h3>
                    <p class="rounded-lg bg-[#F3F6FB] p-3 text-sm text-[#637083]">
                        Mengembalikan dana dari kantong savings ke uang bebas pada akun yang dipilih. Total saldomu tidak berubah.
                    </p>
                    <label class="text-sm">Akun lokasi dana
                        <select v-model="release.account_id" class="field" required>
                            <option v-for="account in accounts.filter((item) => item.currency === selected.currency)" :key="account.id" :value="account.id">
                                {{ account.name }}
                            </option>
                        </select>
                    </label>
                    <label class="text-sm">Nominal
                        <input v-model="release.amount" class="field" type="number" min="0.01" step="0.01" required />
                    </label>
                    <p v-for="(error, key) in release.errors" :key="key" class="text-sm text-red-600">{{ error }}</p>
                    <ButtonPrimary type="submit" variant="secondary" :disabled="release.processing">Lepas dana</ButtonPrimary>
                    </form>
                </div>
                <div v-if="manageSection === 'settings'" class="flex flex-col gap-3">
                    <fieldset class="flex flex-col gap-2">
                        <legend class="text-sm font-semibold">Pilih pengaturan</legend>
                        <div class="grid gap-2 sm:grid-cols-3">
                            <label v-if="!selected.goal_plan" class="flex min-h-12 cursor-pointer items-center gap-3 rounded-xl border border-[#DCE3EE] px-4 py-3 text-sm font-medium">
                                <input v-model="settingsMode" type="radio" name="settings_mode" value="edit" class="accent-[#2457DA]" />
                                Ubah kantong
                            </label>
                            <label v-if="!selected.goal_plan" class="flex min-h-12 cursor-pointer items-center gap-3 rounded-xl border border-[#DCE3EE] px-4 py-3 text-sm font-medium">
                                <input v-model="settingsMode" type="radio" name="settings_mode" value="reminder" class="accent-[#2457DA]" />
                                Pengingat alokasi
                            </label>
                            <label class="flex min-h-12 cursor-pointer items-center gap-3 rounded-xl border border-[#DCE3EE] px-4 py-3 text-sm font-medium">
                                <input v-model="settingsMode" type="radio" name="settings_mode" value="archive" class="accent-[#2457DA]" />
                                Arsipkan kantong
                            </label>
                        </div>
                    </fieldset>
                    <form v-if="settingsMode === 'edit' && !selected.goal_plan" class="flex flex-col gap-3 rounded-xl border border-[#E9EDF3] p-4" @submit.prevent="submitUpdate">
                        <h3 class="text-sm font-semibold">Ubah kantong</h3>
                        <p class="rounded-lg bg-[#F3F6FB] p-3 text-sm text-[#637083]">
                            Perbarui nama atau target kantong. Perubahan target tidak memindahkan dana.
                        </p>
                        <label class="text-sm">Nama
                            <input v-model="form.name" class="field" required />
                        </label>
                        <label class="text-sm">Target nominal
                            <input v-model="form.target_amount" class="field" type="number" min="0.01" step="0.01" />
                        </label>
                        <label class="text-sm">Target tanggal
                            <input v-model="form.target_date" class="field" type="date" />
                        </label>
                        <ButtonPrimary type="submit" variant="secondary">Simpan perubahan</ButtonPrimary>
                    </form>
                    <form v-else-if="settingsMode === 'reminder' && !selected.goal_plan" class="flex flex-col gap-3 rounded-xl border border-[#E9EDF3] p-4" @submit.prevent="submitReminder">
                        <h3 class="text-sm font-semibold">Pengingat alokasi</h3>
                        <p class="rounded-lg bg-[#F3F6FB] p-3 text-sm text-[#637083]">
                            Buat pengingat berkala sebagai jadwal untuk menyisihkan dana ke kantong ini.
                        </p>
                        <label class="text-sm">Nominal perkiraan (opsional)
                            <input v-model="reminder.amount" class="field" type="number" min="0" step="0.01" />
                        </label>
                        <label class="text-sm">Mulai pada
                            <input v-model="reminder.starts_at" class="field" type="datetime-local" required />
                        </label>
                        <label class="text-sm">Interval bulan
                            <input v-model="reminder.interval" class="field" type="number" min="1" required />
                        </label>
                        <p v-for="(error, key) in reminder.errors" :key="key" class="text-sm text-red-600">{{ error }}</p>
                        <ButtonPrimary type="submit" variant="secondary" :disabled="reminder.processing">
                            Buat pengingat
                        </ButtonPrimary>
                    </form>
                    <div v-else class="flex flex-col gap-3 rounded-xl border border-[#E9EDF3] p-4">
                        <h3 class="text-sm font-semibold">Arsipkan kantong</h3>
                        <p class="rounded-lg bg-[#F3F6FB] p-3 text-sm text-[#637083]">
                            Kantong yang diarsipkan tidak lagi digunakan untuk alokasi baru.
                        </p>
                        <ButtonPrimary type="button" variant="secondary" @click="archive">Arsipkan kantong</ButtonPrimary>
                        <p v-if="actionError" class="text-sm text-red-600">{{ actionError }}</p>
                    </div>
                </div>
            </section>

            <section v-if="!showCreateSaving && selected" class="flex flex-col gap-3 rounded-2xl bg-white p-4 shadow-sm sm:p-5">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="font-semibold">Riwayat alokasi</h2>
                    <ButtonPrimary
                        type="button"
                        variant="secondary"
                        class="inline-flex items-center gap-2"
                        aria-label="Buka filter riwayat alokasi"
                        @click="historyFilterModalOpen = true"
                    >
                        <Filter :size="16" />
                        Filter
                        <span v-if="activeHistoryFilterCount" class="rounded-full bg-[#2457DA] px-2 py-0.5 text-xs text-white">
                            {{ activeHistoryFilterCount }}
                        </span>
                    </ButtonPrimary>
                </div>
                <p v-if="activeHistoryFilterCount" class="text-xs text-[#637083]">
                    {{ activeHistoryFilterCount }} filter diterapkan
                </p>
                <div class="max-h-48 overflow-y-auto">
                    <p v-for="log in selected.logs" :key="log.id" class="flex items-center justify-between gap-2 border-t border-[#F1F3F7] py-2 text-sm">
                        <span>{{ log.reason }} · {{ log.occurred_at }}</span>
                        <span>{{ log.type === 'allocation' ? '+' : '-' }} Rp {{ formatMoney(log.amount) }}</span>
                        <ButtonPrimary
                            v-if="['direct_allocation', 'goal_contribution'].includes(log.reason) && !log.reversal"
                            type="button"
                            variant="secondary"
                            class="!min-h-0 !px-2 !py-1 !text-xs"
                            @click="reverseLog(log)"
                        >Balik</ButtonPrimary>
                    </p>
                </div>
            </section>

            <div
                v-if="historyFilterModalOpen"
                class="fixed inset-0 z-50 flex items-center justify-center bg-[#152338]/40 p-4"
                role="dialog"
                aria-modal="true"
                aria-labelledby="history-filter-title"
                tabindex="-1"
                @click.self="historyFilterModalOpen = false"
                @keydown.esc="historyFilterModalOpen = false"
            >
                <section class="max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-2xl bg-white p-5 shadow-xl">
                    <div class="mb-4 flex items-center justify-between gap-3">
                        <h2 id="history-filter-title" class="font-semibold">Filter riwayat alokasi</h2>
                        <ButtonPrimary
                            type="button"
                            variant="secondary"
                            class="!min-h-0 !p-2"
                            aria-label="Tutup filter"
                            @click="historyFilterModalOpen = false"
                        >
                            <X :size="18" />
                        </ButtonPrimary>
                    </div>
                    <form class="flex flex-col gap-3" @submit.prevent="applyHistoryFilters">
                        <label class="text-sm">Sumber transaksi
                            <select v-model="sourceFilter" class="field">
                                <option value="">Semua sumber</option>
                                <option value="direct_allocation">Alokasi langsung</option>
                                <option value="goal_contribution">Setoran goal</option>
                                <option value="budget_claim">Klaim budget</option>
                                <option value="free_release">Lepas dana</option>
                                <option value="expense">Belanja</option>
                                <option value="reversal">Koreksi</option>
                            </select>
                        </label>
                        <label class="text-sm">Periode
                            <select v-model="periodFilter" class="field">
                                <option value="">Semua periode</option>
                                <option v-for="period in periodOptions" :key="period.id" :value="period.id">
                                    {{ period.label }}
                                </option>
                            </select>
                        </label>
                        <label class="text-sm">Akun
                            <select v-model="accountFilter" class="field">
                                <option value="">Semua akun</option>
                                <option v-for="account in accounts" :key="account.id" :value="account.id">
                                    {{ account.name }}
                                </option>
                            </select>
                        </label>
                        <div class="mt-2 flex gap-2">
                            <ButtonPrimary
                                type="button"
                                variant="secondary"
                                class="flex-1 justify-center"
                                @click="sourceFilter = ''; periodFilter = ''; accountFilter = ''"
                            >Reset</ButtonPrimary>
                            <ButtonPrimary type="submit" class="flex-1 justify-center">
                                Terapkan filter
                            </ButtonPrimary>
                        </div>
                    </form>
                </section>
            </div>
        </main>
        <MobileBottomNav @add="router.visit(dashboard.url({ query: { add: 1 } }))" />
    </div>
</template>

<style scoped>
.field {
    @apply mt-1 w-full rounded-xl border border-[#DCE3EE] bg-white px-3 py-2 text-sm;
}
</style>
