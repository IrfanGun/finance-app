<script setup>
import { computed, onMounted, ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Bell, ChevronRight, Plus } from 'lucide-vue-next';
import { dashboard } from '@/routes';
import {
    show as reminderShow,
    store as storeReminder,
    update as updateReminder,
} from '@/routes/recurring-reminders';
import MobileBottomNav from '@/Components/MobileBottomNav.vue';

const props = defineProps({
    reminders: { type: Array, default: () => [] },
    editReminderId: { type: [Number, String], default: null },
    accounts: { type: Array, default: () => [] },
    categories: { type: Array, default: () => [] },
    notifications: { type: Array, default: () => [] },
});

const creating = ref(false);
const currentStep = ref(1);
const createForm = ref(null);
const editingId = ref(null);
const selectedType = ref('all');
const reminderTypeFilters = [
    { label: 'Semua', value: 'all' },
    { label: 'Pengeluaran', value: 'expense' },
    { label: 'Pemasukan', value: 'income' },
];
const filteredReminders = computed(() => selectedType.value === 'all'
    ? props.reminders
    : props.reminders.filter((reminder) => reminder.type === selectedType.value));
const form = useForm({
    type: 'expense',
    priority: 'medium',
    title: '',
    amount: '',
    financial_account_id: '',
    category_id: '',
    frequency: 'monthly',
    interval: 1,
    starts_at: '',
    ends_at: '',
    timezone: 'Asia/Jakarta',
    notes: '',
});
const availableCategories = computed(() => props.categories.filter((category) => category.type === form.type));
const visibleErrorFields = [
    'type',
    'priority',
    'title',
    'amount',
    'financial_account_id',
    'category_id',
    'notes',
    'frequency',
    'interval',
    'starts_at',
    'ends_at',
];
const unhandledErrors = computed(() => Object.entries(form.errors)
    .filter(([field]) => !visibleErrorFields.includes(field)));

const localDateTime = (date, timezone = 'Asia/Jakarta') => {
    const parts = new Intl.DateTimeFormat('en-CA', {
        timeZone: timezone,
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
        hourCycle: 'h23',
    }).formatToParts(date).reduce((values, part) => {
        values[part.type] = part.value;

        return values;
    }, {});

    return `${parts.year}-${parts.month}-${parts.day}T${parts.hour}:${parts.minute}`;
};

const openCreate = () => {
    editingId.value = null;
    form.reset();
    form.clearErrors();
    form.type = selectedType.value === 'all' ? 'expense' : selectedType.value;
    form.priority = 'medium';
    form.starts_at = localDateTime(new Date(Date.now() + 60 * 60 * 1000));
    form.timezone = 'Asia/Jakarta';
    currentStep.value = 1;
    creating.value = true;
};

const openEdit = (reminder) => {
    editingId.value = reminder.id;
    form.clearErrors();
    form.type = reminder.type;
    form.priority = reminder.priority ?? 'medium';
    form.title = reminder.title;
    form.amount = reminder.amount ?? '';
    form.financial_account_id = reminder.financial_account_id ?? '';
    form.category_id = reminder.category_id ?? '';
    form.frequency = reminder.frequency;
    form.interval = reminder.interval;
    form.starts_at = reminder.starts_at;
    form.ends_at = reminder.ends_at ?? '';
    form.timezone = reminder.timezone;
    form.notes = reminder.notes ?? '';
    currentStep.value = 1;
    creating.value = true;
};

const closeCreate = () => {
    creating.value = false;
    currentStep.value = 1;
};

const backCreateStep = () => {
    if (currentStep.value === 2) {
        currentStep.value = 1;

        return;
    }

    closeCreate();
};

const continueToSchedule = () => {
    if (!createForm.value?.reportValidity()) {
        return;
    }

    currentStep.value = 2;
};

const saveReminder = () => {
    if (!createForm.value?.reportValidity()) {
        return;
    }

    const options = {
        preserveScroll: true,
        onSuccess: () => {
            closeCreate();
        },
        onError: (errors) => {
            const scheduleFields = ['frequency', 'interval', 'starts_at', 'ends_at'];
            const hasScheduleError = scheduleFields.some((field) => field in errors);

            currentStep.value = hasScheduleError ? 2 : 1;
        },
    };

    if (editingId.value) {
        form.patch(updateReminder.url(editingId.value), options);

        return;
    }

    form.post(storeReminder.url(), options);
};

const statusLabel = (status) => ({
    active: 'Aktif',
    paused: 'Dijeda',
    ended: 'Dihentikan',
    pending: 'Menunggu',
    snoozed: 'Ditunda',
    completed: 'Selesai',
    skipped: 'Dilewati',
}[status] ?? status);

const priorityLabels = {
    low: 'Rendah',
    medium: 'Sedang',
    high: 'Tinggi',
};
const priorityClasses = {
    low: 'bg-[#EEF3FF] text-[#2457DA]',
    medium: 'bg-[#FFF7E8] text-[#765723]',
    high: 'bg-[#FFF2F0] text-[#B34735]',
};

const amount = (value, currency = 'IDR') => value === null || value === undefined || value === ''
    ? 'Nominal belum diatur'
    : new Intl.NumberFormat('id-ID', { style: 'currency', currency, maximumFractionDigits: 2 }).format(Number(value));

const frequencyLabel = (reminder) => {
    const labels = { daily: 'hari', weekly: 'minggu', monthly: 'bulan', yearly: 'tahun' };

    return reminder.interval === 1
        ? `Setiap ${labels[reminder.frequency]}`
        : `Setiap ${reminder.interval} ${labels[reminder.frequency]}`;
};

onMounted(() => {
    if (!props.editReminderId) {
        return;
    }

    const reminderToEdit = props.reminders.find(
        (reminder) => String(reminder.id) === String(props.editReminderId),
    );

    if (reminderToEdit) {
        openEdit(reminderToEdit);
    }
});
</script>

<template>
    <Head title="Pengingat Berulang" />
    <div class="mx-auto min-h-screen w-full max-w-lg bg-white pb-28 text-[#152338]">
        <header v-if="!creating" class="space-y-4 bg-white px-6 py-5">
            <div class="flex items-center justify-between gap-3">
                <Link :href="dashboard.url()" class="text-[#2457DA]" aria-label="Kembali ke beranda"><ArrowLeft :size="21" /></Link>
                <div class="min-w-0 flex-1">
                    <h1 class="text-xl font-semibold">Pengingat berulang</h1>
                </div>
                <ButtonPrimary type="button" class="!flex h-11 items-center gap-2 !rounded-xl !px-4 !text-sm" @click="openCreate">
                    <Plus :size="18" /> <span class="hidden sm:inline">Tambah</span>
                </ButtonPrimary>
            </div>
            <div class="flex gap-2" role="group" aria-label="Filter jenis pengingat">
                <ButtonPrimary
                    v-for="filter in reminderTypeFilters"
                    :key="filter.value"
                    type="button"
                    :variant="selectedType === filter.value ? 'primary' : 'secondary'"
                    :aria-pressed="selectedType === filter.value"
                    class="!min-h-0 flex-1 !rounded-full !px-3 !py-2 !text-xs sm:!text-sm"
                    @click="selectedType = filter.value"
                >
                    {{ filter.label }}
                </ButtonPrimary>
            </div>
        </header>

        <main v-if="!creating" class="space-y-3 px-6 pt-5">
            <section v-if="notifications.length" class="rounded-xl bg-white p-4">
                <div class="mb-3 flex items-center gap-2">
                    <Bell :size="17" class="text-[#2457DA]" />
                    <h2 class="text-sm font-semibold">Notifikasi terbaru</h2>
                </div>
                <div class="space-y-2">
                    <Link
                        v-for="notification in notifications"
                        :key="notification.id"
                        :href="notification.data.url"
                        class="flex items-start justify-between gap-3 rounded-lg bg-[#F5F7FA] p-3"
                    >
                        <span class="min-w-0">
                            <span class="block truncate text-sm font-medium">{{ notification.data.title }}</span>
                            <span class="mt-1 block text-xs text-[#637083]">
                                {{ notification.data.kind === 'snooze' ? 'Pengingat ulang' : 'Jatuh tempo' }} · {{ notification.data.scheduled_at }}
                            </span>
                        </span>
                        <span
                            v-if="!notification.read_at"
                            class="mt-1 h-2 w-2 shrink-0 rounded-full bg-[#2457DA]"
                            aria-label="Belum dibaca"
                        />
                    </Link>
                </div>
            </section>

            <section
                v-if="!filteredReminders.length"
                class="rounded-xl bg-[#F5F7FA] px-5 py-10 text-center"
            >
                <Bell :size="28" class="mx-auto text-[#8792A2]" />
                <h2 class="mt-3 font-semibold">
                    {{ reminders.length ? 'Tidak ada pengingat untuk jenis ini' : 'Belum ada pengingat' }}
                </h2>
                <p class="mt-1 text-sm text-[#637083]">
                    {{ reminders.length
                        ? 'Pilih filter lain untuk melihat pengingat yang tersedia.'
                        : 'Buat jadwal pemasukan atau pengeluaran yang berulang.' }}
                </p>
                <ButtonPrimary
                    v-if="!reminders.length"
                    type="button"
                    class="mt-5 !min-h-0 !rounded-xl !px-4 !py-2.5 !text-sm"
                    @click="openCreate"
                >
                    Buat pengingat
                </ButtonPrimary>
            </section>

            <Link
                v-for="reminder in filteredReminders"
                :key="reminder.id"
                :href="reminderShow.url(reminder.id)"
                class="block rounded-xl bg-[#F5F7FA] p-4 transition hover:bg-[#EEF3FF]"
            >
                <div class="flex items-start justify-between gap-4">
                    <div class="min-w-0">
                        <div class="flex items-center gap-2">
                            <h2 class="truncate font-semibold">{{ reminder.title }}</h2>
                            <span
                                class="shrink-0 rounded-full px-2.5 py-1 text-[10px] font-semibold"
                                :class="priorityClasses[reminder.priority] ?? priorityClasses.medium"
                            >
                                {{ priorityLabels[reminder.priority] ?? priorityLabels.medium }}
                            </span>
                        </div>
                        <p class="mt-1 text-xs capitalize text-[#637083]">
                            {{ statusLabel(reminder.status) }} ·
                            {{ reminder.type === 'income' ? 'Pemasukan' : 'Pengeluaran' }} ·
                            {{ frequencyLabel(reminder) }}
                        </p>
                    </div>
                    <ChevronRight :size="18" class="mt-1 shrink-0 text-[#2457DA]" />
                </div>
                <div class="mt-4 grid grid-cols-2 gap-3">
                    <div>
                        <p class="text-xs text-[#637083]">Perkiraan nominal</p>
                        <p class="mt-1 font-semibold">
                            {{ amount(reminder.amount, reminder.account?.currency || 'IDR') }}
                        </p>
                    </div>
                    <div>
                        <p class="text-xs text-[#637083]">Jadwal berikutnya</p>
                        <p class="mt-1 font-medium">
                            {{ reminder.next_reminder_at || 'Tidak ada jadwal' }}
                        </p>
                    </div>
                </div>
            </Link>
        </main>
        <section v-else class="px-6 pb-8 pt-3">
            <header class="flex items-center gap-4">
                <ButtonPrimary
                    type="button"
                    variant="secondary"
                    class="!min-h-0 !rounded-full !border-transparent !bg-transparent !p-2 !text-[#2457DA] !shadow-none hover:!bg-white"
                    :aria-label="currentStep === 2 ? 'Kembali ke detail pengingat' : 'Kembali ke daftar pengingat'"
                    @click="backCreateStep"
                >
                    <ArrowLeft :size="22" />
                </ButtonPrimary>
                <div class="min-w-0 flex-1">
                    <p class="text-xs font-medium text-[#637083]">Pengingat berulang</p>
                    <h1 class="text-xl font-semibold">
                        {{ currentStep === 1 ? (editingId ? 'Edit pengingat' : 'Pengingat baru') : 'Atur jadwal' }}
                    </h1>
                </div>
                <span class="shrink-0 text-sm font-medium text-[#637083]">
                    Langkah {{ currentStep }} dari 2
                </span>
            </header>

            <div class="mt-6 flex gap-2" aria-hidden="true">
                <div
                    class="h-1.5 flex-1 rounded-full"
                    :class="currentStep >= 1 ? 'bg-[#2457DA]' : 'bg-slate-200'"
                ></div>
                <div
                    class="h-1.5 flex-1 rounded-full"
                    :class="currentStep >= 2 ? 'bg-[#2457DA]' : 'bg-slate-200'"
                ></div>
            </div>

            <form
                ref="createForm"
                class="mt-6 space-y-5"
                novalidate
                @submit.prevent="saveReminder"
            >
                <div v-if="currentStep === 1" class="space-y-4">
                    <div>
                        <h2 class="text-lg font-semibold">Detail pengingat</h2>
                        <p class="mt-1 text-sm leading-5 text-[#637083]">
                            Tentukan transaksi yang ingin Anda ingat.
                        </p>
                    </div>

                    <label class="block text-sm font-medium">
                        Jenis transaksi
                        <select
                            v-model="form.type"
                            required
                            class="mt-2 h-11 w-full rounded-lg border border-slate-300 bg-white px-4"
                        >
                            <option value="expense">Pengeluaran</option>
                            <option value="income">Pemasukan</option>
                        </select>
                        <p v-if="form.errors.type" class="mt-1 text-xs text-red-600">
                            {{ form.errors.type }}
                        </p>
                    </label>

                    <label class="block text-sm font-medium">
                        Prioritas
                        <select
                            v-model="form.priority"
                            required
                            class="mt-2 h-11 w-full rounded-lg border border-slate-300 bg-white px-4"
                        >
                            <option value="low">Rendah</option>
                            <option value="medium">Sedang</option>
                            <option value="high">Tinggi</option>
                        </select>
                        <p v-if="form.errors.priority" class="mt-1 text-xs text-red-600">
                            {{ form.errors.priority }}
                        </p>
                    </label>

                    <label class="block text-sm font-medium">
                        Judul
                        <input
                            v-model="form.title"
                            required
                            maxlength="255"
                            class="mt-2 h-11 w-full rounded-lg border border-slate-300 px-4"
                            placeholder="Contoh: Tagihan listrik"
                        />
                        <p v-if="form.errors.title" class="mt-1 text-xs text-red-600">
                            {{ form.errors.title }}
                        </p>
                    </label>

                    <label class="block text-sm font-medium">
                        Nominal perkiraan
                        <input
                            v-model="form.amount"
                            type="number"
                            min="0"
                            step="0.01"
                            class="mt-2 h-11 w-full rounded-lg border border-slate-300 px-4"
                        />
                        <p v-if="form.errors.amount" class="mt-1 text-xs text-red-600">
                            {{ form.errors.amount }}
                        </p>
                    </label>

                    <label class="block text-sm font-medium">
                        Akun bawaan
                        <select
                            v-model="form.financial_account_id"
                            class="mt-2 h-11 w-full rounded-lg border border-slate-300 bg-white px-4"
                        >
                            <option value="">Pilih saat mencatat</option>
                            <option
                                v-for="account in accounts"
                                :key="account.id"
                                :value="account.id"
                            >
                                {{ account.name }} ({{ account.currency || 'IDR' }})
                            </option>
                        </select>
                        <p v-if="form.errors.financial_account_id" class="mt-1 text-xs text-red-600">
                            {{ form.errors.financial_account_id }}
                        </p>
                    </label>

                    <label class="block text-sm font-medium">
                        Kategori bawaan
                        <select
                            v-model="form.category_id"
                            class="mt-2 h-11 w-full rounded-lg border border-slate-300 bg-white px-4"
                        >
                            <option value="">Tanpa kategori</option>
                            <option
                                v-for="category in availableCategories"
                                :key="category.id"
                                :value="category.id"
                            >
                                {{ category.name }}
                            </option>
                        </select>
                        <p v-if="form.errors.category_id" class="mt-1 text-xs text-red-600">
                            {{ form.errors.category_id }}
                        </p>
                    </label>

                    <label class="block text-sm font-medium">
                        Catatan
                        <textarea
                            v-model="form.notes"
                            rows="2"
                            maxlength="1000"
                            class="mt-2 w-full rounded-lg border border-slate-300 px-4 py-3"
                            placeholder="Opsional"
                        />
                        <p v-if="form.errors.notes" class="mt-1 text-xs text-red-600">
                            {{ form.errors.notes }}
                        </p>
                    </label>
                </div>

                <div v-if="currentStep === 2" class="space-y-4">
                    <div>
                        <h2 class="text-lg font-semibold">Jadwal berulang</h2>
                        <p class="mt-1 text-sm leading-5 text-[#637083]">
                            Pilih kapan pengingat mulai dan seberapa sering diulang.
                        </p>
                    </div>

                    <label class="block text-sm font-medium">
                        Frekuensi
                        <select
                            v-model="form.frequency"
                            required
                            class="mt-2 h-11 w-full rounded-lg border border-slate-300 bg-white px-4"
                        >
                            <option value="daily">Harian</option>
                            <option value="weekly">Mingguan</option>
                            <option value="monthly">Bulanan</option>
                            <option value="yearly">Tahunan</option>
                        </select>
                        <p v-if="form.errors.frequency" class="mt-1 text-xs text-red-600">
                            {{ form.errors.frequency }}
                        </p>
                    </label>

                    <label class="block text-sm font-medium">
                        Ulang setiap
                        <input
                            v-model.number="form.interval"
                            type="number"
                            min="1"
                            required
                            class="mt-2 h-11 w-full rounded-lg border border-slate-300 px-4"
                        />
                        <p v-if="form.errors.interval" class="mt-1 text-xs text-red-600">
                            {{ form.errors.interval }}
                        </p>
                    </label>

                    <label class="block text-sm font-medium">
                        Jadwal pertama
                        <input
                            v-model="form.starts_at"
                            type="datetime-local"
                            required
                            class="mt-2 h-11 w-full rounded-lg border border-slate-300 px-4"
                        />
                        <p v-if="form.errors.starts_at" class="mt-1 text-xs text-red-600">
                            {{ form.errors.starts_at }}
                        </p>
                    </label>

                    <label class="block text-sm font-medium">
                        Batas akhir (opsional)
                        <input
                            v-model="form.ends_at"
                            type="datetime-local"
                            :min="form.starts_at"
                            class="mt-2 h-11 w-full rounded-lg border border-slate-300 px-4"
                        />
                        <p v-if="form.errors.ends_at" class="mt-1 text-xs text-red-600">
                            {{ form.errors.ends_at }}
                        </p>
                    </label>

                    <p v-if="editingId" class="rounded-xl bg-[#FFF7E8] p-3 text-xs text-[#765723]">
                        Perubahan jadwal akan berlaku pada periode pengingat yang belum selesai.
                    </p>
                </div>

                <div v-if="unhandledErrors.length" class="space-y-1 text-sm text-[#B34735]">
                    <p v-for="[field, error] in unhandledErrors" :key="field">
                        {{ error }}
                    </p>
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <ButtonPrimary
                        type="button"
                        variant="secondary"
                        class="!rounded-xl !px-4 !py-2.5 !text-sm"
                        @click="backCreateStep"
                    >
                        {{ currentStep === 1 ? 'Batal' : 'Kembali' }}
                    </ButtonPrimary>
                    <ButtonPrimary
                        v-if="currentStep === 1"
                        type="button"
                        class="!flex items-center gap-2 !rounded-xl !px-5 !py-2.5 !text-sm"
                        @click="continueToSchedule"
                    >
                        Lanjut <ArrowLeft class="rotate-180" :size="16" />
                    </ButtonPrimary>
                    <ButtonPrimary
                        v-else
                        type="submit"
                        :disabled="form.processing"
                        class="!rounded-xl !px-5 !py-2.5 !text-sm"
                    >
                        {{ form.processing ? 'Menyimpan...' : 'Simpan pengingat' }}
                    </ButtonPrimary>
                </div>
            </form>
        </section>
        <MobileBottomNav />
    </div>
</template>
