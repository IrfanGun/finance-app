<script setup>
import { computed } from 'vue';
import { ArrowRight, Lightbulb } from 'lucide-vue-next';

const props = defineProps({
    monthlyPlans: { type: Array, default: () => [] },
    selectedMonthLabel: { type: String, required: true },
    selectedCurrency: { type: String, required: true },
    createPlanUrl: { type: String, required: true },
    formatCurrency: { type: Function, required: true },
});

const monthlyTotals = computed(() => props.monthlyPlans.reduce((totals, { plan, period }) => ({
    allocated: totals.allocated + Number(period?.allocated_amount ?? plan.amount ?? 0),
    spent: totals.spent + Number(period?.spent ?? 0),
}), { allocated: 0, spent: 0 }));

const monthlyUsagePercentage = computed(() => monthlyTotals.value.allocated > 0
    ? Math.min((monthlyTotals.value.spent / monthlyTotals.value.allocated) * 100, 100)
    : 0);

const chartDotColor = (index) => [
    '#1768EB',
    '#448CF5',
    '#79ACFA',
    '#A8CCFF',
][Math.min(index, 3)];
</script>

<template>
    <aside class="space-y-4 rounded-2xl border border-[#E4EAF4] bg-white p-5 sm:p-6">
        <div class="flex items-center justify-between gap-3">
            <h2 class="text-xl font-semibold tracking-tight text-[#111B39]">{{ $t('Ringkasan budget') }}</h2>
            <span class="rounded-lg bg-[#F4F7FB] px-2.5 py-1 text-xs font-medium capitalize text-[#687A9B]">{{ selectedMonthLabel }}</span>
        </div>

        <section class="border-b border-[#E9EEF5] pb-4">
            <div class="flex items-center justify-between gap-3 text-sm">
                <p class="font-semibold text-[#243451]">{{ $t('Pemakaian budget') }}</p>
                <strong class="text-[#111B39]">{{ Math.round(monthlyUsagePercentage) }}%</strong>
            </div>
            <div class="mt-3 h-3 overflow-hidden rounded-full bg-[#E7EDF6]">
                <div
                    class="h-full rounded-full bg-gradient-to-r from-[#0878F9] to-[#63A2FC]"
                    :style="{ width: `${monthlyUsagePercentage}%` }"
                />
            </div>
            <p class="mt-2 text-lg font-semibold text-[#111B39]">
                {{ formatCurrency(monthlyTotals.spent, selectedCurrency) }}
            </p>
            <p class="text-xs text-[#687A9B]">
                {{ $t('dari') }} {{ formatCurrency(monthlyTotals.allocated, selectedCurrency) }}
            </p>
        </section>

        <section class="space-y-3 border-b border-[#E9EEF5] pb-4">
            <div v-for="({ plan, period }, index) in monthlyPlans" :key="plan.id" class="flex items-center gap-2 text-xs">
                <span class="h-2.5 w-2.5 shrink-0 rounded-full" :style="{ backgroundColor: chartDotColor(index) }" />
                <span class="min-w-0 flex-1 truncate text-[#52627E]">{{ plan.name }}</span>
                <span class="shrink-0 text-[#52627E]">{{ formatCurrency(period?.spent ?? 0, selectedCurrency) }}</span>
                <span class="w-10 shrink-0 text-right text-[#8796AF]">
                    {{ Math.round((period?.allocated_amount ?? plan.amount) > 0 ? Number(period?.spent ?? 0) / Number(period?.allocated_amount ?? plan.amount) * 100 : 0) }}%
                </span>
            </div>
            <p v-if="!monthlyPlans.length" class="text-xs text-[#8796AF]">
                {{ $t('Belum ada pemakaian budget bulan ini.') }}
            </p>
        </section>

        <div class="flex gap-3 rounded-xl border border-[#D9E8FF] bg-[#F1F6FF] p-3.5">
            <Lightbulb :size="19" class="mt-0.5 shrink-0 text-[#1768EB]" />
            <p class="text-xs leading-relaxed text-[#536988]">
                {{ $t('Tinjau budget yang belum didanai sebelum mencatat pengeluaran. Alokasikan dana pada budget yang masih kosong agar pengeluaran tercatat dengan lebih rapi.') }}
            </p>
        </div>

        <ButtonPrimary
            :href="createPlanUrl"
            class="!inline-flex !min-h-0 !w-full !items-center !justify-center !gap-2 !rounded-lg !px-3 !py-2 !text-xs"
        >
            {{ $t('Buat budget baru') }}
            <ArrowRight :size="16" />
        </ButtonPrimary>
    </aside>
</template>
