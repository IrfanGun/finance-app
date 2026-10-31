<script setup>
import { computed, ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import { CalendarDays, MoreVertical, ReceiptText } from 'lucide-vue-next';

const props = defineProps({
    plan: { type: Object, required: true },
    savings: { type: Array, default: () => [] },
    planIcon: { type: Function, required: true },
    planIconStyle: { type: Function, required: true },
    planStatusLabel: { type: Function, required: true },
    periodStatusLabel: { type: Function, required: true },
    periodUsagePercentage: { type: Function, required: true },
    periodUsageColor: { type: Function, required: true },
    formatMoney: { type: Function, required: true },
    activeClaim: { type: Number, default: null },
    claimParts: { type: Array, default: () => [] },
    claimTotal: { type: Number, default: 0 },
    releaseAmount: { type: [String, Number], default: '' },
    reverseClaim: { type: Function, required: true },
    fundPeriod: { type: Function, required: true },
    closePeriod: { type: Function, required: true },
    releasePeriod: { type: Function, required: true },
    savingsUrl: { type: Function, required: true },
    savingsIndexUrl: { type: String, required: true },
    budgetPlansIndexUrl: { type: String, required: true },
    formatDate: { type: Function, required: true },
    actionError: { type: String, default: '' },
    cmsMode: { type: Boolean, default: false },
});

const currentPeriod = computed(() => props.plan.periods?.find((period) => period.status === 'open')
    ?? props.plan.periods?.find((period) => period.status === 'awaiting_funding')
    ?? props.plan.periods?.[0]
    ?? null);
const showPlanActions = ref(false);

defineEmits([
    'edit',
    'change-status',
    'period-action',
    'open-transaction',
    'open-claim',
    'close-claim',
    'submit-claim',
    'update:releaseAmount',
]);
</script>

<template>
    <div v-if="cmsMode && plan.type === 'spending'" class="flex flex-col gap-5 text-[#152338]">
        <div class="flex items-center justify-between">
            <Link :href="budgetPlansIndexUrl" class="inline-flex items-center gap-2 text-sm font-semibold text-[#1764F5]">
                <span aria-hidden="true">←</span> {{ $t('Semua plan') }}
            </Link>
            <Link :href="savingsIndexUrl" class="text-sm font-semibold text-[#1764F5]">{{ $t('Tabungan') }}</Link>
        </div>

        <header class="flex flex-wrap items-center justify-between gap-4">
            <div class="flex min-w-0 items-center gap-4">
                <span
                    class="flex h-[76px] w-[76px] shrink-0 items-center justify-center rounded-[22px] text-white"
                    :style="planIconStyle(plan.color)"
                >
                    <component :is="planIcon(plan.icon)" :size="34" />
                </span>
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-3">
                        <h1 class="truncate text-2xl font-bold tracking-tight text-[#111B39] sm:text-[30px]">{{ plan.name }}</h1>
                        <span class="rounded-full bg-[#DCF8E9] px-3 py-1 text-sm font-semibold text-[#12815B]">{{ planStatusLabel(plan.status) }}</span>
                    </div>
                    <p class="mt-1 text-base text-[#64748B]">{{ $t('Anggaran belanja') }}</p>
                </div>
            </div>
            <div class="relative flex items-center gap-2">
                <ButtonPrimary type="button" variant="secondary" class="!border-[#2463F5] !px-6 !text-[#1764F5]" @click="$emit('edit', plan)">
                    {{ $t('Ubah') }}
                </ButtonPrimary>
                <ButtonPrimary
                    type="button"
                    variant="secondary"
                    class="!min-h-12 !px-3"
                    :aria-label="$t('Aksi lainnya')"
                    :aria-expanded="showPlanActions"
                    @click="showPlanActions = !showPlanActions"
                >
                    <MoreVertical :size="20" />
                </ButtonPrimary>
                <div v-if="showPlanActions" class="absolute right-0 top-14 z-20 flex min-w-44 flex-col rounded-xl border border-[#E4EAF4] bg-white p-2 shadow-lg">
                    <ButtonPrimary v-if="plan.status === 'active'" type="button" variant="secondary" class="!justify-start !border-0 !px-3" @click="$emit('change-status', plan, 'paused'); showPlanActions = false">{{ $t('Jeda') }}</ButtonPrimary>
                    <ButtonPrimary v-if="plan.status === 'paused'" type="button" variant="secondary" class="!justify-start !border-0 !px-3" @click="$emit('change-status', plan, 'active'); showPlanActions = false">{{ $t('Continue') }}</ButtonPrimary>
                    <ButtonPrimary v-if="['active', 'paused'].includes(plan.status)" type="button" variant="secondary" class="!justify-start !border-0 !px-3" @click="$emit('change-status', plan, 'ended'); showPlanActions = false">{{ $t('Hentikan') }}</ButtonPrimary>
                </div>
            </div>
        </header>

        <section v-if="currentPeriod" class="rounded-2xl border border-[#E4EAF4] bg-white p-5 sm:p-6">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-[#E7ECF4] pb-4">
                <p class="flex items-center gap-3 text-base font-semibold text-[#17243A]">
                    <CalendarDays :size="22" class="text-[#64748B]" />
                    {{ formatDate(currentPeriod.starts_on) }} – {{ formatDate(currentPeriod.ends_on) }}
                </p>
                <span class="rounded-full bg-[#EEF4FF] px-3 py-1.5 text-sm font-medium text-[#1764F5]">{{ periodStatusLabel(currentPeriod.status) }}</span>
            </div>

            <div class="grid gap-5 py-5 md:grid-cols-3 md:divide-x md:divide-[#E7ECF4]">
                <div class="md:pr-6">
                    <p class="text-sm text-[#718096]">{{ $t('Sisa anggaran') }}</p>
                    <p class="mt-1 text-3xl font-bold tracking-tight text-[#111B39] sm:text-[38px]">{{ $t('Rp') }} {{ formatMoney(currentPeriod.remaining) }}</p>
                </div>
                <div class="md:px-6">
                    <p class="text-sm text-[#718096]">{{ $t('Dialokasikan') }}</p>
                    <p class="mt-2 text-2xl font-bold text-[#111B39]">{{ $t('Rp') }} {{ formatMoney(currentPeriod.allocated_amount) }}</p>
                </div>
                <div class="md:pl-6">
                    <p class="text-sm text-[#718096]">{{ $t('Terpakai') }}</p>
                    <p class="mt-2 text-2xl font-bold text-[#111B39]">{{ $t('Rp') }} {{ formatMoney(currentPeriod.spent) }}</p>
                </div>
            </div>

            <div class="flex items-center gap-4">
                <div class="h-3 flex-1 overflow-hidden rounded-full bg-[#E8EDF5]" role="progressbar" :aria-label="`Penggunaan anggaran ${plan.name}`" aria-valuemin="0" aria-valuemax="100" :aria-valuenow="Math.round(periodUsagePercentage(currentPeriod))">
                    <div class="h-full rounded-full transition-[width]" :class="periodUsageColor(currentPeriod)" :style="{ width: `${periodUsagePercentage(currentPeriod)}%` }" />
                </div>
                <span class="shrink-0 text-sm text-[#64748B]">{{ Math.round(periodUsagePercentage(currentPeriod)) }}% {{ $t('terpakai') }}</span>
            </div>
            <div class="mt-3 flex flex-wrap items-center justify-between gap-4">
                <p class="text-sm text-[#718096]">{{ $t('Rencana Rp') }} {{ formatMoney(currentPeriod.planned_amount) }} · {{ $t('Dilepas Rp') }} {{ formatMoney(currentPeriod.released_amount) }}</p>
                <div class="flex flex-wrap gap-2">
                    <ButtonPrimary v-if="currentPeriod.status === 'open'" type="button" class="!px-6" @click="$emit('open-transaction', plan, currentPeriod)">
                        <span class="mr-2 text-lg">＋</span>{{ $t('Catat belanja') }}
                    </ButtonPrimary>
                    <ButtonPrimary v-if="Number(currentPeriod.remaining) > 0" type="button" variant="secondary" class="!border-[#2463F5] !px-5 !text-[#1764F5]" @click="$emit('open-claim', currentPeriod)">
                        {{ $t('Klaim sisa') }}
                    </ButtonPrimary>
                </div>
            </div>
            <div v-if="currentPeriod.claims?.length" class="mt-4 flex flex-col gap-2 border-t border-[#E7ECF4] pt-4 text-sm text-[#64748B]">
                <div v-for="claim in currentPeriod.claims" :key="claim.id" class="flex items-center justify-between gap-2">
                    <span>{{ claim.pocket?.name }} · Rp {{ formatMoney(claim.amount) }} {{ claim.reversed_at ? '(dibalik)' : '' }}</span>
                    <ButtonPrimary v-if="!claim.reversed_at && currentPeriod.status === 'open'" type="button" variant="secondary" class="!min-h-9 !px-3 !py-1 !text-xs" @click="$emit('period-action', reverseClaim, claim)">{{ $t('Balik klaim') }}</ButtonPrimary>
                </div>
            </div>
        </section>

        <div
            v-if="activeClaim === currentPeriod?.id"
            class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-[#152338]/45 px-4 py-6"
            @click.self="$emit('close-claim')"
        >
            <section
                role="dialog"
                aria-modal="true"
                aria-labelledby="claim-modal-title"
                class="my-auto max-h-[min(90vh,760px)] w-full max-w-[620px] overflow-y-auto rounded-2xl bg-white px-6 py-5 text-[#152338] shadow-2xl sm:px-7 sm:py-6"
            >
                <header class="mb-4 flex items-center justify-between gap-4">
                    <h2 id="claim-modal-title" class="text-[23px] font-bold tracking-tight">{{ $t('Klaim sisa') }}</h2>
                    <ButtonPrimary
                        type="button"
                        variant="secondary"
                        class="!h-10 !min-h-10 !w-10 !rounded-xl !p-0 !text-xl"
                        :aria-label="$t('Tutup modal klaim')"
                        @click="$emit('close-claim')"
                    >×</ButtonPrimary>
                </header>

                <div class="mb-5 flex items-center justify-between gap-4 rounded-xl bg-[#F1F5FF] px-4 py-3.5">
                    <div class="flex min-w-0 items-center gap-3">
                        <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-[#2463F5] text-white">
                            <component :is="planIcon(plan.icon)" :size="22" />
                        </span>
                        <div class="min-w-0">
                            <p class="truncate font-semibold text-[#17243A]">{{ plan.name }}</p>
                            <p class="mt-1 text-xs text-[#687A9B]">
                                {{ formatDate(currentPeriod.starts_on) }} – {{ formatDate(currentPeriod.ends_on) }}
                            </p>
                        </div>
                    </div>
                    <div class="shrink-0 text-right">
                        <p class="text-xs text-[#687A9B]">{{ $t('Sisa anggaran') }}</p>
                        <strong class="mt-1 block text-base text-[#1764F5]">Rp {{ formatMoney(currentPeriod.remaining) }}</strong>
                    </div>
                </div>

                <div class="flex flex-col gap-4">
                    <div>
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <h3 class="text-sm font-semibold text-[#17243A]">{{ $t('Bagi sisa ke tabungan') }}</h3>
                                <p class="mt-1 text-xs text-[#718096]">{{ $t('Pilih tabungan dan nominal yang ingin diklaim.') }}</p>
                            </div>
                            <ButtonPrimary
                                type="button"
                                variant="secondary"
                                class="!min-h-9 shrink-0 !px-3 !py-1.5 !text-xs"
                                @click="claimParts.push({ saving_id: '', amount: '' })"
                            >{{ $t('Tambah tujuan') }}</ButtonPrimary>
                        </div>
                        <div class="mt-3 flex flex-col gap-3">
                            <div
                                v-for="(part, index) in claimParts"
                                :key="index"
                                class="grid grid-cols-1 gap-3 sm:grid-cols-[minmax(0,1fr)_180px]"
                            >
                                <select v-model="part.saving_id" class="field" required>
                                    <option value="">{{ $t('Pilih tabungan') }}</option>
                                    <option
                                        v-for="saving in savings.filter((item) => item.currency === currentPeriod.currency)"
                                        :key="saving.id"
                                        :value="saving.id"
                                    >{{ saving.name }}</option>
                                </select>
                                <AmountInput v-model="part.amount" class="field" :placeholder="$t('Nominal')" />
                            </div>
                        </div>
                        <p class="mt-3 text-xs text-[#637083]">
                            {{ $t('Total klaim Rp') }} {{ formatMoney(claimTotal) }} {{ $t('dari sisa Rp') }} {{ formatMoney(currentPeriod.remaining) }}
                        </p>
                    </div>

                    <div class="rounded-xl border border-[#E4EAF4] p-4">
                        <label class="block text-sm font-semibold text-[#17243A]">
                            {{ $t('Lepas ke saldo bebas') }}
                            <AmountInput
                                :model-value="releaseAmount"
                                class="field"
                                :placeholder="$t('Nominal')"
                                @update:model-value="$emit('update:releaseAmount', $event)"
                            />
                        </label>
                        <ButtonPrimary
                            type="button"
                            variant="secondary"
                            class="mt-3 !border-[#2463F5] !text-[#1764F5]"
                            :disabled="!Number(releaseAmount)"
                            @click="$emit('period-action', releasePeriod, currentPeriod, { amount: releaseAmount })"
                        >{{ $t('Lepas sisa') }}</ButtonPrimary>
                    </div>
                </div>

                <footer class="mt-5 flex justify-end gap-3 border-t border-[#DCE4F0] pt-4">
                    <p v-if="actionError" class="mr-auto self-center text-sm text-red-600">{{ actionError }}</p>
                    <ButtonPrimary type="button" variant="secondary" class="!px-5" @click="$emit('close-claim')">{{ $t('Batal') }}</ButtonPrimary>
                    <ButtonPrimary type="button" class="!px-6" :disabled="claimTotal <= 0" @click="$emit('submit-claim')">{{ $t('Simpan klaim') }}</ButtonPrimary>
                </footer>
            </section>
        </div>

        <div class="grid items-stretch gap-5 xl:grid-cols-[minmax(0,1.8fr)_minmax(320px,1fr)]">
            <section class="min-h-[340px] rounded-2xl border border-[#E4EAF4] bg-white p-5 sm:p-6">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="text-lg font-bold text-[#111B39]">{{ $t('Riwayat belanja') }}</h2>
                    <span class="rounded-lg border border-[#DCE4F0] px-3 py-2 text-sm text-[#64748B]">{{ $t('Periode ini') }}⌄</span>
                </div>
                <div class="mt-5 grid grid-cols-[minmax(90px,0.8fr)_minmax(110px,1.5fr)_minmax(90px,1fr)_auto] gap-3 rounded-lg bg-[#F3F6FB] px-4 py-3 text-xs font-semibold text-[#718096]">
                    <span>{{ $t('Tanggal') }}</span><span>{{ $t('Nama belanja') }}</span><span>{{ $t('Kategori') }}</span><span class="text-right">{{ $t('Nominal') }}</span>
                </div>
                <div v-if="currentPeriod?.transactions?.length" class="divide-y divide-[#EDF1F6]">
                    <div v-for="transaction in currentPeriod.transactions" :key="transaction.id" class="grid grid-cols-[minmax(90px,0.8fr)_minmax(110px,1.5fr)_minmax(90px,1fr)_auto] gap-3 px-4 py-4 text-sm">
                        <span class="text-[#64748B]">{{ formatDate(transaction.date) }}</span><span class="truncate font-medium text-[#17243A]">{{ transaction.title }}</span><span class="truncate text-[#64748B]">{{ transaction.category || $t('Tanpa kategori') }}</span><strong class="whitespace-nowrap text-right text-[#17243A]">{{ $t('Rp') }} {{ formatMoney(transaction.amount) }}</strong>
                    </div>
                </div>
                <div v-else class="flex min-h-[220px] flex-col items-center justify-center gap-3 text-center">
                    <ReceiptText :size="46" class="text-[#9AA9BF]" />
                    <p class="font-semibold text-[#17243A]">{{ $t('Belum ada belanja') }}</p>
                    <p class="text-sm text-[#8190A8]">{{ $t('Belanja yang dicatat akan muncul di sini.') }}</p>
                </div>
            </section>
            <section class="rounded-2xl border border-[#E4EAF4] bg-white p-5 sm:p-6">
                <h2 class="text-lg font-bold text-[#111B39]">{{ $t('Detail anggaran') }}</h2>
                <dl class="mt-4 divide-y divide-[#E7ECF4] border-t border-[#E7ECF4] text-sm">
                    <div class="flex justify-between gap-4 py-3"><dt class="text-[#718096]">{{ $t('Nama plan') }}</dt><dd class="font-medium text-[#17243A]">{{ plan.name }}</dd></div>
                    <div class="flex justify-between gap-4 py-3"><dt class="text-[#718096]">{{ $t('Jenis') }}</dt><dd class="font-medium text-[#17243A]">{{ $t('Anggaran belanja') }}</dd></div>
                    <div class="flex justify-between gap-4 py-3"><dt class="text-[#718096]">{{ $t('Mulai') }}</dt><dd class="font-medium text-[#17243A]">{{ formatDate(plan.starts_on) }}</dd></div>
                    <div class="flex justify-between gap-4 py-3"><dt class="text-[#718096]">{{ $t('Berakhir') }}</dt><dd class="font-medium text-[#17243A]">{{ formatDate(plan.ends_on || plan.periods?.at(-1)?.ends_on) }}</dd></div>
                    <div class="flex justify-between gap-4 py-3"><dt class="text-[#718096]">{{ $t('Status') }}</dt><dd><span class="rounded-full bg-[#DCF8E9] px-3 py-1 font-semibold text-[#12815B]">{{ planStatusLabel(plan.status) }}</span></dd></div>
                </dl>
            </section>
        </div>
    </div>
    <article
        v-else
        :key="`detail-${plan.id}`"
        class="flex flex-col gap-4 rounded-2xl bg-white p-5"
    >
                <div class="flex items-start justify-between gap-3">
                    <div class="flex min-w-0 items-center gap-3">
                        <span
                            class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl text-white"
                            :style="planIconStyle(plan.color)"
                        >
                            <component :is="planIcon(plan.icon)" :size="23" />
                        </span>
                        <div class="min-w-0">
                            <h2 class="truncate text-lg font-semibold">{{ plan.name }}</h2>
                            <div class="mt-1 flex flex-wrap items-center gap-2 text-xs">
                                <span class="text-[#637083]">
                                    {{ plan.type === 'goal' ? 'Rencana Tujuan' : 'Anggaran Belanja' }}
                                </span>
                                <span class="rounded-full bg-[#EEF3FF] px-2 py-1 font-medium text-[#2457DA]">
                                    {{ planStatusLabel(plan.status) }}
                                </span>
                            </div>
                        </div>
                    </div>
                    <strong class="text-sm text-[#2457DA]"> {{ $t("Rp") }} {{ formatMoney(plan.type === 'goal' ? plan.target_amount : plan.amount) }}
                    </strong>
                </div>
                <div class="flex flex-wrap gap-2">
                    <ButtonPrimary type="button" variant="secondary" @click="$emit('edit', plan)">{{ $t("Edit") }}</ButtonPrimary>
                    <ButtonPrimary
                        v-if="plan.status === 'active'"
                        type="button"
                        variant="secondary"
                        @click="$emit('change-status', plan, 'paused')"
                    >{{ $t("Jeda") }}</ButtonPrimary>
                    <ButtonPrimary
                        v-if="plan.status === 'paused'"
                        type="button"
                        variant="secondary"
                        @click="$emit('change-status', plan, 'active')"
                    >{{ $t("Continue") }}</ButtonPrimary>
                    <ButtonPrimary
                        v-if="['active', 'paused'].includes(plan.status)"
                        type="button"
                        variant="secondary"
                        @click="$emit('change-status', plan, 'ended')"
                    >{{ $t("Hentikan") }}</ButtonPrimary>
                    <ButtonPrimary
                        v-if="plan.type === 'goal' && plan.status !== 'completed'"
                        type="button"
                        variant="secondary"
                        @click="$emit('change-status', plan, 'completed')"
                    >{{ $t("Tandai selesai") }}</ButtonPrimary>
                </div>
                <div v-if="plan.type === 'goal'" class="grid grid-cols-2 gap-3 rounded-xl bg-[#F3F6FF] p-4 text-sm">
                    <p>{{ $t("Dana tersedia") }}<br /><strong>{{ $t("Rp") }} {{ formatMoney(plan.goal_available) }}</strong></p>
                    <p>{{ $t("Biaya dibayar") }}<br /><strong>{{ $t("Rp") }} {{ formatMoney(plan.goal_paid) }}</strong></p>
                    <p>{{ $t("Kebutuhan didanai") }}<br /><strong>{{ $t("Rp") }} {{ formatMoney(plan.goal_funded) }}</strong></p>
                    <p>{{ $t("Kekurangan") }}<br /><strong>{{ $t("Rp") }} {{ formatMoney(plan.goal_shortfall) }}</strong></p>
                    <p class="col-span-2 text-xs text-[#637083]"> {{ $t("Target") }} {{ plan.target_date }} · Savings {{ plan.pocket?.name }}
                    </p>
                </div>
                <div v-if="plan.items?.length" class="text-sm">
                    <p class="font-semibold">{{ $t("Rincian kebutuhan") }}</p>
                    <p v-for="item in plan.items" :key="item.id" class="flex justify-between">
                        <span>{{ item.name }}</span>
                        <span>{{ $t("Rp") }} {{ formatMoney(item.amount) }}</span>
                    </p>
                </div>
                <div
                    v-for="period in plan.periods"
                    :key="period.id"
                    class="flex flex-col gap-3 rounded-2xl bg-[#F8FAFD] p-4 text-sm"
                >
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="font-semibold">{{ period.starts_on }} – {{ period.ends_on }}</p>
                            <p class="mt-1 text-xs text-[#637083]">
                                {{ plan.type === 'goal' ? 'Setoran tujuan' : 'Anggaran belanja' }}
                            </p>
                        </div>
                        <span class="shrink-0 rounded-full bg-white px-2.5 py-1 text-xs font-medium text-[#637083]">
                            {{ periodStatusLabel(period.status) }}
                        </span>
                    </div>

                    <div v-if="plan.type === 'spending'" class="mt-4 rounded-xl bg-white p-3">
                        <div class="flex items-center justify-between gap-3">
                            <p class="text-xs font-medium text-[#637083]">{{ $t("Penggunaan cadangan") }}</p>
                            <strong class="text-xs" :class="periodUsagePercentage(period) >= 100 ? 'text-[#B34735]' : 'text-[#17243A]'">
                                {{ Math.round(periodUsagePercentage(period)) }}%
                            </strong>
                        </div>
                        <div
                            class="mt-2 h-2.5 overflow-hidden rounded-full bg-[#E9EDF3]"
                            role="progressbar"
                            :aria-label="`Penggunaan anggaran ${plan.name}`"
                            aria-valuemin="0"
                            aria-valuemax="100"
                            :aria-valuenow="Math.round(periodUsagePercentage(period))"
                        >
                            <div
                                class="h-full rounded-full transition-[width] duration-300"
                                :class="periodUsageColor(period)"
                                :style="{ width: `${periodUsagePercentage(period)}%` }"
                            ></div>
                        </div>
                        <div class="mt-3 grid grid-cols-3 gap-2">
                            <div>
                                <p class="text-[10px] text-[#637083]">{{ $t("Dialokasikan") }}</p>
                                <p class="mt-0.5 text-xs font-semibold">{{ $t("Rp") }} {{ formatMoney(period.allocated_amount) }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] text-[#637083]">{{ $t("Terpakai") }}</p>
                                <p class="mt-0.5 text-xs font-semibold">{{ $t("Rp") }} {{ formatMoney(period.spent) }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] text-[#637083]">{{ $t("Sisa") }}</p>
                                <p class="mt-0.5 text-xs font-semibold">{{ $t("Rp") }} {{ formatMoney(period.remaining) }}</p>
                            </div>
                        </div>
                        <p class="mt-2 text-[10px] text-[#637083]"> {{ $t("Rencana Rp") }} {{ formatMoney(period.planned_amount) }} ·
                            Dilepas Rp {{ formatMoney(period.released_amount) }}
                        </p>
                    </div>
                    <div v-else class="mt-4 rounded-xl bg-white p-3 text-xs text-[#637083]"> {{ $t("Rencana Rp") }} {{ formatMoney(period.planned_amount) }} ·
                        Setoran Rp {{ formatMoney(period.contributed) }}
                    </div>

                    <div v-if="period.claims?.length" class="mt-1 flex flex-col gap-1 text-xs text-[#637083]">
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
                                @click="$emit('period-action', reverseClaim, claim)"
                            >{{ $t("Balik klaim") }}</ButtonPrimary>
                        </div>
                    </div>
                    <div v-if="period.status !== 'closed'" class="mt-1 flex flex-wrap gap-2">
                        <ButtonPrimary
                            v-if="plan.type === 'spending' && period.status === 'awaiting_funding'"
                            type="button"
                            @click="$emit('period-action', fundPeriod, period)"
                        >{{ $t("Danai") }}</ButtonPrimary>
                        <ButtonPrimary
                            v-if="plan.type === 'spending' && period.status === 'open'"
                            type="button"
                            @click="$emit('open-transaction', plan, period)"
                        >{{ $t("Catat belanja") }}</ButtonPrimary>
                        <ButtonPrimary
                            v-if="plan.type === 'spending' && Number(period.remaining) > 0"
                            type="button"
                            @click="$emit('open-claim', period)"
                        >{{ $t("Klaim sisa") }}</ButtonPrimary>
                        <Link
                            v-if="plan.type === 'goal'"
                            :href="savingsUrl({ query: { saving: plan.saving_id, period: period.id } })"
                            class="text-sm font-semibold text-[#2457DA]"
                        >{{ $t("Sisihkan Dana") }}</Link>
                        <ButtonPrimary
                            v-if="plan.type === 'spending' && Number(period.remaining) === 0"
                            type="button"
                            variant="secondary"
                            @click="$emit('period-action', closePeriod, period)"
                        >{{ $t("Tutup periode") }}</ButtonPrimary>
                    </div>
                    <div v-if="activeClaim === period.id" class="mt-1 flex flex-col gap-2 rounded-xl bg-[#F7F9FC] p-3">
                        <p class="font-semibold">{{ $t("Bagi sisa ke savings") }}</p>
                        <div v-for="(part, index) in claimParts" :key="index" class="flex gap-2">
                            <select v-model="part.saving_id" class="field" required>
                                <option value="">{{ $t("Tujuan") }}</option>
                                <option v-for="saving in savings.filter((item) => item.currency === period.currency)" :key="saving.id" :value="saving.id">{{ saving.name }}</option>
                            </select>
                            <AmountInput v-model="part.amount" class="field" />
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <ButtonPrimary type="button" variant="secondary" @click="claimParts.push({ saving_id: '', amount: '' })">{{ $t("Tambah tujuan") }}</ButtonPrimary>
                            <ButtonPrimary type="button" @click="$emit('submit-claim')">{{ $t("Simpan klaim") }}</ButtonPrimary>
                        </div>
                        <p class="text-xs text-[#637083]"> {{ $t("Total klaim Rp") }} {{ formatMoney(claimTotal) }} {{ $t("dari sisa Rp") }} {{ formatMoney(period.remaining) }}
                        </p>
                        <label class="text-xs">{{ $t("Atau lepas ke uang bebas") }} <input :value="releaseAmount"
                                @input="$emit('update:releaseAmount', $event.target.value)" class="field" type="number" min="0.01" step="0.01" />
                        </label>
                        <ButtonPrimary type="button" variant="secondary" @click="$emit('period-action', releasePeriod, period, { amount: releaseAmount })"> {{ $t("Lepas sisa") }} </ButtonPrimary>
                    </div>
                </div>
    </article>
</template>

<style scoped>
.field {
    @apply w-full rounded-xl border border-[#DCE3EE] bg-white px-3 py-2 text-sm;
}
</style>
