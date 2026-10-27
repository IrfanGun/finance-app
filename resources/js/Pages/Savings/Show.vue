<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, ArrowUpRight, Wallet } from 'lucide-vue-next';
import MobileBottomNav from '@/Components/MobileBottomNav.vue';
import { dashboard } from '@/routes';
import { dashboard as cmsDashboard } from '@/routes/cms';
import { index as cmsSavingsIndex, manage as cmsManage } from '@/routes/cms/savings';
import { index as savingsIndex, manage } from '@/routes/savings';

const props = defineProps({
    cmsMode: { type: Boolean, default: false },
    saving: { type: Object, required: true },
});

const savingsIndexUrl = props.cmsMode ? cmsSavingsIndex.url() : savingsIndex.url();
const manageUrl = (savingId) => props.cmsMode ? cmsManage.url(savingId) : manage.url(savingId);
const dashboardUrl = props.cmsMode ? cmsDashboard.url() : dashboard.url();

const formatMoney = (amount) => new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: props.saving.currency,
    maximumFractionDigits: 2,
}).format(Number(amount ?? 0));

const formatDate = (value) => {
    if (!value) {
        return 'Belum ditentukan';
    }

    return new Intl.DateTimeFormat('id-ID', {
        dateStyle: 'medium',
        timeZone: 'Asia/Jakarta',
    }).format(new Date(value));
};
</script>

<template>
    <Head :title="saving.name" />

    <div class="mx-auto min-h-screen w-full max-w-lg bg-white pb-28 text-app-heading">
        <header class="flex items-center justify-between px-6 py-5">
            <Link :href="savingsIndexUrl" aria-label="Kembali ke daftar Savings">
                <ArrowLeft :size="22" class="text-[#2457DA]" />
            </Link>
            <h1 class="max-w-[70%] truncate text-lg font-semibold">{{ $t("Detail Savings") }}</h1>
            <Link
                :href="manageUrl(saving.id)"
                class="text-sm font-semibold text-[#2457DA]"
            >{{ $t("Manage") }}</Link>
        </header>

        <main class="space-y-5 px-6 pt-2">
            <section class="rounded-2xl bg-gradient-to-br from-[#173A9A] via-[#1F4AC2] to-[#2969E6] p-5 text-white">
                <div class="flex items-start gap-3">
                    <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-white/15">
                        <Wallet :size="22" />
                    </span>
                    <div class="min-w-0 flex-1">
                        <h2 class="truncate text-lg font-semibold">{{ saving.name }}</h2>
                        <p class="mt-1 text-sm capitalize text-blue-100">{{ saving.currency }} · {{ saving.status }}</p>
                    </div>
                </div>
                <p class="mt-6 text-sm text-blue-100">{{ $t("Dana tersedia") }}</p>
                <p class="mt-1 text-3xl font-semibold">{{ formatMoney(saving.balance) }}</p>
                <div v-if="saving.target_amount" class="mt-5 border-t border-white/20 pt-4">
                    <div class="flex justify-between gap-3 text-sm">
                        <span class="text-blue-100">{{ $t("Target") }}</span>
                        <span class="font-semibold">{{ formatMoney(saving.target_amount) }}</span>
                    </div>
                    <div class="mt-3 h-2 overflow-hidden rounded-full bg-white/20">
                        <div
                            class="h-full rounded-full bg-white"
                            :style="{ width: `${Math.min(100, Number(saving.balance) / Number(saving.target_amount) * 100)}%` }"
                        />
                    </div>
                    <p class="mt-2 text-xs text-blue-100"> {{ $t("Target date") }} {{ formatDate(saving.target_date) }}
                    </p>
                </div>
            </section>

            <section v-if="saving.goal_plan" class="space-y-3 rounded-2xl border border-slate-200 p-4">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-xs font-medium text-[#637083]">{{ $t("Terhubung ke rencana tujuan") }}</p>
                        <h2 class="mt-1 font-semibold">{{ saving.goal_plan.name }}</h2>
                    </div>
                    <span class="rounded-full bg-[#EEF3FF] px-3 py-1 text-xs capitalize text-[#2457DA]">
                        {{ saving.goal_plan.status }}
                    </span>
                </div>
                <dl class="grid grid-cols-2 gap-3 text-sm">
                    <div>
                        <dt class="text-xs text-[#637083]">{{ $t("Target tujuan") }}</dt>
                        <dd class="mt-1 font-semibold">{{ formatMoney(saving.goal_plan.target_amount) }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-[#637083]">{{ $t("Tenggat") }}</dt>
                        <dd class="mt-1 font-semibold">{{ formatDate(saving.goal_plan.target_date) }}</dd>
                    </div>
                </dl>
            </section>

            <Link
                :href="manageUrl(saving.id)"
                class="flex min-h-12 items-center justify-center gap-2 rounded-xl bg-[#2457DA] px-5 py-3 font-semibold text-white"
            > {{ $t("Kelola Savings") }} <ArrowUpRight :size="18" />
            </Link>
            <Link
                :href="`${dashboardUrl}?add=1&saving_id=${saving.id}`"
                class="flex min-h-12 items-center justify-center rounded-xl border border-[#DCE3EE] px-5 py-3 text-center font-semibold text-[#2457DA]"
            >{{ $t("Catat belanja dari Savings") }}</Link>

            <section class="space-y-3 rounded-2xl border border-slate-200 p-4">
                <h2 class="font-semibold">{{ $t("Recent activity") }}</h2>
                <p v-if="!saving.logs?.length" class="text-sm text-[#637083]"> {{ $t("Belum ada aktivitas di kantong ini.") }} </p>
                <article
                    v-for="log in saving.logs"
                    :key="log.id"
                    class="flex items-start justify-between gap-3 border-t border-slate-100 pt-3 text-sm"
                >
                    <div>
                        <p class="font-medium">{{ log.reason.replaceAll('_', ' ') }}</p>
                        <p class="mt-1 text-xs text-[#637083]">{{ formatDate(log.occurred_at) }}</p>
                    </div>
                    <p class="shrink-0 font-semibold" :class="log.type === 'allocation' ? 'text-emerald-700' : 'text-[#152338]'">
                        {{ log.type === 'allocation' ? '+' : '−' }}{{ formatMoney(log.amount) }}
                    </p>
                </article>
            </section>
        </main>

        <MobileBottomNav v-if="!cmsMode" />
    </div>
</template>
