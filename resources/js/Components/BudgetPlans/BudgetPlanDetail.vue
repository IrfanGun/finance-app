<script setup>
import { Link } from '@inertiajs/vue3';

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
});

defineEmits([
    'edit',
    'change-status',
    'period-action',
    'open-transaction',
    'open-claim',
    'submit-claim',
    'update:releaseAmount',
]);
</script>

<template>
            <article
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
