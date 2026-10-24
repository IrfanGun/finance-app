<script setup>
import { Filter, X } from 'lucide-vue-next';

defineProps({
    saving: { type: Object, required: true },
    periodOptions: { type: Array, default: () => [] },
    accounts: { type: Array, default: () => [] },
    activeFilterCount: { type: Number, default: 0 },
    sourceFilter: { type: String, default: '' },
    periodFilter: { type: [String, Number], default: '' },
    accountFilter: { type: [String, Number], default: '' },
    isOpen: { type: Boolean, default: false },
    formatMoney: { type: Function, required: true },
});

const emit = defineEmits([
    'update:sourceFilter',
    'update:periodFilter',
    'update:accountFilter',
    'update:isOpen',
    'apply',
    'reverse',
]);

const resetFilters = () => {
    emit('update:sourceFilter', '');
    emit('update:periodFilter', '');
    emit('update:accountFilter', '');
};
</script>

<template>
    <div class="contents">
        <section class="flex flex-col gap-3 rounded-2xl bg-white p-4 shadow-sm sm:p-5">
            <div class="flex items-center justify-between gap-3">
                <h2 class="font-semibold">Riwayat alokasi</h2>
                <ButtonPrimary
                    type="button"
                    variant="secondary"
                    class="inline-flex items-center gap-2"
                    aria-label="Buka filter riwayat alokasi"
                    @click="emit('update:isOpen', true)"
                >
                    <Filter :size="16" />
                    Filter
                    <span v-if="activeFilterCount" class="rounded-full bg-[#2457DA] px-2 py-0.5 text-xs text-white">
                        {{ activeFilterCount }}
                    </span>
                </ButtonPrimary>
            </div>
            <p v-if="activeFilterCount" class="text-xs text-[#637083]">
                {{ activeFilterCount }} filter diterapkan
            </p>
            <div class="max-h-48 overflow-y-auto">
                <p
                    v-for="log in saving.logs"
                    :key="log.id"
                    class="flex items-center justify-between gap-2 border-t border-[#F1F3F7] py-2 text-sm"
                >
                    <span>{{ log.reason }} · {{ log.occurred_at }}</span>
                    <span>{{ log.type === 'allocation' ? '+' : '-' }} Rp {{ formatMoney(log.amount) }}</span>
                    <ButtonPrimary
                        v-if="['direct_allocation', 'goal_contribution'].includes(log.reason) && !log.reversal"
                        type="button"
                        variant="secondary"
                        class="!min-h-0 !px-2 !py-1 !text-xs"
                        @click="emit('reverse', log)"
                    >Balik</ButtonPrimary>
                </p>
            </div>
        </section>

        <div
            v-if="isOpen"
            class="fixed inset-0 z-50 flex items-center justify-center bg-[#152338]/40 p-4"
            role="dialog"
            aria-modal="true"
            aria-labelledby="history-filter-title"
            tabindex="-1"
            @click.self="emit('update:isOpen', false)"
            @keydown.esc="emit('update:isOpen', false)"
        >
            <section class="max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-2xl bg-white p-5 shadow-xl">
                <div class="mb-4 flex items-center justify-between gap-3">
                    <h2 id="history-filter-title" class="font-semibold">Filter riwayat alokasi</h2>
                    <ButtonPrimary
                        type="button"
                        variant="secondary"
                        class="!min-h-0 !p-2"
                        aria-label="Tutup filter"
                        @click="emit('update:isOpen', false)"
                    >
                        <X :size="18" />
                    </ButtonPrimary>
                </div>
                <form class="flex flex-col gap-3" @submit.prevent="emit('apply')">
                    <label class="text-sm">Sumber transaksi
                        <select
                            :value="sourceFilter"
                            class="field"
                            @change="emit('update:sourceFilter', $event.target.value)"
                        >
                            <option value="">Semua sumber</option>
                            <option value="direct_allocation">Alokasi langsung</option>
                            <option value="goal_contribution">Setoran goal</option>
                            <option value="budget_claim">Klaim budget</option>
                            <option value="free_release">Lepas dana</option>
                            <option value="expense">Belanja</option>
                            <option value="reversal">Koreksi</option>
                        </select>
                    </label>
                    <label class="text-sm">Periode
                        <select
                            :value="periodFilter"
                            class="field"
                            @change="emit('update:periodFilter', $event.target.value)"
                        >
                            <option value="">Semua periode</option>
                            <option v-for="period in periodOptions" :key="period.id" :value="period.id">
                                {{ period.label }}
                            </option>
                        </select>
                    </label>
                    <label class="text-sm">Akun
                        <select
                            :value="accountFilter"
                            class="field"
                            @change="emit('update:accountFilter', $event.target.value)"
                        >
                            <option value="">Semua akun</option>
                            <option v-for="account in accounts" :key="account.id" :value="account.id">
                                {{ account.name }}
                            </option>
                        </select>
                    </label>
                    <div class="mt-2 flex gap-2">
                        <ButtonPrimary
                            type="button"
                            variant="secondary"
                            class="flex-1 justify-center"
                            @click="resetFilters"
                        >Reset</ButtonPrimary>
                        <ButtonPrimary type="submit" class="flex-1 justify-center">
                            Terapkan filter
                        </ButtonPrimary>
                    </div>
                </form>
            </section>
        </div>
    </div>
</template>

<style scoped>
.field {
    @apply mt-1 w-full rounded-xl border border-[#DCE3EE] bg-white px-3 py-2 text-sm;
}
</style>
