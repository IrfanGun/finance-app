<script setup>
import { getSavingCurrencies } from '@/Features/Savings/lib/saving';
import {
    savingColorOptions,
    savingIconOptions,
} from '@/Features/Savings/lib/savingAppearance';

const props = defineProps({
    form: { type: Object, required: true },
    accounts: { type: Array, default: () => [] },
    step: { type: Number, required: true },
    appearanceEnabled: { type: Boolean, default: false },
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
            <div v-if="appearanceEnabled" class="flex flex-col gap-2">
                <span class="text-sm font-medium">Ikon</span>
                <div class="flex flex-wrap gap-2" role="radiogroup" aria-label="Pilih ikon tabungan">
                    <button
                        v-for="icon in savingIconOptions"
                        :key="icon.value"
                        type="button"
                        role="radio"
                        :aria-label="icon.label"
                        :aria-checked="form.icon === icon.value"
                        class="flex h-10 w-10 items-center justify-center rounded-xl border transition"
                        :class="form.icon === icon.value
                            ? 'border-[#2457DA] bg-[#EDF4FF] text-[#2457DA] ring-2 ring-[#2457DA]/20'
                            : 'border-[#DCE3EE] bg-white text-[#637083] hover:border-[#A9BCE9]'"
                        @click="form.icon = icon.value"
                    >
                        <component :is="icon.component" :size="19" />
                    </button>
                </div>
            </div>
            <div v-if="appearanceEnabled" class="flex flex-col gap-2">
                <span class="text-sm font-medium">Warna</span>
                <div class="flex flex-wrap gap-3" role="radiogroup" aria-label="Pilih warna tabungan">
                    <button
                        v-for="color in savingColorOptions"
                        :key="color.value"
                        type="button"
                        role="radio"
                        :aria-label="color.label"
                        :aria-checked="form.color === color.value"
                        class="flex h-9 w-9 items-center justify-center rounded-full ring-offset-2 transition"
                        :class="form.color === color.value ? 'ring-2 ring-[#14213A]' : 'hover:scale-105'"
                        :style="{ backgroundImage: color.gradient }"
                        @click="form.color = color.value"
                    >
                        <span v-if="form.color === color.value" class="sr-only">Dipilih</span>
                    </button>
                </div>
            </div>
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
