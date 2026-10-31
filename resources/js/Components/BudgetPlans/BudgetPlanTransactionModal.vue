<script setup>
defineProps({
    show: { type: Boolean, default: false },
    transactionForm: { type: Object, required: true },
    selectedPlan: { type: Object, default: null },
    selectedTransactionPeriod: { type: Object, default: null },
    availableCategories: { type: Array, default: () => [] },
    cmsMode: { type: Boolean, default: false },
    planIcon: { type: Function, required: true },
    formatMoney: { type: Function, required: true },
    formatDate: { type: Function, required: true },
});

defineEmits(['close', 'submit']);
</script>

<template>
    <div
        v-if="show && cmsMode"
        class="fixed inset-0 z-40 flex items-center justify-center overflow-y-auto bg-[#152338]/45 px-4 py-6"
        @click.self="$emit('close')"
    >
        <section
            role="dialog"
            aria-modal="true"
            aria-labelledby="transaction-modal-title"
            class="my-auto max-h-[min(90vh,820px)] w-full max-w-[640px] overflow-y-auto rounded-2xl bg-white px-6 py-5 text-[#152338] shadow-2xl sm:px-7 sm:py-6"
        >
            <form class="flex flex-col gap-4" @submit.prevent="$emit('submit')">
                <header class="flex items-center justify-between gap-4">
                    <h2 id="transaction-modal-title" class="text-[23px] font-bold tracking-tight">{{ $t('Catat belanja') }}</h2>
                    <ButtonPrimary type="button" variant="secondary" class="!h-10 !min-h-10 !w-10 !rounded-xl !p-0 !text-xl" :aria-label="$t('Tutup form transaksi')" @click="$emit('close')">×</ButtonPrimary>
                </header>

                <div class="flex items-center justify-between gap-4 rounded-xl bg-[#F1F5FF] px-4 py-3.5">
                    <div class="flex min-w-0 items-center gap-3">
                        <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-[#2463F5] text-white"><component :is="planIcon(selectedPlan?.icon)" :size="22" /></span>
                        <div class="min-w-0">
                            <p class="truncate font-semibold text-[#17243A]">{{ selectedPlan?.name }}</p>
                            <p v-if="selectedTransactionPeriod" class="mt-1 text-xs text-[#687A9B]">
                                {{ formatDate(selectedTransactionPeriod.starts_on) }} – {{ formatDate(selectedTransactionPeriod.ends_on) }}
                            </p>
                        </div>
                    </div>
                    <div class="shrink-0 text-right">
                        <p class="text-xs text-[#687A9B]">{{ $t('Sisa anggaran') }}</p>
                        <strong class="mt-1 block text-base text-[#1764F5]">Rp {{ formatMoney(selectedTransactionPeriod?.remaining) }}</strong>
                    </div>
                </div>

                <label class="text-sm font-medium text-[#17243A]">
                    {{ $t('Nominal') }}
                    <span class="mt-1 flex h-12 items-center overflow-hidden rounded-lg border border-[#D4DCEB] focus-within:border-[#2463F5]">
                        <span class="border-r border-[#E0E6F0] px-4 font-semibold text-[#344563]">Rp</span>
                        <AmountInput v-model="transactionForm.amount" class="h-full min-w-0 flex-1 px-4 text-sm outline-none" required placeholder="0" />
                    </span>
                    <span class="mt-1 block text-xs font-normal text-[#718096]">{{ $t('Maksimal Rp') }} {{ formatMoney(selectedTransactionPeriod?.remaining) }}</span>
                    <span v-if="transactionForm.errors.amount" class="mt-1 block text-xs text-red-600">{{ transactionForm.errors.amount }}</span>
                </label>

                <label class="text-sm font-medium text-[#17243A]">
                    {{ $t('Nama belanja') }}
                    <input v-model="transactionForm.title" class="modal-field" type="text" maxlength="255" required placeholder="Contoh: Belanja sayur" />
                    <span v-if="transactionForm.errors.title" class="mt-1 block text-xs text-red-600">{{ transactionForm.errors.title }}</span>
                </label>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <label class="text-sm font-medium text-[#17243A]">
                        {{ $t('Kategori') }}
                        <select v-model="transactionForm.category_id" class="modal-field">
                            <option value="">{{ $t('Pilih kategori') }}</option>
                            <option v-for="category in availableCategories" :key="category.id" :value="category.id">{{ category.name }}</option>
                        </select>
                        <span v-if="transactionForm.errors.category_id" class="mt-1 block text-xs text-red-600">{{ transactionForm.errors.category_id }}</span>
                    </label>
                    <label class="text-sm font-medium text-[#17243A]">
                        {{ $t('Tanggal') }}
                        <input v-model="transactionForm.date" class="modal-field" type="date" :min="selectedTransactionPeriod?.starts_on" :max="selectedTransactionPeriod?.ends_on" required />
                        <span v-if="transactionForm.errors.date" class="mt-1 block text-xs text-red-600">{{ transactionForm.errors.date }}</span>
                    </label>
                </div>

                <label class="text-sm font-medium text-[#17243A]">
                    {{ $t('Catatan') }} <span class="font-normal text-[#718096]">({{ $t('opsional') }})</span>
                    <textarea v-model="transactionForm.note" class="modal-field min-h-[88px] resize-y" maxlength="1000" rows="3" :placeholder="$t('Tambahkan catatan')" />
                    <span v-if="transactionForm.errors.note" class="mt-1 block text-xs text-red-600">{{ transactionForm.errors.note }}</span>
                </label>

                <p v-if="transactionForm.errors.budget_period_id" class="text-sm text-red-600">{{ transactionForm.errors.budget_period_id }}</p>
                <div class="flex justify-end gap-3 border-t border-[#DCE4F0] pt-4">
                    <ButtonPrimary type="button" variant="secondary" class="!px-5" @click="$emit('close')">{{ $t('Batal') }}</ButtonPrimary>
                    <ButtonPrimary type="submit" class="!px-6" :disabled="transactionForm.processing">{{ transactionForm.processing ? $t('Menyimpan...') : $t('Simpan belanja') }}</ButtonPrimary>
                </div>
            </form>
        </section>
    </div>
        <div
            v-if="show && !cmsMode"
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
                            <p class="text-xs font-medium text-[#637083]">{{ $t("Pengeluaran dari budget") }}</p>
                            <h2 id="transaction-modal-title" class="text-[22px] font-semibold">{{ $t("Catat belanja") }}</h2>
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
                        <p v-if="selectedTransactionPeriod"> {{ $t("Period") }} {{ selectedTransactionPeriod.starts_on }} – {{ selectedTransactionPeriod.ends_on }}
                        </p>
                    </div>

                    <label class="text-sm font-medium"> {{ $t("Amount") }} <AmountInput
                            v-model="transactionForm.amount"
                            class="field"
                            required
                            placeholder="Contoh: 50000"
                        />
                        <span v-if="transactionForm.errors.amount" class="mt-1 block text-xs text-red-600">
                            {{ transactionForm.errors.amount }}
                        </span>
                    </label>

                    <label class="text-sm font-medium"> {{ $t("Expense name") }} <input
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

                    <label class="text-sm font-medium"> {{ $t("Category") }} <select v-model="transactionForm.category_id" class="field">
                            <option value="">{{ $t("Pilih kategori") }}</option>
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

                    <label class="text-sm font-medium"> {{ $t("Date") }} <input
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

                    <label class="text-sm font-medium"> {{ $t("Note") }} <span class="font-normal text-[#637083]">(opsional)</span>
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
                        >{{ $t("Cancel") }}</ButtonPrimary>
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
.modal-field {
    @apply mt-1 h-12 w-full rounded-lg border border-[#D4DCEB] bg-white px-4 text-sm font-normal text-[#17243A] outline-none focus:border-[#2463F5] focus:ring-2 focus:ring-[#2463F5]/15;
}

.field {
    @apply mt-1 w-full rounded-xl border border-[#DCE3EE] bg-white px-3 py-2 text-sm;
}
</style>
