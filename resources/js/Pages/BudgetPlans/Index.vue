<script setup>
import { computed, ref, watch } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Plus } from 'lucide-vue-next';
import { dashboard } from '@/routes';
import { create as createSavingPage, index as savingsIndex } from '@/routes/savings';
import { reverse as reverseClaim } from '@/routes/budget-claims';
import {
    index as budgetPlansIndex,
    status as statusPlan,
    store as storePlan,
    update as updatePlan,
    create as createPlanPage,
} from '@/routes/budget-plans';
import {
    claim as claimPeriod,
    close as closePeriod,
    fund as fundPeriod,
    release as releasePeriod,
} from '@/routes/budget-periods';
import MobileBottomNav from '@/Components/MobileBottomNav.vue';

const props = defineProps({
    plans: { type: Array, default: () => [] },
    legacyBudgets: { type: Array, default: () => [] },
    accounts: { type: Array, default: () => [] },
    categories: { type: Array, default: () => [] },
    savings: { type: Array, default: () => [] },
    createMode: { type: Boolean, default: false },
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
    <div class="mx-auto min-h-screen w-full max-w-lg bg-[#F7F9FC] pb-28 text-[#152338]">
        <Head :title="showCreate ? (editingId ? 'Ubah Budget Plan' : 'Budget Plan baru') : 'Budget Plan'" />
        <header v-if="!showCreate" class="flex items-center justify-between bg-white px-6 py-5">
            <Link :href="dashboard.url()" class="text-sm font-semibold text-[#2457DA]">Kembali</Link>
            <h1 class="text-lg font-semibold">Budget Plan</h1>
            <Link :href="savingsIndex.url()" class="text-sm font-semibold text-[#2457DA]">Savings</Link>
        </header>

        <main class="flex flex-col gap-5 px-6 pt-6">
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

            <section v-if="!showCreate && fundingNotices.length" class="rounded-2xl bg-[#FFF3DE] p-4 text-sm">
                <h2 class="font-semibold">Budget perlu didanai</h2>
                <Link
                    v-for="notice in fundingNotices"
                    :key="notice.id"
                    :href="notice.data.url"
                    class="mt-2 block text-[#8A5900]"
                >{{ notice.data.title }}</Link>
            </section>
            <section v-if="!showCreate" class="grid grid-cols-2 gap-3">
                <article
                    v-for="(total, currency) in totals"
                    :key="currency"
                    class="col-span-2 rounded-2xl bg-[#EAF0FF] p-4"
                >
                    <p class="text-xs text-[#637083]">Uang bebas {{ currency }}</p>
                    <p class="text-xl font-semibold">Rp {{ formatMoney(total.free) }}</p>
                    <p class="text-xs text-[#637083]">
                        Saldo aktual Rp {{ formatMoney(total.actual) }} ·
                        Budget Rp {{ formatMoney(total.budget) }} ·
                        Savings Rp {{ formatMoney(total.savings) }}
                    </p>
                </article>
            </section>

            <Link
                v-if="!showCreate"
                :href="createPlanPage.url()"
                class="inline-flex min-h-12 items-center justify-center rounded-xl bg-[#2457DA] px-5 py-3 font-semibold text-white shadow-sm"
            >
                <Plus :size="18" class="mr-2" /> Buat Budget Plan
            </Link>

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
                                {{ account.name }} ({{ account.currency }})
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

            <p v-if="!showCreate && !plans.length" class="rounded-2xl bg-white p-5 text-sm text-[#637083]">
                Belum ada budget plan.
            </p>
            <article v-for="plan in plans" v-show="!showCreate" :key="plan.id" class="flex flex-col gap-4 rounded-2xl bg-white p-5 shadow-sm">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-semibold">{{ plan.name }}</h2>
                        <p class="text-xs text-[#637083]">
                            {{ plan.type === 'goal' ? 'Rencana Tujuan' : 'Anggaran Belanja' }} · {{ plan.status }}
                        </p>
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
                <div v-for="period in plan.periods" :key="period.id" class="flex flex-col gap-2 border-t border-[#E9EDF3] pt-3 text-sm">
                    <p class="font-semibold">{{ period.starts_on }} – {{ period.ends_on }}</p>
                    <p class="text-[#637083]">
                        Rencana Rp {{ formatMoney(period.planned_amount) }} ·
                        {{ plan.type === 'goal' ? 'Setoran' : 'Cadangan' }}
                        Rp {{ formatMoney(plan.type === 'goal' ? period.contributed : period.allocated_amount) }}
                    </p>
                    <p v-if="plan.type === 'spending'">Sisa Rp {{ formatMoney(period.remaining) }}</p>
                    <p v-if="plan.type === 'spending'" class="text-[#637083]">
                        Terpakai Rp {{ formatMoney(period.spent) }} ·
                        Dilepas Rp {{ formatMoney(period.released_amount) }}
                    </p>
                    <p v-else>Status setoran: {{ period.status }}</p>
                    <div v-if="period.claims?.length" class="flex flex-col gap-1 text-xs text-[#637083]">
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
                    <div v-if="period.status !== 'closed'" class="flex flex-wrap gap-2">
                        <ButtonPrimary
                            v-if="plan.type === 'spending' && period.status === 'awaiting_funding'"
                            type="button"
                            @click="actOnPeriod(fundPeriod, period)"
                        >Danai</ButtonPrimary>
                        <Link
                            v-if="plan.type === 'spending' && period.status === 'open'"
                            :href="dashboard.url({
                                query: {
                                    add: 1,
                                    budget_period: period.id,
                                    account_id: period.financial_account_id,
                                },
                            })"
                            class="self-center text-sm font-semibold text-[#2457DA]"
                        >Catat belanja</Link>
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
                    <div v-if="activeClaim === period.id" class="flex flex-col gap-2 rounded-xl bg-[#F7F9FC] p-3">
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
            <section v-if="!showCreate && legacyBudgets.length" class="rounded-2xl bg-white p-5 text-sm">
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
        <MobileBottomNav @add="router.visit(dashboard.url({ query: { add: 1 } }))" />
    </div>
</template>

<style scoped>
.field {
    @apply mt-1 w-full rounded-xl border border-[#DCE3EE] bg-white px-3 py-2 text-sm;
}
</style>
