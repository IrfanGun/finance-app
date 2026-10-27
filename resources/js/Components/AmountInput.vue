<script setup lang="ts">
import { ref, watch } from 'vue'

const props = defineProps<{
    modelValue: string | number | null
}>()

const emit = defineEmits<{
    'update:modelValue': [value: string]
}>()

const displayValue = ref(formatValue(props.modelValue))

function formatValue(value: string | number | null): string {
    if (value === '' || value === null || value === undefined) {
        return ''
    }

    const number = Number(value)

    if (!Number.isFinite(number)) {
        return ''
    }

    return new Intl.NumberFormat('id-ID', {
        maximumFractionDigits: 2,
    }).format(number)
}

function parseDisplayValue(value: string): string {
    const sanitizedValue = value.replace(/[^\d,]/g, '')
    const decimalSeparatorIndex = sanitizedValue.lastIndexOf(',')

    if (decimalSeparatorIndex === -1) {
        return sanitizedValue.replace(/\D/g, '')
    }

    const integer = sanitizedValue
        .slice(0, decimalSeparatorIndex)
        .replace(/\D/g, '')
    const fraction = sanitizedValue
        .slice(decimalSeparatorIndex + 1)
        .replace(/\D/g, '')
        .slice(0, 2)

    return fraction.length > 0
        ? `${integer || '0'}.${fraction}`
        : integer
}

function formatInputValue(value: string): string {
    const sanitizedValue = value.replace(/[^\d,]/g, '')
    const decimalSeparatorIndex = sanitizedValue.lastIndexOf(',')
    const integer = (decimalSeparatorIndex === -1
        ? sanitizedValue
        : sanitizedValue.slice(0, decimalSeparatorIndex)
    ).replace(/\D/g, '')
    const fraction = decimalSeparatorIndex === -1
        ? ''
        : sanitizedValue
            .slice(decimalSeparatorIndex + 1)
            .replace(/\D/g, '')
            .slice(0, 2)
    const groupedInteger = integer.replace(/\B(?=(\d{3})+(?!\d))/g, '.')

    if (decimalSeparatorIndex === -1) {
        return groupedInteger
    }

    return `${groupedInteger || '0'},${fraction}`
}

function updateValue(event: Event): void {
    const input = event.target as HTMLInputElement
    const inputBeforeCursor = input.value.slice(
        0,
        input.selectionStart ?? input.value.length,
    )
    const digitPosition = inputBeforeCursor
        .replace(/\D/g, '')
        .length

    displayValue.value = formatInputValue(input.value)
    emit('update:modelValue', parseDisplayValue(input.value))

    let nextPosition = displayValue.value.length
    let digitsSeen = 0

    if (inputBeforeCursor.includes(',') && displayValue.value.includes(',')) {
        const fractionDigitsBeforeCursor = inputBeforeCursor
            .split(',')
            .at(-1)
            ?.replace(/\D/g, '')
            .length ?? 0

        nextPosition = displayValue.value.indexOf(',') + 1 + fractionDigitsBeforeCursor
    } else {
        for (let index = 0; index < displayValue.value.length; index += 1) {
            if (/\d/.test(displayValue.value[index])) {
                digitsSeen += 1
            }

            if (digitsSeen >= digitPosition) {
                nextPosition = index + 1
                break
            }
        }
    }

    requestAnimationFrame(() => {
        input.setSelectionRange(nextPosition, nextPosition)
    })
}

watch(
    () => props.modelValue,
    (value) => {
        displayValue.value = formatValue(value)
    },
)
</script>

<template>
    <input
        v-bind="$attrs"
        :value="displayValue"
        type="text"
        inputmode="decimal"
        @input="updateValue"
    />
</template>
