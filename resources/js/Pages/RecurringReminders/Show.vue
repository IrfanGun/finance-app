<script setup>
import { ref } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import {
    ArrowLeft,
    Check,
    Clock3,
    Pencil,
    Pause,
    Play,
    Square,
} from 'lucide-vue-next';
import MobileBottomNav from '@/Components/MobileBottomNav.vue';
import { index as budgetIndex } from '@/routes/budget-plans';
import { index as savingsIndex } from '@/routes/savings';
import {
    end as endReminder,
    index as remindersIndex,
    pause as pauseReminder,
    resume as resumeReminder,
    skip as skipLog,
    snooze as snoozeLog,
    transaction as reminderTransaction,
} from '@/routes/recurring-reminders';

const props = defineProps({
    reminder: {
        type: Object,
        required: true,
    },
});

const snoozingLog = ref(null);
const snoozeForm = useForm({
    snoozed_until: '',
});

const statusLabel = (status) => ({
    active: 'Aktif',
    paused: 'Dijeda',
    ended: 'Dihentikan',
    pending: 'Menunggu',
    snoozed: 'Ditunda',
    completed: 'Selesai',
    skipped: 'Dilewati',
}[status] ?? status);

const amount = (value, currency = 'IDR') => value === null || value === undefined || value === ''
    ? 'Nominal belum diatur'
    : new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency,
        maximumFractionDigits: 2,
    }).format(Number(value));

const frequencyLabel = (reminder) => {
    const labels = {
        daily: 'hari',
        weekly: 'minggu',
        monthly: 'bulan',
        yearly: 'tahun',
    };

    return reminder.interval === 1
        ? `Setiap ${labels[reminder.frequency]}`
        : `Setiap ${reminder.interval} ${labels[reminder.frequency]}`;
};

const actionUrl = (reminder, log) => {
    if (reminder.purpose === 'transaction') {
        return reminderTransaction.url({ reminder: reminder.id, log: log.id });
    }

    if (reminder.purpose === 'saving_allocation') {
        return savingsIndex.url({
            query: {
                saving: reminder.saving_id,
                reminder_log: log.id,
            },
        });
    }

    return budgetIndex.url({
        query: {
            plan: reminder.budget_plan_id,
            period: log.budget_period_id,
        },
    });
};

const actionLabel = (purpose) => ({
    transaction: 'Catat transaksi',
    budget_allocation: 'Danai budget',
    budget_savings_claim: 'Klaim sisa',
    goal_contribution: 'Sisihkan Dana',
    saving_allocation: 'Alokasikan dana',
}[purpose] ?? 'Buka');

const localDateTime = (date, timezone) => {
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

const editReminder = () => router.visit(remindersIndex.url({
    query: {
        edit: props.reminder.id,
    },
}));

const runReminderAction = (action) => router.post(
    action.url(props.reminder.id),
    {},
    { preserveScroll: true },
);

const runLogAction = (action, log) => router.post(
    action.url({
        reminder: props.reminder.id,
        log: log.id,
    }),
    {},
    { preserveScroll: true },
);

const startSnooze = (log) => {
    snoozingLog.value = log.id;
    snoozeForm.clearErrors();
    snoozeForm.snoozed_until = localDateTime(
        new Date(Date.now() + 60 * 60 * 1000),
        props.reminder.timezone,
    );
};

const submitSnooze = (log) => snoozeForm.post(
    snoozeLog.url({
        reminder: props.reminder.id,
        log: log.id,
    }),
    {
        preserveScroll: true,
        onSuccess: () => {
            snoozingLog.value = null;
        },
    },
);
</script>

<template>
    <Head :title="reminder.title" />

    <div class="mx-auto min-h-screen w-full max-w-lg bg-white pb-28 text-app-heading">
        <header class="flex items-center justify-between px-6 py-5">
            <Link :href="remindersIndex.url()" aria-label="Kembali ke daftar pengingat">
                <ArrowLeft :size="22" class="text-[#2457DA]" />
            </Link>
            <h1 class="max-w-[65%] truncate text-lg font-semibold">Detail pengingat</h1>
            <ButtonPrimary
                v-if="reminder.status !== 'ended' && reminder.purpose === 'transaction'"
                type="button"
                variant="secondary"
                class="!min-h-0 !rounded-lg !p-2 !text-[#2457DA]"
                aria-label="Edit pengingat"
                @click="editReminder"
            >
                <Pencil :size="18" />
            </ButtonPrimary>
            <span v-else class="w-9" />
        </header>

        <main class="space-y-4 px-6 pt-2">
            <section class="space-y-3 rounded-2xl bg-[#F5F7FA] p-5">
                <div class="flex items-start gap-3">
                    <span
                        class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl"
                        :class="reminder.type === 'income'
                            ? 'bg-[#E6F5EE] text-[#16815B]'
                            : 'bg-[#FFF0ED] text-[#B34735]'"
                    >
                        <Check v-if="reminder.type === 'income'" :size="20" />
                        <Square v-else :size="20" />
                    </span>
                    <div class="min-w-0 flex-1">
                        <h2 class="break-words text-xl font-semibold">{{ reminder.title }}</h2>
                        <p class="mt-1 text-sm text-[#637083]">
                            {{ reminder.purpose === 'transaction'
                                ? (reminder.type === 'income' ? 'Pemasukan' : 'Pengeluaran')
                                : actionLabel(reminder.purpose) }} ·
                            {{ frequencyLabel(reminder) }}
                        </p>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3 border-t border-slate-200 pt-4">
                    <div>
                        <p class="text-xs text-[#637083]">Perkiraan nominal</p>
                        <p class="mt-1 font-semibold">
                            {{ amount(reminder.amount, reminder.account?.currency || 'IDR') }}
                        </p>
                    </div>
                    <div>
                        <p class="text-xs text-[#637083]">Status</p>
                        <p class="mt-1 font-semibold">{{ statusLabel(reminder.status) }}</p>
                    </div>
                </div>
            </section>

            <section class="space-y-4 rounded-2xl border border-slate-200 p-4">
                <h2 class="font-semibold">Informasi pengingat</h2>
                <dl class="space-y-4">
                    <div class="flex items-center justify-between gap-4">
                        <dt class="text-sm text-[#637083]">Jadwal berikutnya</dt>
                        <dd class="text-right text-sm font-semibold">
                            {{ reminder.next_reminder_at || 'Tidak ada jadwal' }}
                        </dd>
                    </div>
                    <div class="flex items-center justify-between gap-4">
                        <dt class="text-sm text-[#637083]">Akun bawaan</dt>
                        <dd class="text-right text-sm font-semibold">
                            {{ reminder.account?.name || 'Pilih saat mencatat' }}
                        </dd>
                    </div>
                    <div class="flex items-center justify-between gap-4">
                        <dt class="text-sm text-[#637083]">Kategori</dt>
                        <dd class="text-right text-sm font-semibold">
                            {{ reminder.category?.name || 'Tanpa kategori' }}
                        </dd>
                    </div>
                    <div class="flex items-center justify-between gap-4">
                        <dt class="text-sm text-[#637083]">Mulai</dt>
                        <dd class="text-right text-sm font-semibold">{{ reminder.starts_at }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-4">
                        <dt class="text-sm text-[#637083]">Batas akhir</dt>
                        <dd class="text-right text-sm font-semibold">
                            {{ reminder.ends_at || 'Tidak dibatasi' }}
                        </dd>
                    </div>
                    <div v-if="reminder.notes" class="space-y-1 border-t border-slate-100 pt-3">
                        <dt class="text-xs text-[#637083]">Catatan</dt>
                        <dd class="whitespace-pre-wrap text-sm">{{ reminder.notes }}</dd>
                    </div>
                </dl>
            </section>

            <section
                v-if="reminder.status !== 'ended'"
                class="flex flex-wrap gap-2"
            >
                <ButtonPrimary
                    v-if="reminder.status === 'active'"
                    type="button"
                    variant="secondary"
                    class="!flex !min-h-0 items-center gap-1.5 !rounded-lg !px-3 !py-2 !text-sm"
                    @click="runReminderAction(pauseReminder)"
                >
                    <Pause :size="15" /> Jeda
                </ButtonPrimary>
                <ButtonPrimary
                    v-else-if="reminder.status === 'paused'"
                    type="button"
                    variant="secondary"
                    class="!flex !min-h-0 items-center gap-1.5 !rounded-lg !px-3 !py-2 !text-sm"
                    @click="runReminderAction(resumeReminder)"
                >
                    <Play :size="15" /> Lanjutkan
                </ButtonPrimary>
                <ButtonPrimary
                    type="button"
                    variant="secondary"
                    class="!min-h-0 !rounded-lg !border-[#F1C9C2] !px-3 !py-2 !text-sm !text-[#A63C2C]"
                    @click="runReminderAction(endReminder)"
                >
                    Hentikan
                </ButtonPrimary>
            </section>

            <section class="space-y-3 rounded-2xl border border-slate-200 p-4">
                <h2 class="font-semibold">Riwayat pengingat</h2>
                <p v-if="!reminder.logs.length" class="text-sm text-[#8792A2]">
                    Belum ada periode jatuh tempo.
                </p>
                <article
                    v-for="log in reminder.logs"
                    :key="log.id"
                    class="space-y-3 border-t border-slate-100 pt-3 first:border-0 first:pt-0"
                >
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <div>
                            <p class="text-sm font-semibold">
                                {{ log.scheduled_at }}
                                <span class="font-normal text-[#637083]">· {{ statusLabel(log.status) }}</span>
                            </p>
                            <p v-if="log.snoozed_until" class="mt-1 text-xs text-[#637083]">
                                Diingatkan lagi {{ log.snoozed_until }}
                            </p>
                        </div>
                        <div
                            v-if="['pending', 'snoozed'].includes(log.status)"
                            class="flex flex-wrap gap-2"
                        >
                            <Link
                                :href="actionUrl(reminder, log)"
                                class="rounded-lg bg-[#2457DA] px-3 py-2 text-xs font-semibold text-white"
                            >
                                {{ actionLabel(reminder.purpose) }}
                            </Link>
                            <ButtonPrimary
                                type="button"
                                variant="secondary"
                                class="!flex !min-h-0 items-center !rounded-lg !px-3 !py-2 !text-xs"
                                @click="startSnooze(log)"
                            >
                                <Clock3 :size="14" class="mr-1" /> Ingatkan nanti
                            </ButtonPrimary>
                            <ButtonPrimary
                                type="button"
                                variant="secondary"
                                class="!min-h-0 !rounded-lg !px-3 !py-2 !text-xs"
                                @click="runLogAction(skipLog, log)"
                            >
                                Lewati
                            </ButtonPrimary>
                        </div>
                    </div>

                    <form
                        v-if="snoozingLog === log.id"
                        class="flex flex-wrap items-end gap-2"
                        @submit.prevent="submitSnooze(log)"
                    >
                        <label class="text-xs text-[#637083]">
                            Ingatkan pada
                            <input
                                v-model="snoozeForm.snoozed_until"
                                type="datetime-local"
                                required
                                class="mt-1 block rounded-lg border-[#D8DEE8] text-sm"
                            />
                        </label>
                        <ButtonPrimary
                            type="submit"
                            :disabled="snoozeForm.processing"
                            class="!min-h-0 !rounded-lg !px-3 !py-2 !text-xs"
                        >
                            Simpan
                        </ButtonPrimary>
                        <ButtonPrimary
                            type="button"
                            variant="secondary"
                            class="!min-h-0 !rounded-lg !px-3 !py-2 !text-xs"
                            @click="snoozingLog = null"
                        >
                            Batal
                        </ButtonPrimary>
                        <p v-if="snoozeForm.errors.snoozed_until" class="w-full text-xs text-[#B34735]">
                            {{ snoozeForm.errors.snoozed_until }}
                        </p>
                    </form>
                </article>
            </section>
        </main>

        <MobileBottomNav />
    </div>
</template>
