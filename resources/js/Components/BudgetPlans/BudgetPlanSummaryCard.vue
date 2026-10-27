<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    plan: { type: Object, required: true },
    planIcon: { type: Function, required: true },
    planIconStyle: { type: Function, required: true },
    planStatusLabel: { type: Function, required: true },
    planStatusColor: { type: Function, required: true },
    currentPeriodForPlan: { type: Function, required: true },
    periodUsagePercentage: { type: Function, required: true },
    periodUsageColor: { type: Function, required: true },
    goalProgressPercentage: { type: Function, required: true },
    formatCurrency: { type: Function, required: true },
    formatDate: { type: Function, required: true },
    showPlanUrl: { type: Function, required: true },
});
</script>

<template>
            <Link
                :href="showPlanUrl(plan.id)"
                class="block rounded-[26px] focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-budget-primary/25"
            >
                <article class="relative flex flex-col gap-4 overflow-hidden rounded-[26px] bg-budget-surface p-5 transition sm:p-6">
                <div class="relative flex items-start justify-between gap-3">
                    <div class="flex min-w-0 items-center gap-3.5">
                        <span
                            class="flex h-[58px] w-[58px] shrink-0 items-center justify-center rounded-[20px] text-white"
                            :style="planIconStyle(plan.color)"
                        >
                            <component :is="planIcon(plan.icon)" :size="27" />
                        </span>
                        <div class="min-w-0">
                            <h2 class="truncate text-lg font-bold tracking-tight text-budget-heading sm:text-xl">{{ plan.name }}</h2>
                            <p class="mt-0.5 truncate text-sm text-budget-muted">
                                {{ plan.type === 'goal' ? 'Rencana tujuan' : 'Anggaran belanja' }}
                            </p>
                            <span
                                class="mt-2 inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold"
                                :class="planStatusColor(plan.status)"
                            >
                                <span class="h-2 w-2 rounded-full bg-current"></span>
                                {{ planStatusLabel(plan.status) }}
                            </span>
                        </div>
                    </div>
                    <span class="shrink-0 pt-1 text-xs text-budget-muted">
                        {{ formatDate(plan.starts_on) }}
                    </span>
                </div>

                <div
                    v-if="plan.type === 'spending' && currentPeriodForPlan(plan)"
                    class="relative flex flex-col gap-3 pt-1"
                >
                    <div class="flex items-end justify-between gap-3">
                        <p class="min-w-0 truncate text-sm text-budget-muted">
                            <strong class="text-2xl font-bold tracking-tight text-budget-heading sm:text-[28px]">
                                {{ formatCurrency(currentPeriodForPlan(plan).spent, plan.currency) }}
                            </strong>
                            <span> / {{ formatCurrency(currentPeriodForPlan(plan).allocated_amount, plan.currency) }}</span>
                        </p>
                        <strong class="shrink-0 text-lg font-bold text-budget-heading">
                            {{ Math.round(periodUsagePercentage(currentPeriodForPlan(plan))) }}%
                        </strong>
                    </div>
                    <div class="h-2.5 overflow-hidden rounded-full bg-budget-progress-track">
                        <div
                            class="h-full rounded-full"
                            :class="periodUsageColor(currentPeriodForPlan(plan))"
                            :style="{ width: `${periodUsagePercentage(currentPeriodForPlan(plan))}%` }"
                        ></div>
                    </div>
                </div>
                <div v-else-if="plan.type === 'goal'" class="flex flex-col gap-3 pt-1">
                    <div class="flex items-end justify-between gap-3">
                        <p class="min-w-0 truncate text-sm text-budget-muted">
                            <strong class="text-2xl font-bold tracking-tight text-budget-heading">
                                {{ formatCurrency(plan.goal_funded, plan.currency) }}
                            </strong>
                            <span> / {{ formatCurrency(plan.target_amount, plan.currency) }}</span>
                        </p>
                        <strong class="shrink-0 text-lg font-bold text-budget-heading">
                            {{ Math.round(goalProgressPercentage(plan)) }}%
                        </strong>
                    </div>
                    <div class="h-2.5 overflow-hidden rounded-full bg-budget-progress-track">
                        <div
                            class="h-full rounded-full bg-budget-goal"
                            :style="{ width: `${goalProgressPercentage(plan)}%` }"
                        ></div>
                    </div>
                </div>
                </article>
            </Link>
</template>
