<script setup>
import { useForm } from '@inertiajs/vue3';
import AmountInput from '@/Components/AmountInput.vue';
import { store as transactionStore } from '@/actions/App/Http/Controllers/TransactionController';

const props = defineProps({
    mealRecord: { type: Object, required: true },
    currency: { type: String, default: 'IDR' },
    expenseCategories: { type: Array, default: () => [] },
    accounts: { type: Array, default: () => [] },
});

const emit = defineEmits(['close', 'saved']);

const localDate = (date) => new Intl.DateTimeFormat('sv-SE').format(date);
const form = useForm({
    type: 'expense',
    amount: '',
    date: localDate(new Date()),
    title: props.mealRecord.name,
    note: props.mealRecord.place_name ?? '',
    account_id: props.accounts[0]?.id ?? '',
    category_id: '',
    meal_record_id: props.mealRecord.id,
    return_to_cms_meals: true,
    budget_period_id: null,
    saving_id: null,
});

const submit = () => {
    form.post(transactionStore.url(), {
        preserveScroll: true,
        onSuccess: () => emit('saved'),
    });
};
</script>

<template>
    <Teleport to="body">
        <div
            class="fixed inset-0 z-[100] flex items-end justify-center overflow-y-auto bg-slate-950/50 p-0 sm:items-center sm:p-5"
            @click.self="emit('close')"
            @keydown.esc.prevent="emit('close')"
        >
            <section
                role="dialog"
                aria-modal="true"
                aria-labelledby="meal-expense-title"
                class="max-h-[92vh] w-full max-w-lg overflow-y-auto rounded-t-3xl bg-white p-5 shadow-2xl sm:rounded-3xl sm:p-7"
                @click.stop
            >
                <header class="mb-6 flex items-start justify-between gap-4">
                    <div>
                        <p class="text-sm font-medium text-[#637083]">{{ $t('Catat pengeluaran') }}</p>
                        <h2 id="meal-expense-title" class="mt-1 text-xl font-semibold text-[#152338]">
                            {{ mealRecord.name }}
                        </h2>
                        <p v-if="mealRecord.place_name" class="mt-1 text-sm text-[#637083]">
                            {{ mealRecord.place_name }}
                        </p>
                    </div>
                    <ButtonPrimary
                        type="button"
                        variant="secondary"
                        class="!min-h-0 !rounded-full !px-3 !py-1 text-xl"
                        aria-label="Tutup modal"
                        @click="emit('close')"
                    >
                        ×
                    </ButtonPrimary>
                </header>

                <form class="space-y-5" @submit.prevent="submit">
                    <label class="block text-sm font-medium text-[#314158]">
                        {{ $t('Amount') }} ({{ currency }})
                        <AmountInput
                            v-model="form.amount"
                            required
                            placeholder="Rp 0"
                            class="mt-2 block w-full rounded-xl border border-[#DDE6F5] px-4 py-3 text-lg"
                        />
                        <span v-if="form.errors.amount" class="mt-1 block text-xs text-red-600">
                            {{ form.errors.amount }}
                        </span>
                    </label>

                    <label class="block text-sm font-medium text-[#314158]">
                        {{ $t('Category') }}
                        <select
                            v-model="form.category_id"
                            required
                            class="mt-2 block w-full rounded-xl border border-[#DDE6F5] bg-white px-4 py-3 text-sm"
                        >
                            <option value="" disabled>{{ $t('Pilih kategori') }}</option>
                            <option
                                v-for="category in expenseCategories"
                                :key="category.id"
                                :value="category.id"
                            >
                                {{ category.name }}
                            </option>
                        </select>
                        <span v-if="form.errors.category_id" class="mt-1 block text-xs text-red-600">
                            {{ form.errors.category_id }}
                        </span>
                    </label>

                    <label class="block text-sm font-medium text-[#314158]">
                        {{ $t('Account') }}
                        <select
                            v-model="form.account_id"
                            required
                            class="mt-2 block w-full rounded-xl border border-[#DDE6F5] bg-white px-4 py-3 text-sm"
                        >
                            <option value="" disabled>{{ $t('Select an account') }}</option>
                            <option
                                v-for="account in accounts"
                                :key="account.id"
                                :value="account.id"
                            >
                                {{ account.name }} · {{ account.currency }}
                            </option>
                        </select>
                        <span v-if="form.errors.account_id" class="mt-1 block text-xs text-red-600">
                            {{ form.errors.account_id }}
                        </span>
                    </label>

                    <label class="block text-sm font-medium text-[#314158]">
                        {{ $t('Date') }}
                        <input
                            v-model="form.date"
                            required
                            type="date"
                            class="mt-2 block w-full rounded-xl border border-[#DDE6F5] px-4 py-3 text-sm"
                        />
                        <span v-if="form.errors.date" class="mt-1 block text-xs text-red-600">
                            {{ form.errors.date }}
                        </span>
                    </label>

                    <div v-if="!accounts.length || !expenseCategories.length" class="rounded-xl bg-amber-50 p-3 text-sm text-amber-800">
                        Tambahkan akun IDR dan kategori pengeluaran sebelum mencatat transaksi.
                    </div>

                    <div class="grid grid-cols-2 gap-3 pt-1">
                        <ButtonPrimary
                            type="button"
                            variant="secondary"
                            class="w-full"
                            @click="emit('close')"
                        >
                            {{ $t('Cancel') }}
                        </ButtonPrimary>
                        <ButtonPrimary
                            type="submit"
                            class="w-full"
                            :disabled="form.processing || !accounts.length || !expenseCategories.length"
                        >
                            {{ form.processing ? 'Menyimpan...' : $t('Save') }}
                        </ButtonPrimary>
                    </div>
                </form>
            </section>
        </div>
    </Teleport>
</template>
