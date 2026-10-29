<script setup>
import { computed, ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import {
    ArrowDown,
    ArrowLeft,
    ArrowUp,
    Bell,
    CalendarDays,
    ChevronDown,
    Wallet,
    X,
} from 'lucide-vue-next';

const props = defineProps({
    saving: { type: Object, required: true },
    reminders: { type: Array, default: () => [] },
    accounts: { type: Array, default: () => [] },
    categories: { type: Array, default: () => [] },
    form: { type: Object, required: true },
    reminder: { type: Object, required: true },
    allocation: { type: Object, required: true },
    expense: { type: Object, required: true },
    modalAction: { type: String, default: null },
    startDate: { type: String, default: '' },
    startTime: { type: String, default: '09:00' },
    formatMoney: { type: Function, required: true },
    formatDate: { type: Function, required: true },
    indexUrl: { type: String, required: true },
    actionError: { type: String, default: '' },
});

const emit = defineEmits([
    'select-tab',
    'update:startDate',
    'update:startTime',
    'update-saving',
    'save-reminder',
    'toggle-reminder',
    'open-transactions',
    'close-modal',
    'allocate',
    'record-expense',
    'archive',
]);

const activeTab = defineModel('activeTab', { type: String, default: 'activity' });
const activityFilter = ref('all');

const currentReminder = computed(() => props.reminders[0] ?? null);
const amountForm = computed(() => props.modalAction === 'allocate'
    ? props.allocation
    : props.expense);
const noteValue = computed({
    get: () => props.modalAction === 'allocate'
        ? props.allocation.notes
        : props.expense.note,
    set: (value) => {
        if (props.modalAction === 'allocate') {
            props.allocation.notes = value;
        } else {
            props.expense.note = value;
        }
    },
});
const selectedAccount = computed(() => props.accounts.find(
    (account) => Number(account.id) === Number(amountForm.value.account_id),
) ?? null);
const selectedAccountBalance = computed(() => props.modalAction === 'allocate'
    ? selectedAccount.value?.available_balance ?? 0
    : selectedAccount.value?.saving_balance ?? 0);
const balanceAfterAction = computed(() => {
    const amount = Number(amountForm.value.amount ?? 0);
    const currentBalance = Number(props.saving.balance ?? 0);

    return props.modalAction === 'allocate'
        ? currentBalance + amount
        : Math.max(0, currentBalance - amount);
});
const progress = computed(() => {
    const target = Number(props.saving.target_amount ?? 0);

    if (target <= 0) {
        return 0;
    }

    return Math.min(100, Math.round(Number(props.saving.balance ?? 0) / target * 100));
});
const remaining = computed(() => Math.max(
    0,
    Number(props.saving.target_amount ?? 0) - Number(props.saving.balance ?? 0),
));
const filteredLogs = computed(() => props.saving.logs?.filter((log) => {
    if (activityFilter.value === 'all') {
        return true;
    }

    return activityFilter.value === 'in' ? log.type === 'allocation' : log.type !== 'allocation';
}) ?? []);

const tabItems = [
    { id: 'activity', label: 'Aktivitas' },
    { id: 'reminder', label: 'Pengingat' },
    { id: 'settings', label: 'Pengaturan' },
];

const activityLabel = (reason) => ({
    direct_allocation: 'Alokasi dana',
    goal_contribution: 'Setoran goal',
    budget_claim: 'Klaim budget',
    free_release: 'Penggunaan dana',
    expense: 'Belanja',
    reversal: 'Koreksi dana',
}[reason] ?? String(reason ?? '').replaceAll('_', ' '));

const reminderNextDate = computed(() => {
    if (!currentReminder.value?.next_reminder_at) {
        return null;
    }

    return props.formatDate(currentReminder.value.next_reminder_at);
});
const reminderScheduleLabel = computed(() => {
    if (!currentReminder.value) {
        return '';
    }

    if (currentReminder.value.frequency === 'monthly') {
        const day = Number(currentReminder.value.starts_at?.slice(8, 10) ?? 1);

        return `Setiap tanggal ${day}`;
    }

    const frequencyLabels = {
        daily: 'Setiap hari',
        weekly: 'Setiap minggu',
        yearly: 'Setiap tahun',
    };

    return frequencyLabels[currentReminder.value.frequency] ?? 'Jadwal berkala';
});
const accountsForModal = computed(() => props.accounts.filter((account) => {
    if (account.currency !== props.saving.currency) {
        return false;
    }

    return props.modalAction === 'allocate'
        ? Number(account.available_balance) > 0
        : Number(account.saving_balance) > 0;
}));
const updateAmount = (amount) => {
    amountForm.value.amount = String(Number(amountForm.value.amount ?? 0) + amount);
};
</script>

<template>
    <div class="flex flex-col gap-6 text-[#14213A]">
        <div class="flex flex-col gap-4">
            <Link
                :href="indexUrl"
                class="inline-flex items-center gap-2 self-start text-sm font-semibold text-[#1764E8] hover:text-[#104FC1]"
            >
                <ArrowLeft :size="19" />
                Semua tabungan
            </Link>

            <div class="flex flex-wrap items-center gap-3">
                <h1 class="text-3xl font-bold tracking-tight sm:text-4xl">{{ saving.name }}</h1>
                <span class="rounded-full bg-[#E8F7EE] px-4 py-1.5 text-sm font-semibold capitalize text-[#16804A]">
                    {{ saving.status === 'active' ? 'Aktif' : 'Diarsipkan' }}
                </span>
            </div>
        </div>

        <section class="grid gap-6 rounded-2xl border border-[#E1E8F2] bg-white p-5 shadow-[0_2px_8px_rgba(25,50,90,0.04)] lg:grid-cols-[minmax(0,1fr)_310px] lg:p-7">
            <div class="flex min-w-0 flex-col justify-center">
                <p class="text-base font-medium text-[#5B6B85]">Saldo tabungan</p>
                <p class="mt-1 text-3xl font-bold tracking-tight sm:text-4xl">
                    {{ $t("Rp") }}{{ formatMoney(saving.balance) }}
                </p>

                <template v-if="Number(saving.target_amount) > 0">
                    <div class="mt-4 flex items-center justify-between gap-4 text-sm sm:text-base">
                        <p><strong>{{ progress }}%</strong> tercapai</p>
                        <p class="text-right text-[#5B6B85]">Target {{ $t("Rp") }}{{ formatMoney(saving.target_amount) }}</p>
                    </div>
                    <div
                        class="mt-2 h-3 overflow-hidden rounded-full bg-[#E8EDF4]"
                        role="progressbar"
                        :aria-valuenow="progress"
                        aria-valuemin="0"
                        aria-valuemax="100"
                        :aria-label="`Progress tabungan ${saving.name}`"
                    >
                        <div
                            class="h-full rounded-full bg-[#1764E8] transition-[width]"
                            :style="{ width: `${progress}%` }"
                        />
                    </div>
                    <div class="mt-3 flex justify-between gap-4 text-sm text-[#62738E]">
                        <p>Kurang {{ $t("Rp") }}{{ formatMoney(remaining) }}</p>
                        <p class="text-right">{{ formatDate(saving.target_date) }}</p>
                    </div>
                </template>
                <p v-else class="mt-3 text-sm text-[#62738E]">Belum ada target tabungan.</p>
            </div>

            <div class="flex flex-col justify-center gap-3 border-t border-[#E8EDF4] pt-5 lg:border-l lg:border-t-0 lg:pl-8 lg:pt-0">
                <ButtonPrimary
                    type="button"
                    class="inline-flex w-full items-center justify-center gap-3 !rounded-xl !py-3"
                    @click="emit('open-transactions', 'allocate')"
                >
                    <span class="text-2xl font-light leading-none">+</span>
                    Tambah dana
                </ButtonPrimary>
                <ButtonPrimary
                    type="button"
                    variant="secondary"
                    class="inline-flex w-full items-center justify-center gap-3 !rounded-xl !border-[#1764E8] !py-3 !text-[#1764E8]"
                    @click="emit('open-transactions', 'expense')"
                >
                    <ArrowUp :size="20" />
                    Gunakan dana
                </ButtonPrimary>
            </div>
        </section>

        <nav class="flex gap-2 overflow-x-auto border-b border-[#DCE4EF]" aria-label="Bagian tabungan">
            <button
                v-for="tab in tabItems"
                :key="tab.id"
                type="button"
                class="shrink-0 border-b-2 px-4 py-3 text-sm font-medium transition sm:px-6 sm:text-base"
                :class="activeTab === tab.id
                    ? 'border-[#1764E8] text-[#1764E8]'
                    : 'border-transparent text-[#60708A] hover:text-[#14213A]'"
                :aria-current="activeTab === tab.id ? 'page' : undefined"
                @click="activeTab = tab.id"
            >
                {{ tab.label }}
            </button>
        </nav>

        <section v-if="activeTab === 'activity'" class="grid items-stretch gap-5 xl:grid-cols-[minmax(0,1.65fr)_minmax(300px,0.8fr)]">
            <article class="rounded-2xl border border-[#E1E8F2] bg-white p-5 shadow-[0_2px_8px_rgba(25,50,90,0.04)] sm:p-6">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <h2 class="text-xl font-bold">Riwayat transaksi</h2>
                    <label class="relative">
                        <span class="sr-only">Filter aktivitas</span>
                        <select
                            v-model="activityFilter"
                            class="h-10 appearance-none rounded-lg border border-[#D9E1EC] bg-white py-2 pl-3 pr-9 text-sm text-[#53647E] focus:border-[#1764E8] focus:ring-2 focus:ring-blue-100"
                        >
                            <option value="all">Semua aktivitas</option>
                            <option value="in">Dana masuk</option>
                            <option value="out">Dana keluar</option>
                        </select>
                        <ChevronDown :size="16" class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-[#60708A]" />
                    </label>
                </div>

                <div v-if="filteredLogs.length" class="mt-3 divide-y divide-[#E8EDF4]">
                    <article
                        v-for="log in filteredLogs"
                        :key="log.id"
                        class="flex items-center gap-4 py-4"
                    >
                        <span
                            class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full"
                            :class="log.type === 'allocation'
                                ? 'bg-[#EAF7EF] text-[#17834F]'
                                : 'bg-[#FDEDEC] text-[#D94343]'"
                        >
                            <ArrowDown v-if="log.type === 'allocation'" :size="23" />
                            <ArrowUp v-else :size="23" />
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="truncate font-medium">{{ activityLabel(log.reason) }}</p>
                            <p v-if="log.notes" class="mt-1 truncate text-sm text-[#64748B]">{{ log.notes }}</p>
                            <p class="mt-1 text-sm text-[#64748B]">
                                {{ formatDate(log.occurred_at) }}<span v-if="log.account?.name"> · {{ log.account.name }}</span>
                            </p>
                        </div>
                        <p
                            class="shrink-0 text-right font-semibold"
                            :class="log.type === 'allocation' ? 'text-[#16804A]' : 'text-[#14213A]'"
                        >
                            {{ log.type === 'allocation' ? '+' : '−' }}{{ $t("Rp") }}{{ formatMoney(log.amount) }}
                        </p>
                    </article>
                </div>
                <p v-else class="py-10 text-center text-sm text-[#64748B]">Belum ada aktivitas untuk filter ini.</p>
            </article>

            <article class="flex flex-col rounded-2xl border border-[#E1E8F2] bg-white p-5 shadow-[0_2px_8px_rgba(25,50,90,0.04)] sm:p-6">
                <h2 class="text-xl font-bold">Jadwal menabung</h2>
                <div v-if="currentReminder" class="mt-5 flex items-start gap-4">
                    <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-[#EDF4FF] text-[#1764E8]">
                        <Bell :size="22" />
                    </span>
                    <div class="min-w-0">
                        <p class="text-sm">{{ reminderScheduleLabel }}</p>
                        <p v-if="Number(currentReminder.amount) > 0" class="mt-1 text-2xl font-bold">
                            {{ $t("Rp") }}{{ formatMoney(currentReminder.amount) }}
                        </p>
                        <p class="mt-1 text-sm text-[#64748B]">
                            {{ currentReminder.status === 'active' ? 'Berikutnya' : 'Pengingat dijeda' }}
                            <template v-if="reminderNextDate"> {{ reminderNextDate }}</template>
                        </p>
                    </div>
                </div>
                <p v-else class="mt-5 text-sm text-[#64748B]">Tidak ada transfer otomatis.</p>

                <button
                    type="button"
                    class="mt-5 self-start text-sm font-semibold text-[#1764E8] hover:text-[#104FC1]"
                    @click="activeTab = 'reminder'"
                >
                    {{ currentReminder ? 'Ubah jadwal' : 'Atur jadwal' }}
                </button>
                <p v-if="!currentReminder" class="mt-4 border-t border-[#E8EDF4] pt-4 text-sm text-[#64748B]">
                    Buat pengingat berkala untuk membantu menabung.
                </p>
            </article>
        </section>

        <section v-else-if="activeTab === 'reminder'" class="rounded-2xl border border-[#E1E8F2] bg-white p-5 shadow-[0_2px_8px_rgba(25,50,90,0.04)] sm:p-7">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h2 class="text-xl font-bold">Pengingat menabung</h2>
                    <p class="mt-1 text-sm text-[#64748B]">Atur jadwal dan nominal pengingat tabungan.</p>
                </div>
                <button
                    v-if="currentReminder"
                    type="button"
                    role="switch"
                    :aria-checked="currentReminder.status === 'active'"
                    :aria-label="currentReminder.status === 'active' ? 'Jeda pengingat' : 'Aktifkan pengingat'"
                    class="flex items-center gap-3 text-sm font-medium"
                    @click="emit('toggle-reminder', currentReminder)"
                >
                    <span
                        class="relative h-7 w-12 rounded-full transition"
                        :class="currentReminder.status === 'active' ? 'bg-[#1764E8]' : 'bg-[#CBD5E1]'"
                    >
                        <span
                            class="absolute top-1 h-5 w-5 rounded-full bg-white shadow-sm transition"
                            :class="currentReminder.status === 'active' ? 'left-6' : 'left-1'"
                        />
                    </span>
                    {{ currentReminder.status === 'active' ? 'Aktif' : 'Dijeda' }}
                </button>
                <span v-else class="rounded-full bg-[#F1F5F9] px-3 py-1 text-sm text-[#53647E]">Belum aktif</span>
            </div>

            <form class="mt-6 grid gap-x-6 gap-y-5 md:grid-cols-2" @submit.prevent="emit('save-reminder')">
                <label class="flex flex-col gap-2 text-sm font-medium">
                    Nominal (opsional)
                    <span class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center border-r border-[#D9E1EC] bg-[#F4F6F9] px-4 text-[#60708A]">Rp</span>
                        <AmountInput v-model="reminder.amount" class="w-full rounded-lg border border-[#D9E1EC] py-3 pl-[4.5rem] pr-3 text-sm focus:border-[#1764E8] focus:ring-2 focus:ring-blue-100" />
                    </span>
                    <span v-if="reminder.errors.amount" class="text-xs font-normal text-red-600">{{ reminder.errors.amount }}</span>
                </label>

                <label class="flex flex-col gap-2 text-sm font-medium">
                    Frekuensi
                    <select v-model="reminder.frequency" class="h-12 rounded-lg border border-[#D9E1EC] bg-white px-3 text-sm focus:border-[#1764E8] focus:ring-2 focus:ring-blue-100">
                        <option value="monthly">Setiap bulan</option>
                        <option value="weekly">Setiap minggu</option>
                        <option value="daily">Setiap hari</option>
                        <option value="yearly">Setiap tahun</option>
                    </select>
                    <span v-if="reminder.errors.frequency" class="text-xs font-normal text-red-600">{{ reminder.errors.frequency }}</span>
                </label>

                <label class="flex flex-col gap-2 text-sm font-medium">
                    Mulai tanggal
                    <span class="relative">
                        <input
                            :value="startDate"
                            type="date"
                            required
                            class="h-12 w-full rounded-lg border border-[#D9E1EC] bg-white px-3 pr-10 text-sm focus:border-[#1764E8] focus:ring-2 focus:ring-blue-100"
                            @input="emit('update:startDate', $event.target.value)"
                        />
                        <CalendarDays :size="18" class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-[#53647E]" />
                    </span>
                    <span v-if="reminder.errors.starts_at" class="text-xs font-normal text-red-600">{{ reminder.errors.starts_at }}</span>
                </label>

                <label class="flex flex-col gap-2 text-sm font-medium">
                    Waktu
                    <input
                        :value="startTime"
                        type="time"
                        required
                        class="h-12 rounded-lg border border-[#D9E1EC] bg-white px-3 text-sm focus:border-[#1764E8] focus:ring-2 focus:ring-blue-100"
                        @input="emit('update:startTime', $event.target.value)"
                    />
                </label>

                <div class="flex flex-col gap-2 border-t border-[#E8EDF4] pt-5 text-sm text-[#64748B] md:col-span-2 md:flex-row md:items-center md:justify-between">
                    <p>Tidak ada transfer otomatis. Pengingat membantu Anda menyisihkan dana secara berkala.</p>
                    <ButtonPrimary type="submit" :disabled="reminder.processing" class="self-start md:self-auto">
                        {{ reminder.processing ? 'Menyimpan…' : 'Simpan pengingat' }}
                    </ButtonPrimary>
                </div>
                <p v-if="actionError" class="text-sm text-red-600 md:col-span-2">{{ actionError }}</p>
            </form>
        </section>

        <section v-else class="flex flex-col gap-3">
            <form class="rounded-2xl border border-[#E1E8F2] bg-white p-5 shadow-[0_2px_8px_rgba(25,50,90,0.04)] sm:p-7" @submit.prevent="emit('update-saving')">
                <h2 class="text-xl font-bold">Detail tabungan</h2>
                <div class="mt-5 grid gap-5 md:grid-cols-2">
                    <label class="flex flex-col gap-2 text-sm font-medium md:col-span-2">
                        Nama tabungan
                        <input v-model="form.name" required class="h-12 rounded-lg border border-[#D9E1EC] px-3 text-sm focus:border-[#1764E8] focus:ring-2 focus:ring-blue-100" />
                        <span v-if="form.errors.name" class="text-xs font-normal text-red-600">{{ form.errors.name }}</span>
                    </label>
                    <label class="flex flex-col gap-2 text-sm font-medium">
                        Target dana
                        <AmountInput v-model="form.target_amount" class="h-12 rounded-lg border border-[#D9E1EC] px-3 text-sm focus:border-[#1764E8] focus:ring-2 focus:ring-blue-100" />
                        <span v-if="form.errors.target_amount" class="text-xs font-normal text-red-600">{{ form.errors.target_amount }}</span>
                    </label>
                    <label class="flex flex-col gap-2 text-sm font-medium">
                        Target tanggal (opsional)
                        <input v-model="form.target_date" type="date" class="h-12 rounded-lg border border-[#D9E1EC] px-3 text-sm focus:border-[#1764E8] focus:ring-2 focus:ring-blue-100" />
                        <span v-if="form.errors.target_date" class="text-xs font-normal text-red-600">{{ form.errors.target_date }}</span>
                    </label>
                </div>
                <div class="mt-5 flex flex-col gap-4 border-t border-[#E8EDF4] pt-5 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-sm text-[#64748B]">Perubahan target tidak mengubah saldo.</p>
                    <ButtonPrimary type="submit" :disabled="form.processing">
                        {{ form.processing ? 'Menyimpan…' : 'Simpan perubahan' }}
                    </ButtonPrimary>
                </div>
            </form>

            <div class="flex flex-col gap-4 rounded-2xl border border-[#E1E8F2] bg-white p-5 sm:flex-row sm:items-center sm:justify-between sm:px-7">
                <div>
                    <h2 class="font-bold">Arsipkan tabungan</h2>
                    <p class="mt-1 text-sm text-[#64748B]">Alokasi baru dihentikan. Saldo dan riwayat tetap ada.</p>
                    <p v-if="actionError" class="mt-2 text-sm text-red-600">{{ actionError }}</p>
                </div>
                <ButtonPrimary
                    type="button"
                    variant="secondary"
                    class="!border-red-300 !text-red-600 hover:!bg-red-50"
                    @click="emit('archive')"
                >
                    Arsipkan
                </ButtonPrimary>
            </div>
        </section>

        <div
            v-if="modalAction"
            class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-[#152338]/70 p-4"
            role="presentation"
            @click.self="emit('close-modal')"
            @keydown.esc="emit('close-modal')"
        >
            <form
                class="my-auto max-h-[94vh] w-full max-w-[526px] overflow-y-auto rounded-2xl bg-white p-5 text-[#14213A] shadow-2xl sm:p-7"
                role="dialog"
                aria-modal="true"
                :aria-labelledby="modalAction === 'allocate' ? 'saving-allocation-title' : 'saving-expense-title'"
                @submit.prevent="emit(modalAction === 'allocate' ? 'allocate' : 'record-expense')"
            >
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h2
                            :id="modalAction === 'allocate' ? 'saving-allocation-title' : 'saving-expense-title'"
                            class="text-xl font-bold"
                        >
                            {{ modalAction === 'allocate' ? 'Tambah dana' : 'Gunakan dana' }}
                        </h2>
                        <p class="mt-1 text-sm text-[#64748B]">{{ saving.name }}</p>
                    </div>
                    <ButtonPrimary
                        type="button"
                        variant="secondary"
                        class="!min-h-0 !rounded-full !border-0 !p-2 !text-[#64748B]"
                        aria-label="Tutup modal"
                        @click="emit('close-modal')"
                    >
                        <X :size="22" />
                    </ButtonPrimary>
                </div>

                <template v-if="modalAction === 'allocate'">
                    <label class="mt-5 flex flex-col gap-2 text-sm font-semibold">
                        Sumber dana
                        <span class="relative">
                            <Wallet :size="20" class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-[#1764E8]" />
                            <select
                                v-model="allocation.account_id"
                                class="h-12 w-full appearance-none rounded-lg border border-[#D9E1EC] bg-white py-2 pl-12 pr-10 text-sm font-normal focus:border-[#1764E8] focus:ring-2 focus:ring-blue-100"
                                required
                            >
                                <option v-for="account in accountsForModal" :key="account.id" :value="account.id">
                                    {{ account.name }}
                                </option>
                            </select>
                            <ChevronDown :size="17" class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-[#64748B]" />
                        </span>
                        <span class="text-sm font-normal text-[#64748B]">
                            Tersedia {{ $t("Rp") }}{{ formatMoney(selectedAccountBalance) }}
                        </span>
                        <span v-if="allocation.errors.account_id" class="text-xs font-normal text-red-600">{{ allocation.errors.account_id }}</span>
                    </label>

                    <label v-if="saving.goal_plan" class="mt-4 flex flex-col gap-2 text-sm font-semibold">
                        Periode setoran
                        <select v-model="allocation.budget_period_id" class="h-12 rounded-lg border border-[#D9E1EC] bg-white px-3 text-sm font-normal" required>
                            <option value="">Pilih periode</option>
                            <option
                                v-for="period in saving.goal_plan.periods.filter((item) => item.status !== 'closed')"
                                :key="period.id"
                                :value="period.id"
                            >
                                {{ period.starts_on }} – {{ period.ends_on }}
                            </option>
                        </select>
                        <span v-if="allocation.errors.budget_period_id" class="text-xs font-normal text-red-600">{{ allocation.errors.budget_period_id }}</span>
                    </label>
                </template>

                <div v-else class="mt-5 flex items-center justify-between gap-3 rounded-lg bg-[#EDF4FF] px-4 py-3 text-sm">
                    <span class="text-[#53647E]">Saldo tersedia</span>
                    <strong class="text-base text-[#1764E8]">{{ $t("Rp") }}{{ formatMoney(saving.balance) }}</strong>
                </div>
                <p v-if="!accountsForModal.length" class="mt-4 rounded-lg bg-[#FFF4E5] px-4 py-3 text-sm text-[#9A5B00]">
                    {{ modalAction === 'allocate'
                        ? 'Tidak ada saldo bebas pada akun dengan mata uang yang sesuai.'
                        : 'Dana tabungan ini tidak tersedia pada akun yang dapat digunakan.' }}
                </p>

                <label class="mt-5 flex flex-col gap-2 text-sm font-semibold">
                    Nominal
                    <span class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center border-r border-[#D9E1EC] bg-[#F4F6F9] px-4 font-normal text-[#64748B]">Rp</span>
                        <AmountInput
                            v-model="amountForm.amount"
                            class="h-12 w-full rounded-lg border border-[#D9E1EC] py-2 pl-[4.5rem] pr-3 text-sm font-normal focus:border-[#1764E8] focus:ring-2 focus:ring-blue-100"
                            required
                        />
                    </span>
                    <span v-if="amountForm.errors.amount" class="text-xs font-normal text-red-600">{{ amountForm.errors.amount }}</span>
                </label>

                <div class="mt-3 flex gap-2">
                    <ButtonPrimary
                        v-for="amount in [100000, 500000]"
                        :key="amount"
                        type="button"
                        variant="secondary"
                        class="!min-h-0 !rounded-full !border-[#C7D9FF] !bg-[#F2F6FF] !px-4 !py-1.5 !text-sm !text-[#1764E8]"
                        @click="updateAmount(amount)"
                    >
                        +{{ formatMoney(amount) }}
                    </ButtonPrimary>
                </div>

                <template v-if="modalAction === 'expense'">
                    <label class="mt-5 flex flex-col gap-2 text-sm font-semibold">
                        Bayar dari akun
                        <span class="relative">
                            <Wallet :size="19" class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-[#1764E8]" />
                            <select
                                v-model="expense.account_id"
                                class="h-12 w-full appearance-none rounded-lg border border-[#D9E1EC] bg-white py-2 pl-12 pr-10 text-sm font-normal focus:border-[#1764E8] focus:ring-2 focus:ring-blue-100"
                                required
                            >
                                <option v-for="account in accountsForModal" :key="account.id" :value="account.id">
                                    {{ account.name }}
                                </option>
                            </select>
                            <ChevronDown :size="17" class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-[#64748B]" />
                        </span>
                        <span v-if="expense.errors.account_id" class="text-xs font-normal text-red-600">{{ expense.errors.account_id }}</span>
                    </label>

                    <label class="mt-4 flex flex-col gap-2 text-sm font-semibold">
                        Kategori
                        <span class="relative">
                            <select
                                v-model="expense.category_id"
                                class="h-12 w-full appearance-none rounded-lg border border-[#D9E1EC] bg-white px-3 pr-10 text-sm font-normal focus:border-[#1764E8] focus:ring-2 focus:ring-blue-100"
                            >
                                <option value="">Pilih kategori</option>
                                <option v-for="category in categories" :key="category.id" :value="category.id">
                                    {{ category.name }}
                                </option>
                            </select>
                            <ChevronDown :size="17" class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-[#64748B]" />
                        </span>
                        <span v-if="expense.errors.category_id" class="text-xs font-normal text-red-600">{{ expense.errors.category_id }}</span>
                    </label>
                    <p v-if="expense.errors.saving_id" class="mt-3 text-xs text-red-600">{{ expense.errors.saving_id }}</p>
                </template>

                <label class="mt-5 flex flex-col gap-2 text-sm font-semibold">
                    Tanggal
                    <span class="relative">
                        <input
                            v-model="amountForm.date"
                            type="date"
                            required
                            class="h-12 w-full rounded-lg border border-[#D9E1EC] bg-white px-3 pr-10 text-sm font-normal focus:border-[#1764E8] focus:ring-2 focus:ring-blue-100"
                        />
                        <CalendarDays :size="18" class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-[#53647E]" />
                    </span>
                    <span v-if="amountForm.errors.date" class="text-xs font-normal text-red-600">{{ amountForm.errors.date }}</span>
                </label>

                <label class="mt-5 flex flex-col gap-2 text-sm font-semibold">
                    Catatan (opsional)
                    <input
                        v-model="noteValue"
                        class="h-12 rounded-lg border border-[#D9E1EC] px-3 text-sm font-normal focus:border-[#1764E8] focus:ring-2 focus:ring-blue-100"
                        placeholder="Tambahkan catatan"
                    />
                    <span v-if="amountForm.errors.notes || amountForm.errors.note" class="text-xs font-normal text-red-600">
                        {{ amountForm.errors.notes || amountForm.errors.note }}
                    </span>
                </label>

                <div class="mt-6 flex items-center justify-between gap-4 border-t border-[#E8EDF4] pt-4 text-sm">
                    <span class="text-[#64748B]">
                        {{ modalAction === 'allocate' ? 'Saldo setelah penambahan' : 'Sisa saldo tabungan' }}
                    </span>
                    <strong class="text-base">{{ $t("Rp") }}{{ formatMoney(balanceAfterAction) }}</strong>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <ButtonPrimary
                        type="button"
                        variant="secondary"
                        class="!border-[#1764E8] !text-[#1764E8]"
                        @click="emit('close-modal')"
                    >
                        Batal
                    </ButtonPrimary>
                    <ButtonPrimary
                        type="submit"
                        :disabled="amountForm.processing || accountsForModal.length === 0"
                    >
                        {{ amountForm.processing
                            ? 'Menyimpan…'
                            : modalAction === 'allocate' ? 'Tambah dana' : 'Catat pengeluaran' }}
                    </ButtonPrimary>
                </div>
            </form>
        </div>
    </div>
</template>
