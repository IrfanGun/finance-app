<script setup>
import { computed, ref } from 'vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import {
    ArrowDownUp,
    ArrowLeft,
    CalendarDays,
    CircleDollarSign,
    Pencil,
    Trash2,
} from 'lucide-vue-next';
import MobileBottomNav from '@/Components/MobileBottomNav.vue';
import SuccessModal from '@/Components/SuccessModal.vue';
import { destroy, edit as assetEdit, index, status as assetStatus } from '@/routes/assets';
import { logs as depreciationLogs, status as depreciationStatus } from '@/routes/assets/depreciation';

const props = defineProps({
    asset: { type: Object, required: true },
    logs: { type: Object, default: () => ({ data: [], links: [] }) },
});

const page = usePage();
const showSuccess = ref(Boolean(page.flash.asset_success));
const successMessage = computed(() => page.flash.asset_success === 'created'
    ? 'Aset baru berhasil dibuat.'
    : 'Perubahan aset berhasil disimpan.');

const closeSuccess = () => {
    showSuccess.value = false;
};

const depreciationForm = useForm({
    status: props.asset.depreciation?.status === 'paused' ? 'active' : 'paused',
});

const toggleStatus = () => depreciationForm.patch(
    depreciationStatus.url(props.asset.id),
    { preserveScroll: true },
);

const dispose = () => {
    if (window.confirm('Tandai aset ini sebagai disposed? Depresiasinya tidak akan berjalan lagi.')) {
        router.patch(assetStatus.url(props.asset.id), { confirmation: true });
    }
};

const remove = () => {
    if (window.confirm('Hapus aset ini? Riwayat depresiasinya akan tetap tersimpan.')) {
        router.delete(destroy.url(props.asset.id));
    }
};

const formatMoney = (amount, currency = props.asset.currency) => new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency,
    maximumFractionDigits: 2,
}).format(Number(amount ?? 0));

const formatPercentage = (percentage) => `${new Intl.NumberFormat('id-ID', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
}).format(Math.abs(Number(percentage ?? 0)))}%`;

const formatDate = (value) => {
    if (!value) {
        return 'Selesai';
    }

    const date = new Date(`${String(value).slice(0, 10)}T00:00:00Z`);

    return new Intl.DateTimeFormat('id-ID', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
        timeZone: 'UTC',
    }).format(date);
};

const formatShortDate = (value) => {
    if (!value) {
        return 'Selesai';
    }

    const date = new Date(`${String(value).slice(0, 10)}T00:00:00Z`);

    return new Intl.DateTimeFormat('id-ID', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
        timeZone: 'UTC',
    }).format(date);
};

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
    <Head :title="asset.name" />

    <div class="mx-auto min-h-screen w-full max-w-lg bg-white pb-28 text-app-heading">
        <SuccessModal
            :show="showSuccess"
            :message="successMessage"
            @close="closeSuccess"
        />

        <header class="flex items-center justify-between px-6 py-5">
            <Link :href="index.url()" aria-label="Kembali ke daftar aset">
                <ArrowLeft :size="22" class="text-[#2457DA]" />
            </Link>
            <h1 class="max-w-[70%] truncate text-lg font-semibold">{{ asset.name }}</h1>
            <div class="flex items-center gap-3">
                <Link
                    :href="assetEdit.url(asset.id)"
                    class="rounded-lg p-2 text-[#2457DA] hover:bg-[#EEF3FF]"
                    aria-label="Edit aset"
                >
                    <Pencil :size="18" />
                </Link>
                <button type="button" class="text-red-600" aria-label="Hapus aset" @click="remove">
                    <Trash2 :size="19" />
                </button>
            </div>
        </header>

        <main class="space-y-5 px-6 pt-2">
            <section class="rounded-2xl bg-gradient-to-br from-[#173A9A] via-[#1F4AC2] to-[#2969E6] p-5 text-white">
                <p class="text-sm text-blue-100">{{ $t("Estimasi nilai sekarang") }}</p>
                <p class="mt-2 text-3xl font-semibold">{{ formatMoney(asset.current_value) }}</p>
                <p
                    class="mt-3 inline-flex rounded-full px-3 py-1 text-xs capitalize"
                    :class="asset.status === 'active' ? 'bg-white text-[#173A9A]' : 'bg-white/15'"
                >
                    {{ asset.status }}
                </p>

                <div class="mt-4 grid grid-cols-2 gap-4 border-t border-white/20 pt-4">
                    <div>
                        <p class="text-sm text-blue-100">
                            {{ asset.depreciation?.value_direction === 'increase'
                                ? 'Kenaikan akumulatif'
                                : asset.depreciation
                                    ? 'Penurunan akumulatif'
                                    : 'Perubahan akumulatif' }}
                        </p>
                        <p class="mt-1 text-base font-semibold">
                            {{ formatMoney(Math.abs(Number(asset.total_value_change ?? 0))) }}
                        </p>
                    </div>
                    <div class="border-l border-white/20 pl-4">
                        <p class="text-sm text-blue-100">
                            {{ asset.depreciation?.value_direction === 'increase'
                                ? 'Kenaikan bulan lalu'
                                : asset.depreciation
                                    ? 'Penurunan bulan lalu'
                                    : 'Perubahan bulan lalu' }}
                        </p>
                        <p class="mt-1 text-base font-semibold">
                            {{ formatPercentage(asset.last_month_value_change_percentage) }}
                        </p>
                    </div>
                </div>
            </section>

            <section class="space-y-4 rounded-2xl border border-slate-200 p-4">
                <h2 class="font-semibold">{{ $t("Informasi aset") }}</h2>
                <dl class="space-y-4 text-base">
                    <div class="flex items-center justify-between gap-4">
                        <dt class="text-sm text-[#637083]">{{ $t("Asset name") }}</dt>
                        <dd class="text-right font-semibold">{{ asset.name }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-4">
                        <dt class="text-sm text-[#637083]">{{ $t("Purchase price") }}</dt>
                        <dd class="text-right font-semibold">{{ formatMoney(asset.purchase_price) }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-4">
                        <dt class="text-sm text-[#637083]">{{ $t("Currency") }}</dt>
                        <dd class="text-right font-semibold">{{ asset.currency }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-4">
                        <dt class="text-sm text-[#637083]">{{ $t("Tanggal dibeli") }}</dt>
                        <dd class="text-right font-semibold">{{ formatDate(asset.purchase_date) }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-4">
                        <dt class="text-sm text-[#637083]">{{ $t("Total perubahan nilai") }}</dt>
                        <dd class="text-right font-semibold" :class="valueChangeClass(asset.total_value_change)">
                            {{ formatValueChange(asset.total_value_change) }}
                        </dd>
                    </div>
                    <div v-if="asset.description" class="space-y-1 border-t border-slate-100 pt-3">
                        <dt class="text-xs text-[#637083]">{{ $t("Description") }}</dt>
                        <dd class="whitespace-pre-wrap text-sm">{{ asset.description }}</dd>
                    </div>
                </dl>
            </section>

            <section class="space-y-4 rounded-2xl border border-slate-200 p-4">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <h2 class="font-semibold">{{ $t("Pengaturan perubahan nilai") }}</h2>
                        <p class="mt-1 text-sm capitalize text-[#637083]">
                            {{ asset.depreciation?.status ?? 'Belum diatur' }}
                        </p>
                    </div>
                    <button
                        v-if="asset.depreciation && asset.depreciation.status !== 'completed' && asset.status === 'active'"
                        type="button"
                        class="rounded-lg border border-[#2457DA] px-3 py-2 text-sm font-semibold text-[#2457DA] disabled:opacity-60"
                        :disabled="depreciationForm.processing"
                        @click="toggleStatus"
                    >
                        {{ asset.depreciation.status === 'paused' ? 'Aktifkan' : 'Pause' }}
                    </button>
                </div>

                <div v-if="asset.depreciation" class="space-y-4 text-sm">
                    <div class="flex items-center justify-between gap-2">
                        <div class="flex items-center gap-1 whitespace-nowrap">
                            <ArrowDownUp :size="14" class="shrink-0 text-[#2457DA]" />
                            <p class="text-xs font-semibold leading-4">
                                {{ asset.depreciation.value_direction === 'increase' ? 'Naik (apresiasi)' : 'Turun (depresiasi)' }}
                            </p>
                        </div>
                        <div class="flex items-center gap-1 whitespace-nowrap">
                            <CircleDollarSign :size="14" class="shrink-0 text-[#2457DA]" />
                            <p class="text-xs font-semibold leading-4">
                                {{ asset.depreciation.value_direction === 'decrease'
                                    ? formatMoney(asset.depreciation.residual_value)
                                    : '—' }}
                            </p>
                        </div>
                        <div class="flex items-center gap-1 whitespace-nowrap">
                            <CalendarDays :size="14" class="shrink-0 text-[#2457DA]" />
                            <p class="text-xs font-semibold leading-4">
                                {{ formatShortDate(asset.depreciation.next_depreciation_date) }}
                            </p>
                        </div>
                    </div>
                    <div v-if="!asset.depreciation.is_tiered" class="flex items-start justify-between gap-4">
                        <p class="text-sm text-[#637083]"> {{ $t("Nilai") }} {{ asset.depreciation.value_direction === 'increase' ? 'apresiasi' : 'depresiasi' }} {{ $t("per periode") }} </p>
                        <p class="text-right font-medium">
                            {{ asset.depreciation.depreciation_type === 'percentage'
                                ? `${asset.depreciation.depreciation_value}% dari harga beli`
                                : formatMoney(asset.depreciation.depreciation_value) }}
                            · {{ asset.depreciation.depreciation_period === 'monthly' ? 'bulanan' : 'tahunan' }}
                        </p>
                    </div>
                    <div v-else class="space-y-2">
                        <p class="text-sm text-[#637083]">{{ $t("Annual value change schedule") }}</p>
                        <div class="space-y-2">
                            <p
                                v-for="step in asset.depreciation.rate_steps"
                                :key="step.id"
                                class="flex items-center justify-between gap-4 border-slate-100 pb-2 last:border-0 last:pb-0"
                            >
                                <span class="text-[#637083]">{{ $t("Tahun ke-") }}{{ step.year_number }}</span>
                                <span class="text-right font-medium">
                                    {{ asset.depreciation.depreciation_type === 'percentage'
                                        ? `${step.depreciation_value}%`
                                        : formatMoney(step.depreciation_value) }}
                                </span>
                            </p>
                           
                        </div>
                    </div>
                    <p v-if="asset.depreciation.status === 'paused'" class="border-t border-slate-100 pt-3 text-xs leading-5 text-[#637083]"> {{ $t("Saat diaktifkan kembali, periode yang sudah jatuh tempo akan diproses sekaligus.") }} </p>
                </div>
                <p v-else class="text-sm text-[#637083]">{{ $t("Aset ini belum memiliki pengaturan depresiasi.") }}</p>
            </section>

            <section class="space-y-3">
                <div class="flex items-center justify-between">
                    <h2 class="font-semibold">{{ $t("Riwayat perubahan nilai") }}</h2>
                    <Link :href="depreciationLogs.url(asset.id)" class="text-sm font-medium text-[#2457DA]"> {{ $t("Semua riwayat") }} </Link>
                </div>
                <p v-if="!logs.data.length" class="rounded-xl bg-[#F5F7FA] p-4 text-sm text-[#637083]"> {{ $t("Belum ada perubahan nilai yang diproses.") }} </p>
                <article
                    v-for="log in logs.data"
                    :key="log.id"
                    class="flex items-center justify-between rounded-xl bg-[#F5F7FA] p-4"
                >
                    <div>
                        <p class="font-medium">{{ formatDate(log.depreciation_date) }}</p>
                        <p class="mt-1 text-xs text-[#637083]">
                            {{ formatMoney(log.value_before) }} → {{ formatMoney(log.value_after) }}
                        </p>
                    </div>
                    <strong class="text-sm" :class="valueChangeClass(log.value_change)">
                        {{ formatValueChange(log.value_change) }}
                    </strong>
                </article>
                <nav
                    v-if="logs.links?.length > 3"
                    class="flex flex-wrap justify-center gap-2"
                    aria-label="Halaman riwayat perubahan nilai"
                >
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
            </section>

            <button
                v-if="asset.status === 'active'"
                type="button"
                class="h-12 w-full rounded-xl border border-red-300 text-sm font-semibold text-red-700"
                @click="dispose"
            > {{ $t("Tandai aset sebagai disposed") }} </button>
        </main>

        <MobileBottomNav />
    </div>
</template>
