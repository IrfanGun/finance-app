<script setup>
import { computed, ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import {
    ArrowRight,
    Ellipsis,
    LayoutGrid,
    List,
    Search,
    SlidersHorizontal,
} from 'lucide-vue-next';

const props = defineProps({
    plans: { type: Array, default: () => [] },
    activePlanCount: { type: Number, default: 0 },
    createPlanUrl: { type: String, required: true },
    showPlanUrl: { type: Function, required: true },
    periodForSelectedMonth: { type: Function, required: true },
    isPlanScheduledForSelectedMonth: { type: Function, required: true },
    periodUsagePercentage: { type: Function, required: true },
    formatCurrency: { type: Function, required: true },
    planStatusLabel: { type: Function, required: true },
    planIcon: { type: Function, required: true },
    planIconStyle: { type: Function, required: true },
});

const emit = defineEmits(['edit', 'delete']);
const selectedFilter = ref('all');
const viewMode = ref('table');
const searchTerm = ref('');
const planTypeFilter = ref('all');
const filtersOpen = ref(false);
const openMenuPlanId = ref(null);

const scheduledPlans = computed(() => props.plans.filter((plan) =>
    props.isPlanScheduledForSelectedMonth(plan),
));

const filterCounts = computed(() => ({
    all: scheduledPlans.value.length,
    active: scheduledPlans.value.filter((plan) => plan.status === 'active').length,
    completed: scheduledPlans.value.filter((plan) => ['completed', 'ended'].includes(plan.status)).length,
}));

const visiblePlans = computed(() => scheduledPlans.value.filter((plan) => {
    const matchesStatus = selectedFilter.value === 'all'
        || (selectedFilter.value === 'active' && plan.status === 'active')
        || (selectedFilter.value === 'completed' && ['completed', 'ended'].includes(plan.status));
    const matchesType = planTypeFilter.value === 'all' || plan.type === planTypeFilter.value;
    const matchesSearch = !searchTerm.value
        || `${plan.name} ${plan.notes ?? ''} ${plan.items?.map((item) => item.name).join(' ') ?? ''}`
            .toLowerCase()
            .includes(searchTerm.value.toLowerCase());

    return matchesStatus
        && matchesType
        && matchesSearch;
}));

const amountForPeriod = (plan) => {
    const period = props.periodForSelectedMonth(plan);

    if (period) {
        return Number(period.allocated_amount ?? 0);
    }

    return Number(plan.amount ?? 0);
};

const spentForPlan = (plan) => plan.type === 'goal'
    ? Number(plan.goal_paid ?? 0)
    : Number(props.periodForSelectedMonth(plan)?.spent ?? 0);

const usageForPlan = (plan) => {
    const period = props.periodForSelectedMonth(plan);

    if (period) {
        return props.periodUsagePercentage(period);
    }

    return props.periodUsagePercentage({
        allocated_amount: plan.type === 'goal' ? plan.target_amount : plan.amount,
        spent: plan.type === 'goal' ? plan.goal_funded : 0,
    });
};

const remainingForPlan = (plan) => {
    if (plan.type === 'goal') {
        return Number(plan.goal_shortfall ?? 0);
    }

    const period = props.periodForSelectedMonth(plan);

    return Number(period?.remaining ?? (period ? 0 : plan.amount ?? 0));
};

const allocationLabel = (plan) => {
    const period = props.periodForSelectedMonth(plan);

    if (period?.status === 'awaiting_funding') {
        return 'Belum dialokasikan';
    }

    return props.formatCurrency(amountForPeriod(plan), plan.currency);
};

const amountSummaryForPlan = (plan) => plan.type === 'goal'
    ? `${props.formatCurrency(plan.goal_funded, plan.currency)} / ${props.formatCurrency(plan.target_amount, plan.currency)}`
    : `${props.formatCurrency(spentForPlan(plan), plan.currency)} / ${allocationLabel(plan)}`;

const statusLabel = (plan) => props.periodForSelectedMonth(plan)?.status === 'awaiting_funding'
    ? 'Perlu dana'
    : props.planStatusLabel(plan.status);

const statusClass = (plan) => {
    if (props.periodForSelectedMonth(plan)?.status === 'awaiting_funding') {
        return 'bg-[#FFF2D8] text-[#B96B17]';
    }

    if (plan.status === 'active') {
        return 'bg-[#E4F7EF] text-[#16815B]';
    }

    if (plan.status === 'completed') {
        return 'bg-[#EEF3FF] text-[#2457DA]';
    }

    return 'bg-[#F1F3F6] text-[#637083]';
};

const closeMenu = () => {
    openMenuPlanId.value = null;
};
</script>

<template>
<section class="min-w-0 overflow-hidden rounded-2xl border border-[#E4EAF4] bg-white">
    <div class="space-y-4 border-b border-[#E9EEF5] px-5 py-5 sm:px-6">
        <div class="flex flex-wrap items-center gap-3">
            <h2 class="text-xl font-semibold tracking-tight text-[#111B39]">{{ $t('Budget saya') }}</h2>
            <span class="rounded-full bg-[#E3F7EE] px-3 py-1 text-xs font-semibold text-[#16815B]">
                {{ activePlanCount }} {{ $t('aktif') }}
            </span>
        </div>
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex flex-wrap gap-2">
                <ButtonPrimary
                    v-for="filter in [
                        { value: 'all', label: 'Semua' },
                        { value: 'active', label: 'Aktif' },
                        { value: 'completed', label: 'Selesai' },
                    ]"
                    :key="filter.value"
                    type="button"
                    :variant="selectedFilter === filter.value ? 'primary' : 'secondary'"
                    class="!min-h-9 !rounded-full !px-4 !py-1.5 !text-xs"
                    @click="selectedFilter = filter.value"
                >
                    {{ $t(filter.label) }} ({{ filterCounts[filter.value] }})
                </ButtonPrimary>
            </div>
            <div class="flex w-full items-center gap-2 sm:w-auto">
                <label class="flex h-10 min-w-0 flex-1 items-center gap-2 rounded-xl border border-[#DCE4F0] bg-[#FBFCFE] px-3 sm:w-64 sm:flex-none">
                    <Search :size="17" class="shrink-0 text-[#8796AF]" />
                    <input
                        v-model="searchTerm"
                        class="w-full border-0 bg-transparent p-0 text-sm text-[#233451] placeholder:text-[#8A98AE] focus:ring-0"
                        :placeholder="$t('Cari budget...')"
                    />
                </label>
                <ButtonPrimary
                    type="button"
                    variant="secondary"
                    class="!flex !h-10 !min-h-10 !w-10 !shrink-0 !items-center !justify-center !rounded-xl !p-0"
                    :aria-expanded="filtersOpen"
                    :aria-label="$t('Filter budget')"
                    @click="filtersOpen = !filtersOpen"
                >
                    <SlidersHorizontal :size="18" />
                </ButtonPrimary>
                <div class="flex shrink-0 items-center gap-1 rounded-xl border border-[#DCE4F0] bg-white p-1">
                    <ButtonPrimary
                        type="button"
                        :variant="viewMode === 'cards' ? 'primary' : 'secondary'"
                        class="!flex !h-8 !min-h-8 !w-8 !items-center !justify-center !rounded-lg !border-0 !p-0"
                        :aria-pressed="viewMode === 'cards'"
                        :aria-label="$t('Tampilan kartu')"
                        @click="viewMode = 'cards'"
                    >
                        <LayoutGrid :size="17" />
                    </ButtonPrimary>
                    <ButtonPrimary
                        type="button"
                        :variant="viewMode === 'table' ? 'primary' : 'secondary'"
                        class="!flex !h-8 !min-h-8 !w-8 !items-center !justify-center !rounded-lg !border-0 !p-0"
                        :aria-pressed="viewMode === 'table'"
                        :aria-label="$t('Tampilan tabel')"
                        @click="viewMode = 'table'"
                    >
                        <List :size="17" />
                    </ButtonPrimary>
                </div>
            </div>
        </div>
        <div v-if="filtersOpen" class="flex flex-wrap items-center gap-3 rounded-xl bg-[#F6F8FC] p-3">
            <span class="text-xs font-semibold uppercase tracking-wide text-[#687A9B]">{{ $t('Tipe budget') }}</span>
            <ButtonPrimary
                v-for="filter in [
                    { value: 'all', label: 'Semua tipe' },
                    { value: 'spending', label: 'Belanja' },
                    { value: 'goal', label: 'Target' },
                ]"
                :key="filter.value"
                type="button"
                :variant="planTypeFilter === filter.value ? 'primary' : 'secondary'"
                class="!min-h-8 !rounded-lg !px-3 !py-1 !text-xs"
                @click="planTypeFilter = filter.value"
            >{{ $t(filter.label) }}</ButtonPrimary>
        </div>
    </div>

    <div v-if="viewMode === 'table'" class="overflow-x-auto px-3 sm:px-4">
        <table class="w-full min-w-[850px] border-collapse text-left">
            <thead>
                <tr class="text-[11px] font-semibold uppercase tracking-wide text-[#687A9B]">
                    <th class="px-3 py-3">{{ $t('Budget') }}</th>
                    <th class="px-3 py-3">{{ $t('Alokasi') }}</th>
                    <th class="px-3 py-3">{{ $t('Terpakai') }}</th>
                    <th class="px-3 py-3">{{ $t('Sisa') }}</th>
                    <th class="px-3 py-3">{{ $t('Status') }}</th>
                    <th class="w-12 px-2 py-3"><span class="sr-only">{{ $t('Aksi') }}</span></th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="plan in visiblePlans" :key="plan.id" class="border-t border-[#E9EEF5] first:border-0 hover:bg-[#FAFBFD]">
                    <td class="px-3 py-3">
                        <Link :href="showPlanUrl(plan.id)" class="flex min-w-0 items-center gap-3">
                            <span
                                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl text-white"
                                :style="planIconStyle(plan.color)"
                            >
                                <component :is="planIcon(plan.icon)" :size="19" />
                            </span>
                            <span class="min-w-0">
                                <span class="block truncate text-sm font-semibold text-[#17243A]">{{ plan.name }}</span>
                                <span class="mt-0.5 block max-w-52 truncate text-xs text-[#687A9B]">
                                    {{ plan.items?.map((item) => item.name).join(', ') || (plan.type === 'goal' ? $t('Target tabungan') : $t('Anggaran belanja')) }}
                                </span>
                            </span>
                        </Link>
                    </td>
                    <td class="px-3 py-3">
                        <span
                            class="block text-sm font-medium"
                            :class="periodForSelectedMonth(plan)?.status === 'awaiting_funding' ? 'text-[#A64F0A]' : 'text-[#26354C]'"
                        >{{ allocationLabel(plan) }}</span>
                        <span v-if="periodForSelectedMonth(plan)?.status === 'awaiting_funding'" class="mt-0.5 block text-xs text-[#8796AF]">
                            {{ $t('Setel alokasi') }}
                        </span>
                    </td>
                    <td class="w-44 px-3 py-3">
                        <p class="text-sm font-semibold text-[#26354C]">
                            {{ formatCurrency(spentForPlan(plan), plan.currency) }}
                        </p>
                        <div class="mt-1 flex items-center gap-2">
                            <div class="h-2 min-w-20 flex-1 overflow-hidden rounded-full bg-[#E5EBF4]">
                                <div
                                    class="h-full rounded-full bg-gradient-to-r from-[#1680F8] to-[#2457DA]"
                                    :style="{ width: `${periodUsagePercentage(periodForSelectedMonth(plan) ?? { allocated_amount: plan.target_amount, spent: plan.goal_funded })}%` }"
                                />
                            </div>
                            <span class="w-8 text-right text-xs text-[#687A9B]">
                                {{ Math.round(periodUsagePercentage(periodForSelectedMonth(plan) ?? { allocated_amount: plan.target_amount, spent: plan.goal_funded })) }}%
                            </span>
                        </div>
                    </td>
                    <td class="px-3 py-3 text-sm font-medium text-[#26354C]">
                        {{ formatCurrency(remainingForPlan(plan), plan.currency) }}
                    </td>
                    <td class="px-3 py-3">
                        <span class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-full px-3 py-1.5 text-xs font-semibold" :class="statusClass(plan)">
                            <span class="h-1.5 w-1.5 rounded-full bg-current" />
                            {{ $t(statusLabel(plan)) }}
                        </span>
                    </td>
                    <td class="relative px-2 py-3 text-right">
                        <ButtonPrimary
                            type="button"
                            variant="secondary"
                            class="!flex !h-9 !min-h-9 !w-9 !items-center !justify-center !rounded-lg !border-0 !p-0 !text-[#687A9B]"
                            :aria-label="`${$t('Aksi untuk')} ${plan.name}`"
                            :aria-expanded="openMenuPlanId === plan.id"
                            @click="openMenuPlanId = openMenuPlanId === plan.id ? null : plan.id"
                        >
                            <Ellipsis :size="19" />
                        </ButtonPrimary>
                        <div v-if="openMenuPlanId === plan.id" class="absolute right-2 top-12 z-10 w-44 rounded-xl border border-[#E4EAF4] bg-white p-1.5 text-left shadow-xl">
                            <Link
                                :href="showPlanUrl(plan.id)"
                                class="block rounded-lg px-3 py-2 text-sm text-[#344563] hover:bg-[#F4F7FB]"
                                @click="closeMenu"
                            >{{ $t('Lihat detail') }}</Link>
                            <ButtonPrimary
                                type="button"
                                variant="secondary"
                                class="!min-h-0 !w-full !rounded-lg !border-0 !px-3 !py-2 !text-left !text-sm !font-normal"
                                @click="emit('edit', plan); closeMenu()"
                            >{{ $t('Edit budget') }}</ButtonPrimary>
                            <ButtonPrimary
                                type="button"
                                variant="secondary"
                                class="!min-h-0 !w-full !rounded-lg !border-0 !px-3 !py-2 !text-left !text-sm !font-normal !text-red-600"
                                @click="emit('delete', plan); closeMenu()"
                            >{{ $t('Hapus budget') }}</ButtonPrimary>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <div v-else class="grid gap-3 p-4 sm:grid-cols-2 xl:grid-cols-3">
        <div
            v-for="plan in visiblePlans"
            :key="`card-${plan.id}`"
            class="relative min-h-[190px] rounded-xl border border-[#E4EAF4] bg-white p-4 transition hover:border-[#BBD2F8] hover:bg-[#FCFDFF]"
        >
            <Link
                :href="showPlanUrl(plan.id)"
                class="flex min-h-[158px] flex-col gap-3 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#2457DA]"
            >
                <div class="flex items-start gap-3 pr-10">
                    <div class="flex min-w-0 flex-1 items-center gap-3">
                        <span
                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl text-white"
                            :style="planIconStyle(plan.color)"
                        >
                            <component :is="planIcon(plan.icon)" :size="19" />
                        </span>
                        <span class="min-w-0">
                            <span class="block truncate text-sm font-semibold text-[#17243A]">{{ plan.name }}</span>
                            <span class="mt-0.5 block truncate text-xs text-[#687A9B]">
                                {{ plan.items?.map((item) => item.name).join(', ') || (plan.type === 'goal' ? $t('Target tabungan') : $t('Anggaran belanja')) }}
                            </span>
                            <span class="mt-2 inline-flex items-center gap-1.5 whitespace-nowrap rounded-full px-2.5 py-1 text-[11px] font-semibold" :class="statusClass(plan)">
                                {{ $t(statusLabel(plan)) }}
                            </span>
                        </span>
                    </div>
                </div>

                <div class="mt-auto">
                    <p class="text-base font-semibold tracking-tight text-[#111B39]">
                        {{ amountSummaryForPlan(plan) }}
                    </p>
                    <div class="mt-2 h-2.5 overflow-hidden rounded-full bg-[#E5EBF4]">
                        <div
                            class="h-full rounded-full bg-gradient-to-r from-[#1680F8] to-[#2457DA]"
                            :style="{ width: `${usageForPlan(plan)}%` }"
                        />
                    </div>
                </div>

                <div class="flex items-center justify-between gap-3 border-t border-[#EEF1F5] pt-2.5 text-xs">
                    <span class="text-[#687A9B]">
                        {{ $t('Sisa') }}
                        <strong class="ml-1 font-semibold text-[#243451]">{{ formatCurrency(remainingForPlan(plan), plan.currency) }}</strong>
                    </span>
                    <ArrowRight :size="16" class="text-[#1768EB]" />
                </div>
            </Link>
            <ButtonPrimary
                type="button"
                variant="secondary"
                class="!absolute !right-3 !top-3 !flex !h-9 !min-h-9 !w-9 !items-center !justify-center !rounded-lg !border-0 !p-0 !text-[#687A9B]"
                :aria-label="`${$t('Aksi untuk')} ${plan.name}`"
                :aria-expanded="openMenuPlanId === plan.id"
                @click="openMenuPlanId = openMenuPlanId === plan.id ? null : plan.id"
            >
                <Ellipsis :size="19" />
            </ButtonPrimary>
            <div
                v-if="openMenuPlanId === plan.id"
                class="absolute right-3 top-12 z-10 w-44 rounded-xl border border-[#E4EAF4] bg-white p-1.5 text-left shadow-xl"
            >
                <ButtonPrimary
                    type="button"
                    variant="secondary"
                    class="!min-h-0 !w-full !rounded-lg !border-0 !px-3 !py-2 !text-left !text-sm !font-normal"
                    @click="emit('edit', plan); closeMenu()"
                >{{ $t('Edit budget') }}</ButtonPrimary>
                <ButtonPrimary
                    type="button"
                    variant="secondary"
                    class="!min-h-0 !w-full !rounded-lg !border-0 !px-3 !py-2 !text-left !text-sm !font-normal !text-red-600"
                    @click="emit('delete', plan); closeMenu()"
                >{{ $t('Hapus budget') }}</ButtonPrimary>
            </div>
        </div>

        <Link
            :href="createPlanUrl"
            class="flex min-h-[190px] flex-col items-center justify-center gap-3 rounded-xl border border-dashed border-[#BBD2F8] bg-[#FBFDFF] p-5 text-center transition hover:bg-[#F4F8FF]"
        >
            <span class="flex h-11 w-11 items-center justify-center rounded-full bg-[#E8F1FF] text-2xl font-light text-[#1768EB]">+</span>
            <span>
                <strong class="block text-sm font-semibold text-[#075EDB]">{{ $t('Buat budget baru') }}</strong>
                <span class="mt-1 block max-w-44 text-xs leading-relaxed text-[#687A9B]">
                    {{ $t('Tambah kategori budget sesuai kebutuhanmu.') }}
                </span>
            </span>
        </Link>
    </div>

    <div v-if="!visiblePlans.length" class="px-4 py-8 text-center">
        <p class="text-sm font-semibold text-[#344563]">{{ $t('Tidak ada budget yang cocok.') }}</p>
        <p class="mt-1 text-xs text-[#8796AF]">{{ $t('Coba ubah pencarian atau filter.') }}</p>
    </div>
</section>
</template>
