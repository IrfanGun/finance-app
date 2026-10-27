<script setup>
defineProps({
    show: { type: Boolean, default: false },
    transactionForm: { type: Object, required: true },
    selectedPlan: { type: Object, default: null },
    selectedTransactionPeriod: { type: Object, default: null },
    availableCategories: { type: Array, default: () => [] },
});

defineEmits(['close', 'submit']);
</script>

<template>
        <div
            v-if="show"
            class="fixed inset-0 z-40 flex items-end bg-black/40 px-0 sm:items-center sm:px-4"
            @click.self="$emit('close')"
        >
            <section
                role="dialog"
                aria-modal="true"
                aria-labelledby="transaction-modal-title"
                class="mx-auto max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-t-[28px] bg-white px-6 pb-8 pt-3 text-[#152338] shadow-xl sm:rounded-2xl"
            >
                <div class="mx-auto mb-5 h-1 w-9 rounded-full bg-[#D5DBE5] sm:hidden" />
                <form class="flex flex-col gap-4" @submit.prevent="$emit('submit')">
                    <header class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-xs font-medium text-[#637083]">Pengeluaran dari budget</p>
                            <h2 id="transaction-modal-title" class="text-[22px] font-semibold">Catat belanja</h2>
                        </div>
                        <ButtonPrimary
                            type="button"
                            variant="secondary"
                            class="!min-h-0 !px-3 !py-1 text-xl"
                            aria-label="Tutup form transaksi"
                            @click="$emit('close')"
                        >×</ButtonPrimary>
                    </header>

                    <div class="rounded-xl bg-[#F1F5FF] px-4 py-3 text-xs text-[#637083]">
                        <p class="font-semibold text-[#2457DA]">{{ selectedPlan?.name }}</p>
                        <p v-if="selectedTransactionPeriod">
                            Periode {{ selectedTransactionPeriod.starts_on }} – {{ selectedTransactionPeriod.ends_on }}
                        </p>
                    </div>

                    <label class="text-sm font-medium">
                        Nominal
                        <input
                            v-model="transactionForm.amount"
                            class="field"
                            type="number"
                            min="0.01"
                            step="0.01"
                            required
                            placeholder="Contoh: 50000"
                        />
                        <span v-if="transactionForm.errors.amount" class="mt-1 block text-xs text-red-600">
                            {{ transactionForm.errors.amount }}
                        </span>
                    </label>

                    <label class="text-sm font-medium">
                        Nama belanja
                        <input
                            v-model="transactionForm.title"
                            class="field"
                            type="text"
                            maxlength="255"
                            required
                            placeholder="Contoh: Belanja bulanan"
                        />
                        <span v-if="transactionForm.errors.title" class="mt-1 block text-xs text-red-600">
                            {{ transactionForm.errors.title }}
                        </span>
                    </label>

                    <label class="text-sm font-medium">
                        Kategori
                        <select v-model="transactionForm.category_id" class="field">
                            <option value="">Pilih kategori</option>
                            <option
                                v-for="category in availableCategories"
                                :key="category.id"
                                :value="category.id"
                            >{{ category.name }}</option>
                        </select>
                        <span v-if="transactionForm.errors.category_id" class="mt-1 block text-xs text-red-600">
                            {{ transactionForm.errors.category_id }}
                        </span>
                    </label>

                    <label class="text-sm font-medium">
                        Tanggal
                        <input
                            v-model="transactionForm.date"
                            class="field"
                            type="date"
                            :min="selectedTransactionPeriod?.starts_on"
                            :max="selectedTransactionPeriod?.ends_on"
                            required
                        />
                        <span v-if="transactionForm.errors.date" class="mt-1 block text-xs text-red-600">
                            {{ transactionForm.errors.date }}
                        </span>
                    </label>

                    <label class="text-sm font-medium">
                        Catatan <span class="font-normal text-[#637083]">(opsional)</span>
                        <textarea
                            v-model="transactionForm.note"
                            class="field min-h-20 resize-y"
                            maxlength="1000"
                            rows="2"
                        />
                        <span v-if="transactionForm.errors.note" class="mt-1 block text-xs text-red-600">
                            {{ transactionForm.errors.note }}
                        </span>
                    </label>

                    <p v-if="transactionForm.errors.budget_period_id" class="text-sm text-red-600">
                        {{ transactionForm.errors.budget_period_id }}
                    </p>
                    <div class="flex gap-3 pt-1">
                        <ButtonPrimary
                            type="button"
                            variant="secondary"
                            class="flex-1"
                            @click="$emit('close')"
                        >Batal</ButtonPrimary>
                        <ButtonPrimary
                            type="submit"
                            class="flex-1"
                            :disabled="transactionForm.processing"
                        >{{ transactionForm.processing ? 'Menyimpan...' : 'Simpan belanja' }}</ButtonPrimary>
                    </div>
                </form>
            </section>
        </div>
</template>

<style scoped>
.field {
    @apply mt-1 w-full rounded-xl border border-[#DCE3EE] bg-white px-3 py-2 text-sm;
}
</style>
