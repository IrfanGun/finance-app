<script setup>
defineProps({
    isOpen: {
        type: Boolean,
        default: false,
    },
    setupTitle: {
        type: String,
        required: true,
    },
    setupSubmitLabel: {
        type: String,
        required: true,
    },
    accountSelectionRequired: {
        type: Boolean,
        default: false,
    },
    accountOptions: {
        type: Array,
        required: true,
    },
    selectedAccountId: {
        type: [Number, String],
        default: null,
    },
    setupResources: {
        type: Array,
        required: true,
    },
    canConfirmSetup: {
        type: Boolean,
        default: false,
    },
    isSavingSetup: {
        type: Boolean,
        default: false,
    },
    accountTypeLabel: {
        type: Function,
        required: true,
    },
});

const emit = defineEmits([
    'cancel',
    'confirm',
    'start-account-creation',
    'update:selectedAccountId',
]);
</script>

<template>
    <div
        v-if="isOpen"
        class="fixed inset-0 z-50 flex items-end justify-center bg-black/40 px-0 sm:items-center sm:px-4"
        @click.self="emit('cancel')"
    >
        <section
            role="dialog"
            aria-modal="true"
            aria-labelledby="setup-title"
            class="max-h-[90dvh] w-full max-w-lg space-y-5 overflow-y-auto rounded-t-[28px] bg-white px-6 pb-8 pt-5 text-[#152338] shadow-xl sm:rounded-[28px]"
        >
            <div class="space-y-2">
                <p class="text-xs font-semibold uppercase tracking-wide text-[#2457DA]">
                    Konfirmasi transaksi
                </p>
                <h2 id="setup-title" class="text-xl font-semibold">{{ setupTitle }}</h2>
                <p v-if="accountSelectionRequired && accountOptions.length" class="text-sm leading-5 text-[#637083]">
                    Transaksi belum dicatat. Pilih akun sumber dana yang akan dihubungkan ke transaksi ini.
                </p>
                <p v-else-if="accountSelectionRequired" class="text-sm leading-5 text-[#637083]">
                    Belum ada asset yang tersedia. Buat asset baru untuk mencatat transaksi ini.
                </p>
                <p v-else class="text-sm leading-5 text-[#637083]">
                    Transaksi belum dicatat. Lengkapi data yang diperlukan; transaksi akan dibuat setelah Anda konfirmasi.
                </p>
            </div>

            <fieldset v-if="accountSelectionRequired && accountOptions.length" class="space-y-3">
                <legend class="text-sm font-medium text-[#314158]">Pilih asset / akun</legend>
                <label
                    v-for="account in accountOptions"
                    :key="account.id"
                    class="flex cursor-pointer items-center gap-3 rounded-xl border p-4 transition"
                    :class="selectedAccountId === account.id ? 'border-[#2457DA] bg-[#EEF3FF]' : 'border-[#E9EDF3] bg-white hover:bg-[#F8FAFC]'"
                >
                    <input
                        type="radio"
                        name="transaction-account"
                        :value="account.id"
                        class="h-4 w-4 border-slate-300 text-[#2457DA] focus:ring-[#2457DA]"
                        :checked="selectedAccountId === account.id"
                        @change="emit('update:selectedAccountId', account.id)"
                    />
                    <span class="min-w-0 flex-1">
                        <span class="block font-semibold">{{ account.name }}</span>
                        <span class="mt-0.5 block text-xs capitalize text-[#637083]">{{ accountTypeLabel(account.type) }}</span>
                    </span>
                </label>
                <button
                    type="button"
                    class="w-full rounded-xl border border-dashed border-[#AABBEA] px-4 py-3 text-sm font-medium text-[#2457DA] hover:bg-[#F7F9FF]"
                    @click="emit('start-account-creation')"
                >
                    + Buat Asset Baru
                </button>
            </fieldset>

            <div
                v-for="(resource, index) in setupResources"
                :key="`${resource.type}-${index}`"
                class="space-y-3 rounded-2xl border border-[#E9EDF3] bg-[#F8FAFC] p-4"
            >
                <h3 class="font-semibold">
                    {{ resource.type === 'account' ? 'Buat Asset Baru' : 'Buat Category Baru / Create New Category' }}
                </h3>
                <label class="block text-sm">
                    {{ resource.type === 'account' ? 'Nama asset/akun' : 'Nama kategori' }}
                    <input
                        v-model="resource.name"
                        required
                        maxlength="100"
                        class="mt-2 h-11 w-full rounded-lg border border-slate-300 bg-white px-3 outline-none focus:border-[#2457DA]"
                    />
                </label>

                <template v-if="resource.type === 'account'">
                    <label class="block text-sm">
                        Tipe akun
                        <select v-model="resource.accountType" class="mt-2 h-11 w-full rounded-lg border border-slate-300 bg-white px-3">
                            <option value="cash">Cash</option>
                            <option value="bank">Bank / e-wallet</option>
                            <option value="investment">Investment</option>
                            <option value="other">Other</option>
                        </select>
                    </label>
                    <label class="block text-sm">
                        Saldo awal
                        <input
                            v-model="resource.openingBalance"
                            type="number"
                            min="0"
                            step="0.01"
                            required
                            class="mt-2 h-11 w-full rounded-lg border border-slate-300 bg-white px-3"
                        />
                    </label>
                </template>

                <p v-else class="text-sm text-[#637083]">
                    Tipe kategori: <span class="font-medium capitalize">{{ resource.categoryType }}</span>
                </p>
            </div>

            <div class="grid gap-3">
                <button
                    type="button"
                    class="h-12 rounded-xl bg-[#2457DA] font-semibold text-white disabled:cursor-not-allowed disabled:opacity-60"
                    :disabled="!canConfirmSetup"
                    @click="emit('confirm')"
                >
                    {{ setupSubmitLabel }}
                </button>
                <button
                    type="button"
                    class="h-11 rounded-xl border border-[#DCE3ED] font-medium text-[#637083] disabled:opacity-60"
                    :disabled="isSavingSetup"
                    @click="emit('cancel')"
                >
                    Batal
                </button>
            </div>
        </section>
    </div>
</template>
