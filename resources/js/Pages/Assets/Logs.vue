<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft } from 'lucide-vue-next';
import MobileBottomNav from '@/Components/MobileBottomNav.vue';
import { show } from '@/routes/assets';

const props = defineProps({
    asset: { type: Object, required: true },
    logs: { type: Object, default: () => ({ data: [], links: [] }) },
});

const formatMoney = (amount) => new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: props.asset.currency,
    maximumFractionDigits: 2,
}).format(Number(amount ?? 0));
const formatValueChange = (amount) => {
    const value = Number(amount ?? 0);
    const sign = value > 0 ? '+' : value < 0 ? '−' : '';

    return `${sign}${formatMoney(Math.abs(value))}`;
};
const valueChangeClass = (amount) => Number(amount ?? 0) > 0
    ? 'text-emerald-700'
    : Number(amount ?? 0) < 0
        ? 'text-red-700'
        : 'text-[#637083]';
</script>

<template>
    <Head :title="`Riwayat perubahan nilai · ${asset.name}`" />

    <div class="mx-auto min-h-screen w-full max-w-lg bg-white pb-28 text-app-heading">
        <header class="flex items-center justify-between px-6 py-5">
            <Link :href="show.url(asset.id)" aria-label="Back to asset">
                <ArrowLeft :size="22" class="text-[#2457DA]" />
            </Link>
            <h1 class="text-lg font-semibold">{{ $t("Riwayat perubahan nilai") }}</h1>
            <span class="w-[22px]" />
        </header>

        <main class="space-y-3 px-6 pt-5">
            <p v-if="!logs.data.length" class="rounded-2xl bg-[#F5F7FA] px-5 py-10 text-center text-sm text-[#637083]"> {{ $t("Belum ada riwayat perubahan nilai untuk") }} {{ asset.name }}.
            </p>

            <article v-for="log in logs.data" :key="log.id" class="rounded-xl bg-[#F5F7FA] p-4">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <p class="font-semibold">{{ log.depreciation_date }}</p>
                        <p class="mt-1 text-xs text-[#637083]">
                            {{ formatMoney(log.value_before) }} → {{ formatMoney(log.value_after) }}
                        </p>
                    </div>
                    <strong class="shrink-0 text-sm" :class="valueChangeClass(log.value_change)">
                        {{ formatValueChange(log.value_change) }}
                    </strong>
                </div>
            </article>

            <nav v-if="logs.links?.length > 3" class="flex flex-wrap justify-center gap-2 pt-3" aria-label="Depreciation log pages">
                <Link
                    v-for="link in logs.links"
                    :key="`${link.label}-${link.url}`"
                    :href="link.url || '#'"
                    :class="[
                        'rounded-lg px-3 py-2 text-sm',
                        link.active ? 'bg-[#2457DA] text-white' : 'bg-[#F5F7FA] text-[#314158]',
                        !link.url ? 'pointer-events-none opacity-40' : '',
                    ]"
                    v-html="link.label"
                />
            </nav>
        </main>

        <MobileBottomNav />
    </div>
</template>
