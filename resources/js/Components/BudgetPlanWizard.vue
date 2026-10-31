<script setup>
import { computed, ref, watch } from 'vue';
import {
    ArrowLeft,
    Bell,
    CalendarDays,
    Check,
    ChevronRight,
    Info,
    Plus,
    Palette,
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
    cmsMode: { type: Boolean, default: false },
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
    budgetPlansIndexUrl: { type: String, required: true },
});
defineEmits(['submit']);

const customColorValue = ref(
    /^#[\da-f]{6}$/i.test(props.form.color)
        ? props.form.color
        : '#2457da',
);

watch(() => props.form.color, (color) => {
    if (/^#[\da-f]{6}$/i.test(color)) {
        customColorValue.value = color;
    }
});

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
    <div :class="cmsMode ? 'mx-auto flex w-full max-w-7xl flex-col gap-5 text-[#152338]' : 'flex flex-1 flex-col gap-4'">
        <template v-if="cmsMode">
            <Link :href="budgetPlansIndexUrl" class="inline-flex w-fit items-center gap-2 px-1 pt-1 text-sm font-medium text-[#1764F5]">
                <ArrowLeft :size="19" />
                Semua plan
            </Link>
            <h1 class="px-1 text-3xl font-bold tracking-tight text-[#111B39] sm:px-2 sm:text-[32px]">
                {{ editing ? 'Ubah budget plan' : 'Buat budget plan' }}
            </h1>
        </template>
        <header v-else class="flex items-center gap-3 px-1 py-1 sm:px-2">
            <button type="button" class="flex h-10 w-10 shrink-0 items-center justify-center text-[#2457DA]" aria-label="Kembali" @click="backStep">
                <ArrowLeft :size="22" />
            </button>
            <div class="min-w-0 flex-1">
                <p class="text-xs font-medium text-[#637083]">{{ $t("Budget Plan") }}</p>
                <h1 class="truncate text-xl font-semibold text-[#152338]">
                    {{ editing ? 'Ubah rencana' : (activeStep === 1 ? 'Jenis rencana' : steps[activeStep - 1]) }}
                </h1>
            </div>
            <span class="shrink-0 text-sm font-medium text-[#637083]">{{ $t("Step") }} {{ activeStep }} {{ $t("dari") }} {{ steps.length }}</span>
        </header>
        <div :class="cmsMode ? 'relative mx-auto grid w-full max-w-3xl grid-cols-3 px-1 py-1' : 'relative grid grid-cols-3 px-1'" role="list" aria-label="Progres pembuatan rencana">
            <span
                class="absolute left-[16.6667%] top-[15px] h-0.5 w-[33.3333%] rounded-full"
                :class="activeStep > 1 ? 'bg-[#16815B]' : 'bg-[#DDE3EC]'"
                aria-hidden="true"
            />
            <span
                class="absolute left-1/2 top-[15px] h-0.5 w-[33.3333%] rounded-full"
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
                    class="flex h-[30px] w-[30px] shrink-0 items-center justify-center rounded-full border bg-white text-sm"
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
            :class="cmsMode
                ? 'grid grid-cols-1 items-start gap-4 lg:grid-cols-[minmax(0,1.55fr)_minmax(320px,0.95fr)]'
                : 'flex min-h-[calc(100vh-210px)] flex-1 flex-col overflow-hidden rounded-[24px] border border-[#E7EAF0] bg-white px-5 pb-5 pt-5 shadow-[0_8px_24px_rgba(41,82,160,0.06)] sm:px-6'"
            @submit.prevent="$emit('submit')"
        >
            <div :class="cmsMode ? 'flex min-h-[560px] flex-col overflow-hidden rounded-2xl border border-[#E2E8F2] bg-white px-5 pb-5 pt-5 shadow-[0_4px_18px_rgba(41,82,160,0.04)] sm:px-6' : 'contents'">
            <p class="mb-5 text-xs font-medium text-[#637083]"> {{ $t("Step") }} {{ activeStep }} {{ $t("dari") }} {{ steps.length }} · {{ activeStep === 1 ? 'Jenis rencana' : steps[activeStep - 1] }}
            </p>
            <div class="flex-1 overflow-y-auto">
            <section v-if="activeStep === 1" class="flex flex-col gap-5">
                <div>
                    <h2 class="text-[26px] font-bold tracking-tight text-[#152338]">{{ $t("Detail rencana") }}</h2>
                    <p class="mt-1 text-sm text-[#758196]">{{ $t("Pilih apa yang ingin kamu rencanakan.") }}</p>
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
                <label class="flex flex-col gap-2 text-sm font-medium text-[#314158]"> {{ $t("Plan name") }} <input v-model="form.name" class="field" required maxlength="255" placeholder="Contoh: Belanja bulanan" />
                </label>
                <p v-if="form.errors.name" class="-mt-3 text-sm text-red-600">{{ form.errors.name }}</p>

                <template v-if="form.type === 'spending'">
                    <label class="flex flex-col gap-2 text-sm font-medium text-[#314158]"> {{ $t("Anggaran per periode") }} <span class="flex min-h-[62px] items-center gap-2 rounded-2xl border border-[#E2E7EF] px-4 focus-within:border-[#2457DA]">
                            <span class="text-sm text-[#758196]">{{ form.currency }}</span>
                            <AmountInput v-model="form.amount" class="w-full border-0 bg-transparent p-0 text-xl font-bold text-[#152338] outline-none ring-0 placeholder:text-[#A0A9B8] focus:ring-0" required placeholder="1.500.000" />
                        </span>
                    </label>
                    <p v-if="form.errors.amount" class="-mt-3 text-sm text-red-600">{{ form.errors.amount }}</p>
                    <label class="flex flex-col gap-2 text-sm font-medium text-[#314158]"> {{ $t("Akun sumber") }} <select v-model="form.financial_account_id" class="field" required @change="accountChanged">
                            <option value="">{{ $t("Select an account") }}</option>
                            <option v-for="account in accounts" :key="account.id" :value="account.id">{{ account.name }} · {{ displayAccountCurrency(account) }}</option>
                        </select>
                    </label>
                    <p v-if="form.errors.financial_account_id" class="-mt-3 text-sm text-red-600">{{ form.errors.financial_account_id }}</p>
                    <label class="flex flex-col gap-2 text-sm font-medium text-[#314158]"> {{ $t("Category") }} <select v-model="form.category_id" class="field">
                            <option value="">{{ $t("Pilih kategori (opsional)") }}</option>
                            <option v-for="category in availableCategories" :key="category.id" :value="category.id">{{ category.name }}</option>
                        </select>
                    </label>
                </template>
                <template v-else>
                    <label class="flex flex-col gap-2 text-sm font-medium text-[#314158]">{{ $t("Target dana") }} <AmountInput v-model="form.target_amount" class="field" required placeholder="Contoh: 6.000.000" />
                    </label>
                    <p v-if="form.errors.target_amount" class="-mt-3 text-sm text-red-600">{{ form.errors.target_amount }}</p>
                    <label class="flex flex-col gap-2 text-sm font-medium text-[#314158]">{{ $t("Savings tujuan") }} <select v-model="form.saving_id" class="field" required>
                            <option value="">{{ $t("Pilih savings") }}</option>
                            <option v-for="saving in availableSavings" :key="saving.id" :value="saving.id">{{ saving.name }}</option>
                        </select>
                    </label>
                    <p v-if="form.errors.saving_id" class="-mt-3 text-sm text-red-600">{{ form.errors.saving_id }}</p>
                    <Link :href="savingsUrl" class="text-sm font-medium text-[#2457DA]">{{ $t("Belum ada savings? Buat di halaman Savings") }}</Link>
                    <label class="flex items-start gap-2 rounded-2xl bg-[#F5F7FA] p-3 text-sm text-[#52627E]">
                        <input v-model="form.confirm_existing_funds" class="mt-0.5 rounded border-[#CBD5E1] text-[#2457DA] focus:ring-[#2457DA]" type="checkbox" /> {{ $t("Seluruh dana yang sudah ada di savings ini untuk tujuan tersebut.") }} </label>
                    <label class="flex flex-col gap-2 text-sm font-medium text-[#314158]">{{ $t("Setoran pilihan per tanggal (opsional)") }} <AmountInput v-model="form.contribution_amount" class="field" placeholder="Nominal setoran" />
                    </label>
                    <label class="flex flex-col gap-2 text-sm font-medium text-[#314158]">{{ $t("Akun sumber") }} <select v-model="form.financial_account_id" class="field" required @change="accountChanged">
                            <option value="">{{ $t("Select an account") }}</option>
                            <option v-for="account in accounts" :key="account.id" :value="account.id">{{ account.name }} · {{ displayAccountCurrency(account) }}</option>
                        </select>
                    </label>
                    <p v-if="form.errors.financial_account_id" class="-mt-3 text-sm text-red-600">{{ form.errors.financial_account_id }}</p>
                    <div class="flex flex-col gap-3 rounded-2xl border border-[#E7EAF0] p-4">
                        <div class="flex items-center justify-between gap-3">
                            <h3 class="text-sm font-semibold text-[#314158]">{{ $t("Rincian biaya") }}</h3>
                            <ButtonPrimary type="button" variant="secondary" @click="addItem"><Plus :size="15" /> {{ $t("Add an item") }}</ButtonPrimary>
                        </div>
                        <div v-for="(item, index) in form.items" :key="index" class="grid grid-cols-1 gap-2 sm:grid-cols-[1fr_130px_auto]">
                            <input v-model="item.name" class="field" placeholder="Nama item" required />
                            <AmountInput v-model="item.amount" class="field" required placeholder="Nominal" />
                            <ButtonPrimary type="button" variant="secondary" @click="form.items.splice(index, 1)">{{ $t("Delete") }}</ButtonPrimary>
                        </div>
                        <p v-if="form.errors.items" class="text-sm text-red-600">{{ form.errors.items }}</p>
                    </div>
                </template>

                <details class="group border-t border-[#E7EAF0] pt-4" :open="cmsMode">
                    <summary class="flex cursor-pointer list-none items-center justify-between text-sm font-medium text-[#637083]">
                        <span class="flex items-center gap-2"><ChevronRight :size="17" class="transition group-open:rotate-90" /> {{ $t("Ikon & warna") }}</span><span class="text-xs">{{ $t("Optional") }}</span>
                    </summary>
                    <div class="mt-4 flex flex-col gap-4">
                        <fieldset class="flex flex-col gap-2">
                            <legend class="text-xs font-semibold text-[#637083]">{{ $t("Choose an icon") }}</legend>
                            <div class="grid grid-cols-7 gap-2">
                                <ButtonPrimary v-for="([icon, label]) in Object.entries(icons)" :key="icon" type="button" variant="secondary" class="!inline-flex !h-10 !min-h-10 !w-full !items-center !justify-center !rounded-xl !p-0" :class="form.icon === icon ? '!border-[#2457DA] !bg-[#EEF3FF] !text-[#2457DA]' : '!border-transparent !bg-[#F5F7FA] !text-[#637083]'" :aria-label="`Pilih ikon ${label}`" :aria-pressed="form.icon === icon" @click="form.icon = icon"><component :is="planIcon(icon)" class="block shrink-0" :size="19" /></ButtonPrimary>
                            </div>
                        </fieldset>
                        <fieldset class="flex flex-col gap-2">
                            <legend class="text-xs font-semibold text-[#637083]">{{ $t("Pilih warna") }}</legend>
                            <div class="flex flex-wrap items-center gap-2">
                                <ButtonPrimary v-for="([color, label]) in Object.entries(colors)" :key="color" type="button" variant="secondary" class="!inline-flex !h-10 !min-h-10 !w-10 !items-center !justify-center !rounded-full !p-0" :class="form.color === color ? '!border-[#2457DA] !ring-2 !ring-[#2457DA]/20 !ring-offset-1' : '!border-transparent'" :aria-label="`Pilih warna ${label}`" :aria-pressed="form.color === color" @click="form.color = color"><span class="block h-6 w-6 shrink-0 rounded-full" :style="planIconStyle(color)" /></ButtonPrimary>
                                <label
                                    class="relative inline-flex h-10 w-10 cursor-pointer items-center justify-center rounded-full border bg-white p-1 transition"
                                    :class="/^#[\da-f]{6}$/i.test(form.color) ? 'border-[#2457DA] ring-2 ring-[#2457DA]/20 ring-offset-1' : 'border-dashed border-[#C9D1DE] hover:border-[#2457DA]'"
                                    title="Pilih warna kustom"
                                >
                                    <span class="flex h-6 w-6 items-center justify-center rounded-full" :style="planIconStyle(customColorValue)">
                                        <Palette :size="13" class="text-white drop-shadow" />
                                    </span>
                                    <input
                                        v-model="customColorValue"
                                        class="absolute inset-0 h-full w-full cursor-pointer opacity-0"
                                        type="color"
                                        aria-label="Pilih warna kustom"
                                        @input="form.color = $event.target.value"
                                    />
                                </label>
                            </div>
                        </fieldset>
                    </div>
                </details>
            </section>

            <section v-else-if="activeStep === 2" class="flex flex-col gap-5">
                <div>
                    <h2 class="text-[26px] font-bold tracking-tight text-[#152338]">{{ $t("Schedule") }}</h2>
                    <p class="mt-1 text-sm text-[#758196]">{{ $t("Tentukan kapan rencana ini dimulai dan berulang.") }}</p>
                </div>
                <div class="overflow-hidden rounded-[22px] bg-[#F4F5F8]">
                    <label class="flex items-center justify-between gap-4 border-b border-white px-4 py-4 text-sm font-medium text-[#314158]">
                        <span class="flex items-center gap-3"><CalendarDays :size="19" class="text-[#758196]" /> {{ $t("Mulai") }}</span>
                        <input v-model="form.starts_on" class="max-w-[165px] rounded-xl border-0 bg-[#2457DA] px-3 py-2 text-right text-sm font-semibold text-white focus:ring-2 focus:ring-[#2457DA]/30" type="date" required />
                    </label>
                    <label v-if="form.type === 'goal'" class="flex items-center justify-between gap-4 border-b border-white px-4 py-4 text-sm font-medium text-[#314158]">
                        <span class="flex items-center gap-3"><Target :size="19" class="text-[#758196]" /> {{ $t("Tenggat tujuan") }}</span>
                        <input v-model="form.target_date" class="max-w-[165px] rounded-xl border-0 bg-white px-3 py-2 text-right text-sm text-[#314158]" type="date" required />
                    </label>
                    <label class="flex items-center justify-between gap-4 border-b border-white px-4 py-4 text-sm font-medium text-[#314158]">
                        <span class="flex items-center gap-3"><Repeat :size="19" class="text-[#758196]" /> {{ $t("Ulangi") }}</span>
                        <select v-model="form.is_recurring" class="max-w-[150px] rounded-xl border-0 bg-white px-3 py-2 text-sm text-[#314158]"><option :value="true">{{ $t("Monthly") }}</option><option :value="false">{{ $t("Satu kali") }}</option></select>
                    </label>
                    <label v-if="form.is_recurring" class="flex items-center justify-between gap-4 border-b border-white px-4 py-4 text-sm font-medium text-[#314158]">
                        <span class="flex items-center gap-3"><CalendarDays :size="19" class="text-[#758196]" /> {{ $t("Setiap") }}</span>
                        <select v-model.number="form.interval" class="max-w-[150px] rounded-xl border-0 bg-white px-3 py-2 text-sm text-[#314158]"><option :value="1">{{ $t("1 bulan") }}</option><option :value="2">{{ $t("2 bulan") }}</option><option :value="3">{{ $t("3 bulan") }}</option><option :value="6">{{ $t("6 bulan") }}</option><option :value="12">{{ $t("12 bulan") }}</option></select>
                    </label>
                    <label v-if="form.type === 'spending' && !form.is_recurring" class="flex items-center justify-between gap-4 px-4 py-4 text-sm font-medium text-[#314158]">
                        <span class="flex items-center gap-3"><CalendarDays :size="19" class="text-[#758196]" /> {{ $t("Tanggal akhir") }}</span>
                        <input v-model="form.ends_on" class="max-w-[165px] rounded-xl border-0 bg-white px-3 py-2 text-right text-sm text-[#314158]" type="date" required />
                    </label>
                </div>
                <p v-if="form.errors.starts_on || form.errors.target_date || form.errors.ends_on" class="text-sm text-red-600">{{ form.errors.starts_on || form.errors.target_date || form.errors.ends_on }}</p>
                <label class="flex items-center justify-between gap-4 rounded-2xl bg-[#F4F5F8] px-4 py-4 text-sm font-medium text-[#314158]">
                    <span class="flex items-center gap-3"><Bell :size="19" class="text-[#758196]" /> {{ $t("Reminder") }}</span>
                    <select v-model="form.remind" class="max-w-[160px] rounded-xl border-0 bg-white px-3 py-2 text-sm text-[#314158]"><option :value="false">{{ $t("Tidak ada") }}</option><option :value="true">{{ $t("1 hari sebelumnya") }}</option></select>
                </label>
                <label v-if="form.type === 'spending' && form.remind" class="flex flex-col gap-2 text-sm font-medium text-[#314158]">{{ $t("Tindakan pengingat") }} <select v-model="form.reminder_purpose" class="field"><option value="budget_savings_claim">{{ $t("Klaim sisa di akhir periode") }}</option><option value="budget_allocation">{{ $t("Danai di awal periode") }}</option></select>
                </label>
                <label class="flex flex-col gap-2 rounded-[22px] bg-[#F4F5F8] p-4 text-sm font-medium text-[#314158]">{{ $t("Note") }} <span class="font-normal text-[#758196]">(opsional)</span>
                    <textarea v-model="form.notes" class="min-h-28 resize-y border-0 bg-transparent p-0 text-sm placeholder:text-[#98A2B3] focus:ring-0" placeholder="Tulis catatan..." />
                </label>
            </section>

            <section v-else class="flex flex-col gap-6">
                <div>
                    <h2 class="text-[26px] font-bold tracking-tight text-[#152338]">{{ $t("Summary") }}</h2>
                    <p class="mt-1 text-sm text-[#758196]">{{ $t("Periksa kembali rencana sebelum dibuat.") }}</p>
                </div>
                <div class="flex items-center gap-4 rounded-[22px] bg-[#F4F5F8] p-4">
                    <span class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl text-white" :style="planIconStyle(form.color)"><component :is="planIcon(form.icon)" :size="25" /></span>
                    <span class="min-w-0"><strong class="block truncate text-lg font-bold text-[#152338]">{{ form.name || 'Rencana baru' }}</strong><span class="block text-sm text-[#758196]">{{ form.type === 'goal' ? 'Rencana tujuan' : 'Anggaran belanja' }}</span></span>
                </div>
                <section class="flex flex-col gap-4 border-b border-[#E2E7EF] pb-5">
                    <div class="flex items-center justify-between text-sm"><h3 class="font-semibold text-[#314158]">{{ $t("Rencana") }}</h3><button type="button" class="font-semibold text-[#2457DA]" @click="changeStep(1)">{{ $t("Edit") }}</button></div>
                    <dl class="flex flex-col gap-3 text-sm">
                        <div class="flex justify-between gap-4"><dt class="text-[#758196]">{{ form.type === 'goal' ? 'Target' : 'Anggaran' }}</dt><dd class="text-right font-medium text-[#152338]">{{ formatCurrency(form.type === 'goal' ? form.target_amount : form.amount, form.currency) }}</dd></div>
                        <div v-if="selectedAccount" class="flex justify-between gap-4"><dt class="text-[#758196]">{{ $t("Account") }}</dt><dd class="text-right font-medium text-[#152338]">{{ selectedAccount.name }} · {{ selectedAccount.currency || 'IDR' }}</dd></div>
                        <div v-if="selectedCategory" class="flex justify-between gap-4"><dt class="text-[#758196]">{{ $t("Category") }}</dt><dd class="text-right font-medium text-[#152338]">{{ selectedCategory.name }}</dd></div>
                        <div v-if="selectedSaving" class="flex justify-between gap-4"><dt class="text-[#758196]">{{ $t("Savings tujuan") }}</dt><dd class="text-right font-medium text-[#152338]">{{ selectedSaving.name }}</dd></div>
                        <div v-if="form.type === 'goal' && form.contribution_amount" class="flex justify-between gap-4"><dt class="text-[#758196]">{{ $t("Setoran pilihan") }}</dt><dd class="text-right font-medium text-[#152338]">{{ formatCurrency(form.contribution_amount, form.currency) }}</dd></div>
                        <div v-if="form.type === 'goal' && form.items.length" class="flex justify-between gap-4"><dt class="text-[#758196]">{{ $t("Rincian biaya") }}</dt><dd class="text-right font-medium text-[#152338]">{{ form.items.length }} item</dd></div>
                    </dl>
                </section>
                <section class="flex flex-col gap-4 border-b border-[#E2E7EF] pb-5">
                    <div class="flex items-center justify-between text-sm"><h3 class="font-semibold text-[#314158]">{{ $t("Schedule") }}</h3><button type="button" class="font-semibold text-[#2457DA]" @click="changeStep(2)">{{ $t("Edit") }}</button></div>
                    <dl class="flex flex-col gap-3 text-sm">
                        <div class="flex justify-between gap-4"><dt class="text-[#758196]">{{ $t("Mulai") }}</dt><dd class="text-right font-medium text-[#152338]">{{ formatDate(form.starts_on) }}</dd></div>
                        <div v-if="form.type === 'goal'" class="flex justify-between gap-4"><dt class="text-[#758196]">{{ $t("Tenggat") }}</dt><dd class="text-right font-medium text-[#152338]">{{ formatDate(form.target_date) }}</dd></div>
                        <div class="flex justify-between gap-4"><dt class="text-[#758196]">{{ $t("Pengulangan") }}</dt><dd class="text-right font-medium text-[#152338]">{{ form.is_recurring ? `Setiap ${form.interval} bulan` : 'Satu kali' }}</dd></div>
                        <div class="flex justify-between gap-4"><dt class="text-[#758196]">{{ $t("Reminder") }}</dt><dd class="text-right font-medium text-[#152338]">{{ form.remind ? '1 hari sebelumnya' : 'Tidak ada' }}</dd></div>
                    </dl>
                </section>
                <div class="flex items-center gap-3 rounded-2xl bg-[#EEF3FF] p-4 text-sm text-[#314158]"><Info :size="20" class="shrink-0 text-[#2457DA]" />{{ $t("Saldo akun tidak berubah.") }}</div>
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
                >{{ $t("Back") }}</ButtonPrimary>
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
            </div>

            <aside
                v-if="cmsMode"
                class="min-h-[420px] rounded-2xl border border-[#E2E8F2] bg-white p-5 shadow-[0_4px_18px_rgba(41,82,160,0.04)] sm:p-6"
            >
                <template v-if="activeStep === 1">
                    <h2 class="text-lg font-bold text-[#152338]">Pratinjau</h2>
                    <p class="mt-1 text-sm text-[#758196]">Berikut tampilan budget plan kamu.</p>
                    <div class="relative isolate mt-5 overflow-hidden rounded-2xl bg-[linear-gradient(135deg,#F4F8FD_0%,#EDF4FF_100%)] p-5">
                        <svg
                            aria-hidden="true"
                            class="pointer-events-none absolute -right-1 top-0 z-0 h-full w-[58%]"
                            viewBox="0 0 240 260"
                            fill="none"
                            preserveAspectRatio="xMidYMid slice"
                        >
                            <path
                                d="M132 -35C122 27 213 16 226 76C239 132 174 151 192 210C201 239 224 258 250 278"
                                stroke="#8FB1FF"
                                stroke-width="30"
                                stroke-linecap="round"
                                opacity=".2"
                            />
                            <g stroke="#78A2FF" stroke-width="1" opacity=".38">
                                <circle cx="174" cy="126" r="43" />
                                <circle cx="174" cy="126" r="51" />
                                <circle cx="174" cy="126" r="59" />
                                <circle cx="174" cy="126" r="67" />
                                <circle cx="174" cy="126" r="75" />
                                <circle cx="225" cy="248" r="53" />
                                <circle cx="225" cy="248" r="61" />
                                <circle cx="225" cy="248" r="69" />
                                <circle cx="225" cy="248" r="77" />
                            </g>
                        </svg>
                        <div class="relative z-10">
                        <span class="flex h-14 w-14 items-center justify-center rounded-2xl text-white shadow-[0_8px_18px_rgba(36,87,218,0.2)]" :style="planIconStyle(form.color)">
                            <component :is="planIcon(form.icon)" :size="26" />
                        </span>
                        <h3 class="mt-3 truncate text-xl font-bold text-[#152338]">{{ form.name || 'Nama rencana' }}</h3>
                        <p class="mt-1 text-sm text-[#758196]">{{ form.type === 'goal' ? 'Rencana tujuan' : 'Anggaran belanja' }}</p>
                        <p class="mt-4 text-3xl font-bold tracking-tight text-[#111B39]">
                            {{ formatCurrency(form.type === 'goal' ? form.target_amount : form.amount, form.currency) }}
                        </p>
                        <p class="mt-1 text-sm text-[#758196]">{{ form.type === 'goal' ? 'target dana' : 'per periode' }}</p>
                        </div>
                    </div>
                    <dl class="mt-5 divide-y divide-[#E8EDF5] border-y border-[#E8EDF5] text-sm">
                        <div class="flex justify-between gap-4 py-3.5">
                            <dt class="text-[#758196]">Akun sumber</dt>
                            <dd class="text-right font-medium text-[#152338]">{{ selectedAccount ? `${selectedAccount.name} · ${displayAccountCurrency(selectedAccount)}` : 'Belum dipilih' }}</dd>
                        </div>
                        <div class="flex justify-between gap-4 py-3.5">
                            <dt class="text-[#758196]">Kategori</dt>
                            <dd class="text-right font-medium text-[#152338]">{{ selectedCategory?.name || 'Belum dipilih' }}</dd>
                        </div>
                    </dl>
                    <div class="mt-5 flex items-start gap-3 rounded-xl bg-[#F1F5FC] p-4 text-sm text-[#64748B]">
                        <Info :size="18" class="mt-0.5 shrink-0 text-[#1764F5]" />
                        <span>Jadwal diatur pada langkah berikutnya.</span>
                    </div>
                </template>
                <template v-else>
                    <h2 class="text-lg font-bold text-[#152338]">{{ activeStep === 2 ? 'Ringkasan sementara' : 'Ringkasan rencana' }}</h2>
                    <p class="mt-1 text-sm text-[#758196]">Periksa detail rencana sebelum melanjutkan.</p>
                    <div class="mt-5 flex items-center gap-4 rounded-2xl bg-[#F4F8FD] p-4">
                        <span class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl text-white" :style="planIconStyle(form.color)">
                            <component :is="planIcon(form.icon)" :size="25" />
                        </span>
                        <div class="min-w-0">
                            <p class="truncate font-bold text-[#152338]">{{ form.name || 'Nama rencana' }}</p>
                            <p class="text-sm text-[#758196]">{{ formatCurrency(form.type === 'goal' ? form.target_amount : form.amount, form.currency) }} {{ form.type === 'goal' ? 'target' : 'per periode' }}</p>
                        </div>
                    </div>
                    <dl class="mt-5 divide-y divide-[#E8EDF5] border-y border-[#E8EDF5] text-sm">
                        <div class="flex justify-between gap-4 py-3.5"><dt class="text-[#758196]">Akun sumber</dt><dd class="text-right font-medium">{{ selectedAccount ? `${selectedAccount.name} · ${displayAccountCurrency(selectedAccount)}` : 'Belum dipilih' }}</dd></div>
                        <div class="flex justify-between gap-4 py-3.5"><dt class="text-[#758196]">Kategori</dt><dd class="text-right font-medium">{{ selectedCategory?.name || 'Belum dipilih' }}</dd></div>
                        <div class="flex justify-between gap-4 py-3.5"><dt class="text-[#758196]">Mulai</dt><dd class="text-right font-medium">{{ formatDate(form.starts_on) }}</dd></div>
                        <div class="flex justify-between gap-4 py-3.5"><dt class="text-[#758196]">Pengulangan</dt><dd class="text-right font-medium">{{ form.is_recurring ? `Setiap ${form.interval} bulan` : 'Satu kali' }}</dd></div>
                        <div class="flex justify-between gap-4 py-3.5"><dt class="text-[#758196]">Pengingat</dt><dd class="text-right font-medium">{{ form.remind ? '1 hari sebelumnya' : 'Tidak ada' }}</dd></div>
                    </dl>
                    <div v-if="activeStep === 3" class="mt-5 flex items-start gap-3 rounded-xl bg-[#F1F5FC] p-4 text-sm text-[#64748B]">
                        <Info :size="18" class="mt-0.5 shrink-0 text-[#1764F5]" />
                        <span>Membuat rencana tidak mengubah saldo akun.</span>
                    </div>
                </template>
            </aside>
        </form>
    </div>
</template>

<style scoped>
.field {
    @apply min-h-[52px] w-full rounded-2xl border border-[#E2E7EF] bg-white px-4 py-3 text-sm text-[#152338] transition focus:border-[#2457DA] focus:ring-2 focus:ring-[#2457DA]/15;
}
</style>
