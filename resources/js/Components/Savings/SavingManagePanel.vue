<script setup>
import { Link } from '@inertiajs/vue3';
import { ArrowDownUp, ArrowRight, SlidersHorizontal } from 'lucide-vue-next';
import { dashboard } from '@/routes';

const props = defineProps({
    saving: { type: Object, required: true },
    accounts: { type: Array, default: () => [] },
    form: { type: Object, required: true },
    allocation: { type: Object, required: true },
    release: { type: Object, required: true },
    reminder: { type: Object, required: true },
    formatMoney: { type: Function, required: true },
    actionError: { type: String, default: '' },
    manageSection: { type: String, default: null },
    transactionMode: { type: String, default: 'allocate' },
    settingsMode: { type: String, default: 'edit' },
});

const emit = defineEmits([
    'update:manageSection',
    'update:transactionMode',
    'update:settingsMode',
    'allocate',
    'release',
    'update',
    'reminder',
    'archive',
]);

const accountsForSaving = () => props.accounts.filter(
    (account) => account.currency === props.saving.currency,
);

const openSection = (section) => emit('update:manageSection', section);
</script>

<template>
    <section class="flex flex-col gap-3 rounded-2xl bg-white p-4 shadow-sm sm:p-5">
        <div class="flex flex-col gap-3">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <h2 class="font-semibold">{{ $t("Manage") }} {{ saving.name }}</h2>
                <Link
                    :href="dashboard.url({ query: { add: 1, saving_id: saving.id } })"
                    class="text-sm font-semibold text-[#2457DA]"
                >{{ $t("Catat belanja dari savings") }}</Link>
            </div>
            <div v-if="!manageSection" class="flex flex-col gap-3" aria-label="Bagian kelola savings">
                <ButtonPrimary
                    type="button"
                    variant="secondary"
                    class="group relative !flex min-h-28 w-full !flex-row items-center justify-between gap-4 overflow-hidden rounded-2xl border-0 !bg-gradient-to-br !from-[#2457DA] !to-[#123A9A] p-4 text-left !text-white shadow-lg transition duration-200 hover:-translate-y-0.5 hover:shadow-xl sm:min-h-32 sm:p-5"
                    @click="openSection('transactions')"
                >
                    <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-[#C9DCFF] text-[#123A9A] shadow-lg shadow-[#0B245C]/30 ring-1 ring-white/40">
                        <ArrowDownUp :size="24" />
                    </span>
                    <span class="flex min-w-0 flex-1 flex-col gap-1">
                        <span class="text-base font-bold sm:text-lg">{{ $t("Transactions") }}</span>
                        <span class="text-xs font-medium leading-snug text-white/80 sm:text-sm"> {{ $t("Set aside funds or release to available funds") }} </span>
                    </span>
                    <ArrowRight :size="20" class="shrink-0 transition-transform group-hover:translate-x-1" />
                </ButtonPrimary>
                <ButtonPrimary
                    type="button"
                    variant="secondary"
                    class="group relative !flex min-h-28 w-full !flex-row items-center justify-between gap-4 overflow-hidden rounded-2xl border-0 !bg-gradient-to-br !from-[#59C7F5] !to-[#2676E8] p-4 text-left !text-white shadow-lg transition duration-200 hover:-translate-y-0.5 hover:shadow-xl sm:min-h-32 sm:p-5"
                    @click="openSection('settings')"
                >
                    <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-[#1746A2] text-white shadow-lg shadow-[#123A9A]/35 ring-1 ring-white/25">
                        <SlidersHorizontal :size="24" />
                    </span>
                    <span class="flex min-w-0 flex-1 flex-col gap-1">
                        <span class="text-base font-bold sm:text-lg">{{ $t("Pengaturan") }}</span>
                        <span class="text-xs font-medium leading-snug text-white/85 sm:text-sm"> {{ $t("Ubah kantong, buat pengingat, atau arsipkan") }} </span>
                    </span>
                    <ArrowRight :size="20" class="shrink-0 transition-transform group-hover:translate-x-1" />
                </ButtonPrimary>
            </div>
            <ButtonPrimary
                v-else
                type="button"
                variant="secondary"
                class="self-start"
                @click="openSection(null)"
            >{{ $t("Kembali ke menu") }}</ButtonPrimary>
        </div>

        <div v-if="manageSection === 'transactions'" class="flex flex-col gap-3">
            <fieldset class="flex flex-col gap-2">
                <legend class="text-sm font-semibold">{{ $t("Choose a transaction") }}</legend>
                <div class="grid gap-2 sm:grid-cols-2">
                    <label class="flex min-h-12 cursor-pointer items-center gap-3 rounded-xl border border-[#DCE3EE] px-4 py-3 text-sm font-medium">
                        <input
                            :checked="transactionMode === 'allocate'"
                            type="radio"
                            name="transaction_mode"
                            value="allocate"
                            class="accent-[#2457DA]"
                            @change="emit('update:transactionMode', 'allocate')"
                        />
                        {{ saving.goal_plan ? 'Setoran goal' : 'Alokasi langsung' }}
                    </label>
                    <label class="flex min-h-12 cursor-pointer items-center gap-3 rounded-xl border border-[#DCE3EE] px-4 py-3 text-sm font-medium">
                        <input
                            :checked="transactionMode === 'release'"
                            type="radio"
                            name="transaction_mode"
                            value="release"
                            class="accent-[#2457DA]"
                            @change="emit('update:transactionMode', 'release')"
                        /> {{ $t("Lepas ke uang bebas") }} </label>
                </div>
            </fieldset>
            <form
                v-if="transactionMode === 'allocate'"
                class="flex flex-col gap-3 rounded-xl border border-[#E9EDF3] p-4"
                @submit.prevent="emit('allocate')"
            >
                <h3 class="text-sm font-semibold">
                    {{ saving.goal_plan ? 'Setoran goal' : 'Alokasi langsung' }}
                </h3>
                <p class="rounded-lg bg-[#F3F6FB] p-3 text-sm text-[#637083]">
                    {{ saving.goal_plan
                        ? 'Setoran goal memindahkan dana dari akun sumber ke kantong ini dan mencatatnya sebagai setoran pada periode yang dipilih.'
                        : 'Alokasi langsung memindahkan dana dari akun sumber ke kantong savings. Uang bebas di akun berkurang, tetapi dana tetap menjadi bagian dari saldo keuanganmu.' }}
                </p>
                <label v-if="saving.goal_plan" class="text-sm">{{ $t("Periode setoran") }} <select v-model="allocation.budget_period_id" class="field" required>
                        <option value="">{{ $t("Pilih periode") }}</option>
                        <option
                            v-for="period in saving.goal_plan.periods.filter((item) => item.status !== 'closed')"
                            :key="period.id"
                            :value="period.id"
                        >
                            {{ period.starts_on }} – {{ period.ends_on }} · rencana Rp {{ formatMoney(period.planned_amount) }}
                        </option>
                    </select>
                </label>
                <label class="text-sm">{{ $t("Akun sumber") }} <select v-model="allocation.account_id" class="field" required>
                        <option v-for="account in accountsForSaving()" :key="account.id" :value="account.id">
                            {{ account.name }}
                        </option>
                    </select>
                </label>
                <label class="text-sm">{{ $t("Amount") }} <AmountInput v-model="allocation.amount" class="field" required />
                </label>
                <p v-for="(error, key) in allocation.errors" :key="key" class="text-sm text-red-600">
                    {{ error }}
                </p>
                <ButtonPrimary type="submit" :disabled="allocation.processing">{{ $t("Sisihkan Dana") }}</ButtonPrimary>
            </form>
            <form
                v-else
                class="flex flex-col gap-3 rounded-xl border border-[#E9EDF3] p-4"
                @submit.prevent="emit('release')"
            >
                <h3 class="text-sm font-semibold">{{ $t("Lepas ke uang bebas") }}</h3>
                <p class="rounded-lg bg-[#F3F6FB] p-3 text-sm text-[#637083]">
                    Mengembalikan dana dari kantong savings ke uang bebas pada akun yang dipilih. Total saldomu tidak berubah.
                </p>
                <label class="text-sm">{{ $t("Akun lokasi dana") }} <select v-model="release.account_id" class="field" required>
                        <option v-for="account in accountsForSaving()" :key="account.id" :value="account.id">
                            {{ account.name }}
                        </option>
                    </select>
                </label>
                <label class="text-sm">{{ $t("Amount") }} <AmountInput v-model="release.amount" class="field" required />
                </label>
                <p v-for="(error, key) in release.errors" :key="key" class="text-sm text-red-600">
                    {{ error }}
                </p>
                <ButtonPrimary type="submit" variant="secondary" :disabled="release.processing"> {{ $t("Lepas dana") }} </ButtonPrimary>
            </form>
        </div>

        <div v-if="manageSection === 'settings'" class="flex flex-col gap-3">
            <fieldset class="flex flex-col gap-2">
                <legend class="text-sm font-semibold">{{ $t("Pilih pengaturan") }}</legend>
                <div class="grid gap-2 sm:grid-cols-3">
                    <label v-if="!saving.goal_plan" class="flex min-h-12 cursor-pointer items-center gap-3 rounded-xl border border-[#DCE3EE] px-4 py-3 text-sm font-medium">
                        <input
                            :checked="settingsMode === 'edit'"
                            type="radio"
                            name="settings_mode"
                            value="edit"
                            class="accent-[#2457DA]"
                            @change="emit('update:settingsMode', 'edit')"
                        /> {{ $t("Ubah kantong") }} </label>
                    <label v-if="!saving.goal_plan" class="flex min-h-12 cursor-pointer items-center gap-3 rounded-xl border border-[#DCE3EE] px-4 py-3 text-sm font-medium">
                        <input
                            :checked="settingsMode === 'reminder'"
                            type="radio"
                            name="settings_mode"
                            value="reminder"
                            class="accent-[#2457DA]"
                            @change="emit('update:settingsMode', 'reminder')"
                        /> {{ $t("Pengingat alokasi") }} </label>
                    <label class="flex min-h-12 cursor-pointer items-center gap-3 rounded-xl border border-[#DCE3EE] px-4 py-3 text-sm font-medium">
                        <input
                            :checked="settingsMode === 'archive'"
                            type="radio"
                            name="settings_mode"
                            value="archive"
                            class="accent-[#2457DA]"
                            @change="emit('update:settingsMode', 'archive')"
                        /> {{ $t("Archive pocket") }} </label>
                </div>
            </fieldset>
            <form
                v-if="settingsMode === 'edit' && !saving.goal_plan"
                class="flex flex-col gap-3 rounded-xl border border-[#E9EDF3] p-4"
                @submit.prevent="emit('update')"
            >
                <h3 class="text-sm font-semibold">{{ $t("Ubah kantong") }}</h3>
                <p class="rounded-lg bg-[#F3F6FB] p-3 text-sm text-[#637083]"> {{ $t("Perbarui nama atau target kantong. Perubahan target tidak memindahkan dana.") }} </p>
                <label class="text-sm">{{ $t("Name") }} <input v-model="form.name" class="field" required />
                </label>
                <label class="text-sm">{{ $t("Target amount") }} <AmountInput v-model="form.target_amount" class="field" />
                </label>
                <label class="text-sm">{{ $t("Target date") }} <input v-model="form.target_date" class="field" type="date" />
                </label>
                <ButtonPrimary type="submit" variant="secondary">{{ $t("Save changes") }}</ButtonPrimary>
            </form>
            <form
                v-else-if="settingsMode === 'reminder' && !saving.goal_plan"
                class="flex flex-col gap-3 rounded-xl border border-[#E9EDF3] p-4"
                @submit.prevent="emit('reminder')"
            >
                <h3 class="text-sm font-semibold">{{ $t("Pengingat alokasi") }}</h3>
                <p class="rounded-lg bg-[#F3F6FB] p-3 text-sm text-[#637083]"> {{ $t("Buat pengingat berkala sebagai jadwal untuk menyisihkan dana ke kantong ini.") }} </p>
                <label class="text-sm">{{ $t("Nominal perkiraan (opsional)") }} <AmountInput v-model="reminder.amount" class="field" />
                </label>
                <label class="text-sm">{{ $t("Mulai pada") }} <input v-model="reminder.starts_at" class="field" type="datetime-local" required />
                </label>
                <label class="text-sm">{{ $t("Interval bulan") }} <input v-model="reminder.interval" class="field" type="number" min="1" required />
                </label>
                <p v-for="(error, key) in reminder.errors" :key="key" class="text-sm text-red-600">
                    {{ error }}
                </p>
                <ButtonPrimary type="submit" variant="secondary" :disabled="reminder.processing"> {{ $t("Create a reminder") }} </ButtonPrimary>
            </form>
            <div v-else class="flex flex-col gap-3 rounded-xl border border-[#E9EDF3] p-4">
                <h3 class="text-sm font-semibold">{{ $t("Archive pocket") }}</h3>
                <p class="rounded-lg bg-[#F3F6FB] p-3 text-sm text-[#637083]"> {{ $t("Kantong yang diarsipkan tidak lagi digunakan untuk alokasi baru.") }} </p>
                <ButtonPrimary type="button" variant="secondary" @click="emit('archive')"> {{ $t("Archive pocket") }} </ButtonPrimary>
                <p v-if="actionError" class="text-sm text-red-600">{{ actionError }}</p>
            </div>
        </div>
    </section>
</template>

<style scoped>
.field {
    @apply mt-1 w-full rounded-xl border border-[#DCE3EE] bg-white px-3 py-2 text-sm;
}
</style>
