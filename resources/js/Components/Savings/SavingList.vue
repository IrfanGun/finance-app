<script setup>
import { Link } from '@inertiajs/vue3';
import { show as showSaving, manage as manageSaving } from '@/routes/savings';

defineProps({
    savings: { type: Array, default: () => [] },
    formatMoney: { type: Function, required: true },
});
</script>

<template>
    <section class="flex flex-col gap-3">
        <h2 class="font-semibold">Kantong saya</h2>
        <p v-if="!savings.length" class="text-sm text-[#637083]">Belum ada savings.</p>
        <article
            v-for="saving in savings"
            :key="saving.id"
            class="rounded-2xl bg-white p-4 shadow-sm"
        >
            <div class="flex items-center justify-between gap-3">
                <div>
                    <h3 class="font-semibold">{{ saving.name }}</h3>
                    <p class="text-xs text-[#637083]">{{ saving.currency }} · {{ saving.status }}</p>
                </div>
                <strong>Rp {{ formatMoney(saving.balance) }}</strong>
            </div>
            <p v-if="saving.goal_plan" class="mt-2 text-xs text-[#637083]">
                Terkait goal {{ saving.goal_plan.name }} · target Rp {{ formatMoney(saving.goal_plan.target_amount) }}
            </p>
            <p v-else-if="saving.target_amount" class="mt-2 text-xs text-[#637083]">
                Target Rp {{ formatMoney(saving.target_amount) }}
            </p>
            <div class="mt-3 flex gap-2">
                <Link
                    :href="showSaving.url(saving.id)"
                    class="flex min-h-10 flex-1 items-center justify-center rounded-lg border border-[#DCE3EE] px-3 text-sm font-semibold text-[#2457DA]"
                >Detail</Link>
                <Link
                    :href="manageSaving.url(saving.id)"
                    class="flex min-h-10 flex-1 items-center justify-center rounded-lg bg-[#2457DA] px-3 text-sm font-semibold text-white"
                >Kelola</Link>
            </div>
        </article>
    </section>
</template>
