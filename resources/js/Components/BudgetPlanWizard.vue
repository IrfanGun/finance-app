<script setup>
import { computed } from 'vue';
import {
    ArrowLeft,
    Bell,
    CalendarDays,
    Check,
    ChevronRight,
    Info,
    Plus,
    Repeat,
    ShoppingBag,
    Target,
} from 'lucide-vue-next';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    form: { type: Object, required: true },
    activeStep: { type: Number, required: true },
    steps: { type: Array, required: true },
    editing: { type: Boolean, default: false },
    accounts: { type: Array, default: () => [] },
    categories: { type: Array, default: () => [] },
    savings: { type: Array, default: () => [] },
    icons: { type: Object, default: () => ({}) },
    colors: { type: Object, default: () => ({}) },
    planIcon: { type: Function, required: true },
    planIconStyle: { type: Function, required: true },
    formatMoney: { type: Function, required: true },
    formatCurrency: { type: Function, required: true },
    formatDate: { type: Function, required: true },
    displayAccountCurrency: { type: Function, required: true },
    accountChanged: { type: Function, required: true },
    nextStep: { type: Function, required: true },
    backStep: { type: Function, required: true },
    changeStep: { type: Function, required: true },
    addItem: { type: Function, required: true },
    savingsUrl: { type: String, required: true },
});
defineEmits(['submit']);

const availableCategories = computed(() => props.categories.filter(
    (category) => category.type === 'expense',
));
const availableSavings = computed(() => props.savings.filter(
    (saving) => saving.currency === props.form.currency
        && (!saving.goal_plan || Number(saving.id) === Number(props.form.saving_id)),
));
const selectedAccount = computed(() => props.accounts.find(
    (account) => Number(account.id) === Number(props.form.financial_account_id),
) ?? null);
const selectedCategory = computed(() => availableCategories.value.find(
    (category) => Number(category.id) === Number(props.form.category_id),
) ?? null);
const selectedSaving = computed(() => props.savings.find(
    (saving) => Number(saving.id) === Number(props.form.saving_id),
) ?? null);
</script>

<template>
    <div class="flex flex-1 flex-col gap-4">
        <header class="flex items-center gap-3 px-1 py-1 sm:px-2">
            <button type="button" class="flex h-10 w-10 shrink-0 items-center justify-center text-[#2457DA]" aria-label="Kembali" @click="backStep">
                <ArrowLeft :size="22" />
            </button>
            <div class="min-w-0 flex-1">
                <p class="text-xs font-medium text-[#637083]">Budget Plan</p>
                <h1 class="truncate text-xl font-semibold text-[#152338]">
                    {{ editing ? 'Ubah rencana' : (activeStep === 1 ? 'Jenis rencana' : steps[activeStep - 1]) }}
                </h1>
            </div>
            <span class="shrink-0 text-sm font-medium text-[#637083]">Langkah {{ activeStep }} dari {{ steps.length }}</span>
        </header>
        <div class="relative grid grid-cols-3 px-1" role="list" aria-label="Progres pembuatan rencana">
            <span
                class="absolute left-[16.6667%] top-[10px] h-0.5 w-[33.3333%] rounded-full"
                :class="activeStep > 1 ? 'bg-[#16815B]' : 'bg-[#DDE3EC]'"
                aria-hidden="true"
            />
            <span
                class="absolute left-1/2 top-[10px] h-0.5 w-[33.3333%] rounded-full"
                :class="activeStep > 2 ? 'bg-[#16815B]' : 'bg-[#DDE3EC]'"
                aria-hidden="true"
            />
            <div
                v-for="(step, index) in steps"
                :key="step"
                class="relative z-10 flex min-w-0 flex-col items-center gap-1.5 text-center text-xs sm:text-sm"
                :class="activeStep === index + 1
                    ? 'font-semibold text-[#2457DA]'
                    : (activeStep > index + 1 ? 'font-semibold text-[#16815B]' : 'text-[#758196]')"
                role="listitem"
                :aria-current="activeStep === index + 1 ? 'step' : undefined"
            >
                <span
                    class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full border bg-[#F4F7FC] text-[11px]"
                    :class="activeStep > index + 1
                        ? 'border-[#16815B] bg-[#16815B] text-white'
                        : (activeStep === index + 1 ? 'border-[#2457DA] text-[#2457DA]' : 'border-[#C9D1DE] text-[#758196]')"
                >
                    <Check v-if="activeStep > index + 1" :size="16" :stroke-width="3" />
                    <span v-else>{{ index + 1 }}</span>
                </span>
                <span class="leading-tight">{{ step }}</span>
            </div>
        </div>

        <form
            class="flex min-h-[calc(100vh-210px)] flex-1 flex-col overflow-hidden rounded-[24px] border border-[#E7EAF0] bg-white px-5 pb-5 pt-5 shadow-[0_8px_24px_rgba(41,82,160,0.06)] sm:px-6"
            @submit.prevent="$emit('submit')"
        >
            <p class="mb-5 text-xs font-medium text-[#637083]">
                Langkah {{ activeStep }} dari {{ steps.length }} · {{ activeStep === 1 ? 'Jenis rencana' : steps[activeStep - 1] }}
            </p>
            <div class="flex-1 overflow-y-auto">
            <section v-if="activeStep === 1" class="flex flex-col gap-5">
                <div>
                    <h2 class="text-[26px] font-bold tracking-tight text-[#152338]">Detail rencana</h2>
                    <p class="mt-1 text-sm text-[#758196]">Pilih apa yang ingin kamu rencanakan.</p>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <button
                        v-for="option in [
                            { value: 'spending', title: 'Anggaran', detail: 'Batas pengeluaran', icon: ShoppingBag },
                            { value: 'goal', title: 'Tujuan', detail: 'Target dana', icon: Target },
                        ]"
                        :key="option.value"
                        type="button"
                        class="relative flex min-h-[132px] flex-col items-start justify-end overflow-hidden rounded-[20px] border-2 p-4 text-left transition"
                        :class="form.type === option.value
                            ? 'border-[#2457DA] bg-[#EAF1FF] text-[#2457DA] shadow-[0_8px_20px_rgba(36,87,218,0.12)]'
                            : option.value === 'goal' ? 'border-transparent bg-[#FFF0E4] text-[#B65F2B]' : 'border-transparent bg-[#EEF2F8] text-[#52627E]'"
                        :disabled="editing"
                        :aria-pressed="form.type === option.value"
                        @click="form.type = option.value"
                    >
                        <span class="absolute -right-5 -top-8 h-28 w-28 rounded-full border-[12px] border-white/35" />
                        <span class="mb-3 flex h-11 w-11 items-center justify-center rounded-2xl bg-white/70"><component :is="option.icon" :size="23" /></span>
                        <span class="text-base font-bold text-[#152338]">{{ option.title }}</span>
                        <span class="mt-0.5 text-xs text-[#637083]">{{ option.detail }}</span>
                        <Check v-if="form.type === option.value" :size="20" class="absolute right-3 top-3" />
                    </button>
                </div>
                <label class="flex flex-col gap-2 text-sm font-medium text-[#314158]">
                    Nama rencana
                    <input v-model="form.name" class="field" required maxlength="255" placeholder="Contoh: Belanja bulanan" />
                </label>
                <p v-if="form.errors.name" class="-mt-3 text-sm text-red-600">{{ form.errors.name }}</p>

                <template v-if="form.type === 'spending'">
                    <label class="flex flex-col gap-2 text-sm font-medium text-[#314158]">
                        Anggaran per periode
                        <span class="flex min-h-[62px] items-center gap-2 rounded-2xl border border-[#E2E7EF] px-4 focus-within:border-[#2457DA]">
                            <span class="text-sm text-[#758196]">{{ form.currency }}</span>
                            <input v-model="form.amount" class="w-full border-0 bg-transparent p-0 text-xl font-bold text-[#152338] outline-none ring-0 placeholder:text-[#A0A9B8] focus:ring-0" type="number" min="0.01" step="0.01" required placeholder="1.500.000" />
                        </span>
                    </label>
                    <p v-if="form.errors.amount" class="-mt-3 text-sm text-red-600">{{ form.errors.amount }}</p>
                    <label class="flex flex-col gap-2 text-sm font-medium text-[#314158]">
                        Akun sumber
                        <select v-model="form.financial_account_id" class="field" required @change="accountChanged">
                            <option value="">Pilih akun</option>
                            <option v-for="account in accounts" :key="account.id" :value="account.id">{{ account.name }} · {{ displayAccountCurrency(account) }}</option>
                        </select>
                    </label>
                    <p v-if="form.errors.financial_account_id" class="-mt-3 text-sm text-red-600">{{ form.errors.financial_account_id }}</p>
                    <label class="flex flex-col gap-2 text-sm font-medium text-[#314158]">
                        Kategori
                        <select v-model="form.category_id" class="field">
                            <option value="">Pilih kategori (opsional)</option>
                            <option v-for="category in availableCategories" :key="category.id" :value="category.id">{{ category.name }}</option>
                        </select>
                    </label>
                </template>
                <template v-else>
                    <label class="flex flex-col gap-2 text-sm font-medium text-[#314158]">Target dana
                        <input v-model="form.target_amount" class="field" type="number" min="0.01" step="0.01" required placeholder="Contoh: 6.000.000" />
                    </label>
                    <p v-if="form.errors.target_amount" class="-mt-3 text-sm text-red-600">{{ form.errors.target_amount }}</p>
                    <label class="flex flex-col gap-2 text-sm font-medium text-[#314158]">Savings tujuan
                        <select v-model="form.saving_id" class="field" required>
                            <option value="">Pilih savings</option>
                            <option v-for="saving in availableSavings" :key="saving.id" :value="saving.id">{{ saving.name }}</option>
                        </select>
                    </label>
                    <p v-if="form.errors.saving_id" class="-mt-3 text-sm text-red-600">{{ form.errors.saving_id }}</p>
                    <Link :href="savingsUrl" class="text-sm font-medium text-[#2457DA]">Belum ada savings? Buat di halaman Savings</Link>
                    <label class="flex items-start gap-2 rounded-2xl bg-[#F5F7FA] p-3 text-sm text-[#52627E]">
                        <input v-model="form.confirm_existing_funds" class="mt-0.5 rounded border-[#CBD5E1] text-[#2457DA] focus:ring-[#2457DA]" type="checkbox" />
                        Seluruh dana yang sudah ada di savings ini untuk tujuan tersebut.
                    </label>
                    <label class="flex flex-col gap-2 text-sm font-medium text-[#314158]">Setoran pilihan per tanggal (opsional)
                        <input v-model="form.contribution_amount" class="field" type="number" min="0.01" step="0.01" placeholder="Nominal setoran" />
                    </label>
                    <label class="flex flex-col gap-2 text-sm font-medium text-[#314158]">Akun sumber
                        <select v-model="form.financial_account_id" class="field" required @change="accountChanged">
                            <option value="">Pilih akun</option>
                            <option v-for="account in accounts" :key="account.id" :value="account.id">{{ account.name }} · {{ displayAccountCurrency(account) }}</option>
                        </select>
                    </label>
                    <p v-if="form.errors.financial_account_id" class="-mt-3 text-sm text-red-600">{{ form.errors.financial_account_id }}</p>
                    <div class="flex flex-col gap-3 rounded-2xl border border-[#E7EAF0] p-4">
                        <div class="flex items-center justify-between gap-3">
                            <h3 class="text-sm font-semibold text-[#314158]">Rincian biaya</h3>
                            <ButtonPrimary type="button" variant="secondary" @click="addItem"><Plus :size="15" /> Tambah item</ButtonPrimary>
                        </div>
                        <div v-for="(item, index) in form.items" :key="index" class="grid grid-cols-1 gap-2 sm:grid-cols-[1fr_130px_auto]">
                            <input v-model="item.name" class="field" placeholder="Nama item" required />
                            <input v-model="item.amount" class="field" type="number" min="0.01" step="0.01" required placeholder="Nominal" />
                            <ButtonPrimary type="button" variant="secondary" @click="form.items.splice(index, 1)">Hapus</ButtonPrimary>
                        </div>
                        <p v-if="form.errors.items" class="text-sm text-red-600">{{ form.errors.items }}</p>
                    </div>
                </template>

                <details class="group border-t border-[#E7EAF0] pt-4">
                    <summary class="flex cursor-pointer list-none items-center justify-between text-sm font-medium text-[#637083]">
                        <span class="flex items-center gap-2"><ChevronRight :size="17" class="transition group-open:rotate-90" /> Ikon & warna</span><span class="text-xs">Opsional</span>
                    </summary>
                    <div class="mt-4 flex flex-col gap-4">
                        <fieldset class="flex flex-col gap-2">
                            <legend class="text-xs font-semibold text-[#637083]">Pilih ikon</legend>
                            <div class="grid grid-cols-7 gap-2">
                                <ButtonPrimary v-for="([icon, label]) in Object.entries(icons)" :key="icon" type="button" variant="secondary" class="!h-10 !min-h-10 !w-full !rounded-xl !p-2" :class="form.icon === icon ? '!border-[#2457DA] !bg-[#EEF3FF] !text-[#2457DA]' : '!border-transparent !bg-[#F5F7FA] !text-[#637083]'" :aria-label="`Pilih ikon ${label}`" :aria-pressed="form.icon === icon" @click="form.icon = icon"><component :is="planIcon(icon)" :size="19" /></ButtonPrimary>
                            </div>
                        </fieldset>
                        <fieldset class="flex flex-col gap-2">
                            <legend class="text-xs font-semibold text-[#637083]">Pilih warna</legend>
                            <div class="flex gap-2">
                                <ButtonPrimary v-for="([color, label]) in Object.entries(colors)" :key="color" type="button" variant="secondary" class="!h-10 !min-h-10 !w-10 !rounded-full !p-1" :class="form.color === color ? '!border-[#2457DA]' : '!border-transparent'" :aria-label="`Pilih warna ${label}`" :aria-pressed="form.color === color" @click="form.color = color"><span class="block h-6 w-6 rounded-full" :style="planIconStyle(color)" /></ButtonPrimary>
                            </div>
                        </fieldset>
                    </div>
                </details>
            </section>

            <section v-else-if="activeStep === 2" class="flex flex-col gap-5">
                <div>
                    <h2 class="text-[26px] font-bold tracking-tight text-[#152338]">Jadwal</h2>
                    <p class="mt-1 text-sm text-[#758196]">Tentukan kapan rencana ini dimulai dan berulang.</p>
                </div>
                <div class="overflow-hidden rounded-[22px] bg-[#F4F5F8]">
                    <label class="flex items-center justify-between gap-4 border-b border-white px-4 py-4 text-sm font-medium text-[#314158]">
                        <span class="flex items-center gap-3"><CalendarDays :size="19" class="text-[#758196]" /> Mulai</span>
                        <input v-model="form.starts_on" class="max-w-[165px] rounded-xl border-0 bg-[#2457DA] px-3 py-2 text-right text-sm font-semibold text-white focus:ring-2 focus:ring-[#2457DA]/30" type="date" required />
                    </label>
                    <label v-if="form.type === 'goal'" class="flex items-center justify-between gap-4 border-b border-white px-4 py-4 text-sm font-medium text-[#314158]">
                        <span class="flex items-center gap-3"><Target :size="19" class="text-[#758196]" /> Tenggat tujuan</span>
                        <input v-model="form.target_date" class="max-w-[165px] rounded-xl border-0 bg-white px-3 py-2 text-right text-sm text-[#314158]" type="date" required />
                    </label>
                    <label class="flex items-center justify-between gap-4 border-b border-white px-4 py-4 text-sm font-medium text-[#314158]">
                        <span class="flex items-center gap-3"><Repeat :size="19" class="text-[#758196]" /> Ulangi</span>
                        <select v-model="form.is_recurring" class="max-w-[150px] rounded-xl border-0 bg-white px-3 py-2 text-sm text-[#314158]"><option :value="true">Bulanan</option><option :value="false">Satu kali</option></select>
                    </label>
                    <label v-if="form.is_recurring" class="flex items-center justify-between gap-4 border-b border-white px-4 py-4 text-sm font-medium text-[#314158]">
                        <span class="flex items-center gap-3"><CalendarDays :size="19" class="text-[#758196]" /> Setiap</span>
                        <select v-model.number="form.interval" class="max-w-[150px] rounded-xl border-0 bg-white px-3 py-2 text-sm text-[#314158]"><option :value="1">1 bulan</option><option :value="2">2 bulan</option><option :value="3">3 bulan</option><option :value="6">6 bulan</option><option :value="12">12 bulan</option></select>
                    </label>
                    <label v-if="form.type === 'spending' && !form.is_recurring" class="flex items-center justify-between gap-4 px-4 py-4 text-sm font-medium text-[#314158]">
                        <span class="flex items-center gap-3"><CalendarDays :size="19" class="text-[#758196]" /> Tanggal akhir</span>
                        <input v-model="form.ends_on" class="max-w-[165px] rounded-xl border-0 bg-white px-3 py-2 text-right text-sm text-[#314158]" type="date" required />
                    </label>
                </div>
                <p v-if="form.errors.starts_on || form.errors.target_date || form.errors.ends_on" class="text-sm text-red-600">{{ form.errors.starts_on || form.errors.target_date || form.errors.ends_on }}</p>
                <label class="flex items-center justify-between gap-4 rounded-2xl bg-[#F4F5F8] px-4 py-4 text-sm font-medium text-[#314158]">
                    <span class="flex items-center gap-3"><Bell :size="19" class="text-[#758196]" /> Pengingat</span>
                    <select v-model="form.remind" class="max-w-[160px] rounded-xl border-0 bg-white px-3 py-2 text-sm text-[#314158]"><option :value="false">Tidak ada</option><option :value="true">1 hari sebelumnya</option></select>
                </label>
                <label v-if="form.type === 'spending' && form.remind" class="flex flex-col gap-2 text-sm font-medium text-[#314158]">Tindakan pengingat
                    <select v-model="form.reminder_purpose" class="field"><option value="budget_savings_claim">Klaim sisa di akhir periode</option><option value="budget_allocation">Danai di awal periode</option></select>
                </label>
                <label class="flex flex-col gap-2 rounded-[22px] bg-[#F4F5F8] p-4 text-sm font-medium text-[#314158]">Catatan <span class="font-normal text-[#758196]">(opsional)</span>
                    <textarea v-model="form.notes" class="min-h-28 resize-y border-0 bg-transparent p-0 text-sm placeholder:text-[#98A2B3] focus:ring-0" placeholder="Tulis catatan..." />
                </label>
            </section>

            <section v-else class="flex flex-col gap-6">
                <div>
                    <h2 class="text-[26px] font-bold tracking-tight text-[#152338]">Ringkasan</h2>
                    <p class="mt-1 text-sm text-[#758196]">Periksa kembali rencana sebelum dibuat.</p>
                </div>
                <div class="flex items-center gap-4 rounded-[22px] bg-[#F4F5F8] p-4">
                    <span class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl text-white" :style="planIconStyle(form.color)"><component :is="planIcon(form.icon)" :size="25" /></span>
                    <span class="min-w-0"><strong class="block truncate text-lg font-bold text-[#152338]">{{ form.name || 'Rencana baru' }}</strong><span class="block text-sm text-[#758196]">{{ form.type === 'goal' ? 'Rencana tujuan' : 'Anggaran belanja' }}</span></span>
                </div>
                <section class="flex flex-col gap-4 border-b border-[#E2E7EF] pb-5">
                    <div class="flex items-center justify-between text-sm"><h3 class="font-semibold text-[#314158]">Rencana</h3><button type="button" class="font-semibold text-[#2457DA]" @click="changeStep(1)">Ubah</button></div>
                    <dl class="flex flex-col gap-3 text-sm">
                        <div class="flex justify-between gap-4"><dt class="text-[#758196]">{{ form.type === 'goal' ? 'Target' : 'Anggaran' }}</dt><dd class="text-right font-medium text-[#152338]">{{ formatCurrency(form.type === 'goal' ? form.target_amount : form.amount, form.currency) }}</dd></div>
                        <div v-if="selectedAccount" class="flex justify-between gap-4"><dt class="text-[#758196]">Akun</dt><dd class="text-right font-medium text-[#152338]">{{ selectedAccount.name }} · {{ selectedAccount.currency || 'IDR' }}</dd></div>
                        <div v-if="selectedCategory" class="flex justify-between gap-4"><dt class="text-[#758196]">Kategori</dt><dd class="text-right font-medium text-[#152338]">{{ selectedCategory.name }}</dd></div>
                        <div v-if="selectedSaving" class="flex justify-between gap-4"><dt class="text-[#758196]">Savings tujuan</dt><dd class="text-right font-medium text-[#152338]">{{ selectedSaving.name }}</dd></div>
                        <div v-if="form.type === 'goal' && form.contribution_amount" class="flex justify-between gap-4"><dt class="text-[#758196]">Setoran pilihan</dt><dd class="text-right font-medium text-[#152338]">{{ formatCurrency(form.contribution_amount, form.currency) }}</dd></div>
                        <div v-if="form.type === 'goal' && form.items.length" class="flex justify-between gap-4"><dt class="text-[#758196]">Rincian biaya</dt><dd class="text-right font-medium text-[#152338]">{{ form.items.length }} item</dd></div>
                    </dl>
                </section>
                <section class="flex flex-col gap-4 border-b border-[#E2E7EF] pb-5">
                    <div class="flex items-center justify-between text-sm"><h3 class="font-semibold text-[#314158]">Jadwal</h3><button type="button" class="font-semibold text-[#2457DA]" @click="changeStep(2)">Ubah</button></div>
                    <dl class="flex flex-col gap-3 text-sm">
                        <div class="flex justify-between gap-4"><dt class="text-[#758196]">Mulai</dt><dd class="text-right font-medium text-[#152338]">{{ formatDate(form.starts_on) }}</dd></div>
                        <div v-if="form.type === 'goal'" class="flex justify-between gap-4"><dt class="text-[#758196]">Tenggat</dt><dd class="text-right font-medium text-[#152338]">{{ formatDate(form.target_date) }}</dd></div>
                        <div class="flex justify-between gap-4"><dt class="text-[#758196]">Pengulangan</dt><dd class="text-right font-medium text-[#152338]">{{ form.is_recurring ? `Setiap ${form.interval} bulan` : 'Satu kali' }}</dd></div>
                        <div class="flex justify-between gap-4"><dt class="text-[#758196]">Pengingat</dt><dd class="text-right font-medium text-[#152338]">{{ form.remind ? '1 hari sebelumnya' : 'Tidak ada' }}</dd></div>
                    </dl>
                </section>
                <div class="flex items-center gap-3 rounded-2xl bg-[#EEF3FF] p-4 text-sm text-[#314158]"><Info :size="20" class="shrink-0 text-[#2457DA]" />Saldo akun tidak berubah.</div>
                <p v-if="form.notes" class="whitespace-pre-line text-sm text-[#758196]">{{ form.notes }}</p>
            </section>
            <p v-for="(error, key) in form.errors" :key="key" class="mt-3 text-sm text-red-600">{{ error }}</p>
        </div>

        <footer class="pt-4">
            <div class="flex gap-3">
                <ButtonPrimary
                    v-if="activeStep > 1"
                    type="button"
                    variant="secondary"
                    @click="backStep"
                >Kembali</ButtonPrimary>
                <ButtonPrimary
                    v-if="activeStep < steps.length"
                    type="button"
                    class="flex-1 justify-center"
                    @click="nextStep"
                >{{ activeStep === 1 ? 'Lanjut' : 'Lihat ringkasan' }}</ButtonPrimary>
                <ButtonPrimary
                    v-else
                    type="submit"
                    class="flex-1 justify-center"
                    :disabled="form.processing"
                >
                    {{ form.processing ? 'Menyimpan...' : (editing ? 'Simpan perubahan' : 'Buat rencana') }}
                </ButtonPrimary>
            </div>
        </footer>
        </form>
    </div>
</template>

<style scoped>
.field {
    @apply min-h-[52px] w-full rounded-2xl border border-[#E2E7EF] bg-white px-4 py-3 text-sm text-[#152338] transition focus:border-[#2457DA] focus:ring-2 focus:ring-[#2457DA]/15;
}
</style>
