<script setup>
import { getSavingCurrencies } from '@/Features/Savings/lib/saving';

const props = defineProps({
    form: { type: Object, required: true },
    accounts: { type: Array, default: () => [] },
    step: { type: Number, required: true },
});

const emit = defineEmits(['update:step', 'submit']);

const goToGoalDetails = () => {
    if (!props.form.name.trim()) {
        props.form.setError('name', 'Masukkan nama kantong.');

        return;
    }

    props.form.clearErrors();
    emit('update:step', 2);
};
</script>

<template>
    <form
        class="flex flex-col gap-3 rounded-2xl bg-white p-5 shadow-sm"
        @submit.prevent="emit('submit')"
    >
        <div class="flex items-center gap-2" aria-label="Langkah formulir">
            <span
                v-for="currentStep in 2"
                :key="currentStep"
                class="h-2 flex-1 rounded-full"
                :class="currentStep <= step ? 'bg-[#2457DA]' : 'bg-[#E2E8F0]'"
            />
        </div>
        <template v-if="step === 1">
            <p class="text-sm text-[#637083]"> {{ $t("Buat kantong untuk memberi nama pada tujuan dana. Dana belum akan dipindahkan.") }} </p>
            <label class="text-sm">{{ $t("Nama kantong") }} <input
                    v-model="form.name"
                    class="field"
                    maxlength="255"
                    required
                    placeholder="Contoh: Dana darurat"
                />
            </label>
            <label class="text-sm">{{ $t("Currency") }} <select v-model="form.currency" class="field">
                    <option
                        v-for="currency in getSavingCurrencies(accounts)"
                        :key="currency"
                        :value="currency"
                    >
                        {{ currency }}
                    </option>
                </select>
            </label>
            <p v-if="form.errors.name || form.errors.currency" class="text-sm text-red-600">
                {{ form.errors.name || form.errors.currency }}
            </p>
            <ButtonPrimary type="button" @click="goToGoalDetails"> {{ $t("Lanjut") }} </ButtonPrimary>
        </template>
        <template v-else>
            <p class="text-sm text-[#637083]"> {{ $t("Target hanya sebagai pengingat progres. Kamu bisa melewati bagian ini.") }} </p>
            <label class="text-sm">{{ $t("Target nominal (opsional)") }} <AmountInput
                    v-model="form.target_amount"
                    class="field"
                    placeholder="Contoh: 1000000"
                />
            </label>
            <label class="text-sm">{{ $t("Target tanggal (opsional)") }} <input v-model="form.target_date" class="field" type="date" />
            </label>
            <label class="text-sm">{{ $t("Catatan (opsional)") }} <textarea
                    v-model="form.notes"
                    class="field"
                    rows="2"
                    placeholder="Untuk apa kantong ini?"
                />
            </label>
            <p v-for="(error, key) in form.errors" :key="key" class="text-sm text-red-600">
                {{ error }}
            </p>
            <div class="flex gap-3">
                <ButtonPrimary
                    type="button"
                    variant="secondary"
                    class="flex-1 justify-center"
                    @click="emit('update:step', 1)"
                > {{ $t("Back") }} </ButtonPrimary>
                <ButtonPrimary
                    type="submit"
                    class="flex-1 justify-center"
                    :disabled="form.processing"
                > {{ $t("Buat kantong") }} </ButtonPrimary>
            </div>
        </template>
    </form>
</template>

<style scoped>
.field {
    @apply mt-1 w-full rounded-xl border border-[#DCE3EE] bg-white px-3 py-2 text-sm;
}
</style>
